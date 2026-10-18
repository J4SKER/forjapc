<?php

namespace App\Services;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Enums\TipoMovimiento;
use App\Events\PagoAprobado;
use App\Events\ReembolsoRegistrado;
use App\Models\EventoWebhook;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Reembolso;
use App\Models\User;
use App\Notifications\PagoRechazado;
use App\Notifications\ReembolsoProcesado;
use DomainException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cobro de pedidos con PayPal (sandbox) o transferencia verificada por ventas,
 * reembolsos y notificaciones de la pasarela procesadas una sola vez.
 */
class PagoService
{
    public function __construct(
        private PayPalClient $paypal,
        private TipoCambioService $tipoCambio,
        private PedidoService $pedidos,
        private InventarioService $inventario,
        private BitacoraService $bitacora,
        private TallerService $taller,
    ) {}

    /**
     * Crea la orden en PayPal (en dólares, con el tipo de cambio del día) y el pago pendiente.
     */
    public function iniciarPaypal(Pedido $pedido): Pago
    {
        $this->asegurarCobrable($pedido, permitirVencido: false);

        $tasa = $this->tipoCambio->vigente();
        $usd = round((float) $pedido->total / $tasa, 2);
        $ordenId = $this->paypal->crearOrden($usd, $pedido->numero, "Pedido {$pedido->numero} en ForjaPC");

        $pago = new Pago;
        $pago->forceFill([
            'pedido_id' => $pedido->id,
            'metodo' => MetodoPago::Paypal,
            'estado' => EstadoPago::Pendiente,
            'monto' => $pedido->total,
            'monto_usd' => $usd,
            'tipo_cambio' => $tasa,
            'referencia_externa' => $ordenId,
        ])->save();

        return $pago;
    }

    /**
     * Captura la orden aprobada por el comprador y, si PayPal la completa por el monto esperado, aprueba el pago.
     */
    public function capturarPaypal(Pedido $pedido, string $ordenId): Pago
    {
        $pago = $pedido->pagos()->where('metodo', MetodoPago::Paypal)->where('referencia_externa', $ordenId)->firstOrFail();

        if ($pago->estado === EstadoPago::Aprobado) {
            return $pago;
        }

        $this->asegurarCobrable($pedido);
        $captura = $this->paypal->capturarOrden($ordenId);

        if ($captura['estado'] !== 'COMPLETED' || $captura['moneda'] !== 'USD' || abs($captura['monto'] - (float) $pago->monto_usd) > 0.01) {
            $pago->forceFill(['estado' => EstadoPago::Rechazado, 'motivo_rechazo' => "PayPal respondió {$captura['estado']}.", 'datos' => $captura['respuesta']])->save();

            throw new DomainException('PayPal no completó el pago. No se realizó ningún cobro; intenta de nuevo.');
        }

        $pago->forceFill([
            'comision' => round($captura['comision'] * (float) $pago->tipo_cambio, 2),
            'datos' => ['captura_id' => $captura['captura_id'], 'respuesta' => $captura['respuesta']],
        ])->save();

        return $this->aprobar($pago);
    }

    /**
     * Respaldo por si el navegador no llegó a avisar la aprobación (red caída, ventana cerrada):
     * consulta en PayPal las órdenes pendientes del pedido y captura la que el comprador aprobó.
     */
    public function sincronizarPaypal(Pedido $pedido): void
    {
        if (! $this->paypal->configurado() || ! $pedido->estaPendienteDePago()) {
            return;
        }

        $pendientes = $pedido->pagos()->where('metodo', MetodoPago::Paypal)->where('estado', EstadoPago::Pendiente)
            ->whereNotNull('referencia_externa')->latest('id')->get();

        foreach ($pendientes as $pago) {
            try {
                if (in_array($this->paypal->estadoOrden($pago->referencia_externa), ['APPROVED', 'COMPLETED'], true)) {
                    $this->capturarPaypal($pedido->refresh(), $pago->referencia_externa);

                    return;
                }
            } catch (Throwable $e) {
                Log::warning("No se pudo sincronizar la orden de PayPal {$pago->referencia_externa}: {$e->getMessage()}");
            }
        }
    }

