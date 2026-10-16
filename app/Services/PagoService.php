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
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

            $fila->forceFill([
                'estado' => EstadoPago::Aprobado,
                'verificado_por' => $verificador?->id,
                'verificado_at' => now(),
            ])->save();

            $this->pedidos->registrarPago($pedido, $fila);

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

            $referencia = null;
            if ($fila->metodo === MetodoPago::Paypal && $this->paypal->configurado()) {
                $usd = round($monto / (float) $fila->tipo_cambio, 2);
                $referencia = $this->paypal->reembolsar((string) ($fila->datos['captura_id'] ?? ''), $usd, $motivo);
            }

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
                $this->pedidos->cambiarEstado($pedido, EstadoPedido::Reembolsado, "Reembolso total: {$motivo}");
            }

            $this->bitacora->registrar('reembolso_registrado', $reembolso, null, ['pedido' => $pedido->numero, 'monto' => $monto, 'total' => $esTotal]);
            ReembolsoRegistrado::dispatch($reembolso);

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
                    $pago->forceFill(['datos' => ['captura_id' => data_get($evento, 'resource.id'), 'webhook' => true]])->save();
                    $this->aprobar($pago);
                }
            }

            $registro->forceFill(['procesado_at' => now(), 'error' => null])->save();
        } catch (Throwable $e) {
            $registro->forceFill(['error' => mb_substr($e->getMessage(), 0, 255)])->save();
        }
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