    public function registrarTransferencia(Pedido $pedido, string $banco, string $boleta, float $monto, UploadedFile $comprobante): Pago
    {
        $this->asegurarCobrable($pedido, permitirVencido: false);

        if ($pedido->tienePagoPorVerificar()) {
            throw new DomainException('Ya subiste un comprobante que está pendiente de verificación.');
        }

        $pago = new Pago;
        $pago->forceFill([
            'pedido_id' => $pedido->id,
            'metodo' => MetodoPago::Transferencia,
            'estado' => EstadoPago::Pendiente,
            'monto' => $monto,
            'banco' => $banco,
            'numero_boleta' => $boleta,
            'comprobante_ruta' => $comprobante->store("comprobantes/{$pedido->numero}", 'local'),
        ])->save();

        return $pago;
    }

    /**
     * Aprueba un pago: queda conciliado, la mercadería sale del kardex y el pedido avanza.
     */
    public function aprobar(Pago $pago, ?User $verificador = null): Pago
    {
        return DB::transaction(function () use ($pago, $verificador) {
            $fila = Pago::query()->lockForUpdate()->findOrFail($pago->id);
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($fila->pedido_id);

            if ($fila->estado !== EstadoPago::Pendiente) {
                throw new DomainException('Este pago ya fue procesado.');
            }

            if (! $pedido->estaPendienteDePago()) {
                throw new DomainException("El pedido está «{$pedido->estado->etiqueta()}» y ya no puede recibir pagos.");
            }

            // Un pago de PayPal solo se aprueba con una captura real, nunca a mano.
            if ($fila->metodo === MetodoPago::Paypal && blank($fila->datos['captura_id'] ?? null)) {
                throw new DomainException('Los pagos de PayPal se aprueban solos cuando PayPal confirma el cobro; no se pueden aprobar a mano.');
            }

            if ((float) $fila->monto + 0.009 < (float) $pedido->total) {
                throw new DomainException('El monto pagado (Q'.number_format((float) $fila->monto, 2).') es menor que el total del pedido (Q'.number_format((float) $pedido->total, 2).'). Rechaza el comprobante e indica al cliente que complete el pago.');
            }

            $fila->forceFill([
                'estado' => EstadoPago::Aprobado,
                'verificado_por' => $verificador?->id,
                'verificado_at' => now(),
            ])->save();

            $this->pedidos->registrarPago($pedido, $fila);
            $this->descartarOrdenesAbandonadas($pedido, 'El pedido se pagó con otra orden.');

            if ($verificador) {
                $this->bitacora->registrar('pago_aprobado', $fila, null, ['pedido' => $pedido->numero, 'monto' => $fila->monto]);
            }

            PagoAprobado::dispatch($fila);
            $pago->setRawAttributes($fila->getAttributes(), true);

            return $pago;
        });
    }

    public function rechazar(Pago $pago, string $motivo, User $verificador): Pago
    {
        if ($pago->estado !== EstadoPago::Pendiente) {
            throw new DomainException('Este pago ya fue procesado.');
        }

        $pago->forceFill([
            'estado' => EstadoPago::Rechazado,
            'motivo_rechazo' => $motivo,
            'verificado_por' => $verificador->id,
            'verificado_at' => now(),
        ])->save();

        $this->bitacora->registrar('pago_rechazado', $pago, null, ['pedido' => $pago->pedido->numero, 'motivo' => $motivo]);
        $pago->pedido->usuario->notify(new PagoRechazado($pago));

        return $pago;
    }

    /**
     * Reembolso total o parcial de un pago aprobado; nunca excede lo pagado. Si es total y la
     * mercadería no había salido de la tienda, vuelve al inventario.
     */
    public function reembolsar(Pago $pago, float $monto, string $motivo, bool $devolverInventario): Reembolso
    {
        return DB::transaction(function () use ($pago, $monto, $motivo, $devolverInventario) {
            $fila = Pago::query()->lockForUpdate()->findOrFail($pago->id);
            $pedido = $fila->pedido;

            if ($fila->estado !== EstadoPago::Aprobado) {
                throw new DomainException('Solo se reembolsan pagos aprobados.');
            }

            $monto = round($monto, 2);
            if ($monto <= 0 || $monto > $fila->saldoReembolsable()) {
                throw new DomainException('El monto excede lo que queda por reembolsar (Q'.number_format($fila->saldoReembolsable(), 2).').');
            }

            $esTotal = abs($monto - $fila->saldoReembolsable()) < 0.01;
            $devolver = $devolverInventario && $esTotal && ($pedido->estado->estaEnTienda() || $pedido->estado === EstadoPedido::Entregado);

            [$referencia, $usd] = $fila->metodo === MetodoPago::Paypal && $this->paypal->configurado()
                ? $this->reembolsarEnPaypal($fila, $monto, $esTotal, $motivo)
                : [null, null];

            $reembolso = new Reembolso;
            $reembolso->forceFill([
                'pago_id' => $fila->id,
                'monto' => $monto,
                'motivo' => $motivo,
                'referencia_externa' => $referencia,
                'devuelve_inventario' => $devolver,
                'usuario_id' => Auth::id(),
            ])->save();

            if ($devolver) {
                foreach ($pedido->items()->with('producto')->get() as $item) {
                    $this->inventario->entrada($item->producto, $item->cantidad, (float) $item->costo_unitario, TipoMovimiento::Devolucion, $pedido, "Reembolso del pedido {$pedido->numero}");
                }
            }

            if ($esTotal) {
                $this->pedidos->cambiarEstado($pedido, EstadoPedido::Reembolsado, "Reembolso total: {$motivo}", notificar: false);
                $this->taller->retirarOrdenesPendientes($pedido);
            }

            $this->bitacora->registrar('reembolso_registrado', $reembolso, null, ['pedido' => $pedido->numero, 'monto' => $monto, 'total' => $esTotal]);
            ReembolsoRegistrado::dispatch($reembolso);
            DB::afterCommit(fn () => $pedido->usuario->notify(new ReembolsoProcesado($reembolso, $esTotal, $usd)));

            return $reembolso;
        });
    }

    /**
     * Procesa una notificación de PayPal una sola vez (idempotente por id de evento).
     *
     * @param  array<string, mixed>  $evento
     */
    public function procesarWebhook(array $evento): void
    {
        $registro = EventoWebhook::query()->firstOrNew(['proveedor' => 'PAYPAL', 'evento_id' => (string) ($evento['id'] ?? '')]);

        if ($registro->exists && $registro->procesado_at !== null) {
            return;
        }

        $registro->forceFill(['tipo' => (string) ($evento['event_type'] ?? ''), 'payload' => $evento])->save();

        try {
            if (($evento['event_type'] ?? null) === 'PAYMENT.CAPTURE.COMPLETED') {
                $ordenId = data_get($evento, 'resource.supplementary_data.related_ids.order_id');
                $pago = Pago::query()->where('metodo', MetodoPago::Paypal)->where('referencia_externa', $ordenId)->first();

                if ($pago && $pago->estado === EstadoPago::Pendiente && $pago->pedido->estaPendienteDePago()) {
                    $this->aprobarCapturaDeWebhook($pago, (array) data_get($evento, 'resource', []));
                }
            }

            // El comprador aprobó pero el navegador no pidió la captura: se captura desde aquí.
            if (($evento['event_type'] ?? null) === 'CHECKOUT.ORDER.APPROVED') {
                $pago = Pago::query()->where('metodo', MetodoPago::Paypal)->where('referencia_externa', data_get($evento, 'resource.id'))->first();

                if ($pago && $pago->estado === EstadoPago::Pendiente && $pago->pedido->estaPendienteDePago()) {
                    $this->capturarPaypal($pago->pedido, $pago->referencia_externa);
                }
            }

            $registro->forceFill(['procesado_at' => now(), 'error' => null])->save();
        } catch (Throwable $e) {
            $registro->forceFill(['error' => mb_substr($e->getMessage(), 0, 255)])->save();
        }
    }

    /**
     * Aprueba con los datos de la captura que trae el webhook, con las mismas validaciones
     * de monto y moneda que la captura desde el navegador, y guarda la comisión.
     *
     * @param  array<string, mixed>  $captura
     */
    private function aprobarCapturaDeWebhook(Pago $pago, array $captura): void
    {
        $monto = (float) data_get($captura, 'amount.value', 0);

        if (data_get($captura, 'status') !== 'COMPLETED' || data_get($captura, 'amount.currency_code') !== 'USD' || abs($monto - (float) $pago->monto_usd) > 0.01) {
            throw new DomainException("La captura del webhook no coincide con el pago esperado (US\${$monto}).");
        }

        $pago->forceFill([
            'comision' => round((float) data_get($captura, 'seller_receivable_breakdown.paypal_fee.value', 0) * (float) $pago->tipo_cambio, 2),
            'datos' => ['captura_id' => data_get($captura, 'id'), 'webhook' => true],
        ])->save();

        $this->aprobar($pago);
    }

    /**
     * Devuelve el dinero en PayPal. Cada parcial se convierte a dólares igual; el que cierra el
     * saldo devuelve exactamente lo que queda de la captura, para no pasarse por redondeo.
     *
     * @return array{string, float} Referencia del reembolso en PayPal y dólares devueltos.
     */
    private function reembolsarEnPaypal(Pago $pago, float $monto, bool $esTotal, string $motivo): array
    {
        $tasa = (float) $pago->tipo_cambio;
        $saldoUsd = round((float) $pago->monto_usd - $pago->reembolsos()->get()->sum(fn (Reembolso $r) => round((float) $r->monto / $tasa, 2)), 2);
        $usd = $esTotal ? $saldoUsd : min(round($monto / $tasa, 2), $saldoUsd);

        if ($usd < 0.01) {
            throw new DomainException('El monto es demasiado pequeño para reembolsarlo por PayPal (menos de US$0.01).');
        }

        try {
            return [$this->paypal->reembolsar((string) ($pago->datos['captura_id'] ?? ''), $usd, $motivo), $usd];
        } catch (RequestException $e) {
            Log::warning("PayPal rechazó el reembolso del pago {$pago->id}: ".$e->response->body());
            $detalle = $e->response->json('details.0.description') ?? $e->response->json('message') ?? 'error '.$e->response->status();

            throw new DomainException("PayPal no aceptó el reembolso ({$detalle}). No se registró nada.");
        }
    }

    /**
     * Las órdenes de PayPal que el cliente abrió pero no pagó dejan de figurar como pendientes.
     */
    public function descartarOrdenesAbandonadas(Pedido $pedido, string $motivo): void
    {
        $pedido->pagos()->where('metodo', MetodoPago::Paypal)->where('estado', EstadoPago::Pendiente)
            ->update(['estado' => EstadoPago::Rechazado->value, 'motivo_rechazo' => $motivo, 'updated_at' => now()]);
    }

    private function asegurarCobrable(Pedido $pedido, bool $permitirVencido = true): void
    {
        if (! $pedido->estaPendienteDePago()) {
            throw new DomainException('Este pedido ya no está pendiente de pago.');
        }

        if (! $permitirVencido && $pedido->vence_at?->isPast()) {
            throw new DomainException('Venció el plazo para pagar este pedido.');
        }

        if ($pedido->pagoAprobado()->exists()) {
            throw new DomainException('Este pedido ya tiene un pago aprobado.');
        }
    }
}
