<?php

namespace App\Services;

use App\Enums\MetodoPago;
use App\Enums\TipoMovimiento;
use App\Models\Compra;
use App\Models\CuentaContable;
use App\Models\MovimientoInventario;
use App\Models\Pago;
use App\Models\Partida;
use App\Models\PeriodoContable;
use App\Models\Reembolso;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Seeders\NomenclaturaSeeder as N;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Contabilidad por partida doble: partidas automáticas por ventas, compras, reembolsos
 * y ajustes de inventario, partidas manuales, reversiones y cierre de períodos.
 */
class ContabilidadService
{
    /**
     * Registra una partida cuadrada. Cada línea: [código de cuenta, debe, haber, descripción].
     *
     * @param  list<array{0: string, 1: float, 2: float, 3?: string|null}>  $lineas
     */
    public function registrar(
        string $descripcion,
        array $lineas,
        string $tipo = 'AUTOMATICA',
        ?CarbonInterface $fecha = null,
        ?string $origenTipo = null,
        ?int $origenId = null,
        ?int $revierte = null,
    ): Partida {
        $lineas = $this->normalizar($lineas);
        $debe = round(array_sum(array_column($lineas, 1)), 2);
        $haber = round(array_sum(array_column($lineas, 2)), 2);

        if ($lineas === [] || abs($debe - $haber) > 0.001 || $debe <= 0) {
            throw new DomainException(sprintf('La partida no cuadra: debe Q%s y haber Q%s.', number_format($debe, 2), number_format($haber, 2)));
        }

        $fecha ??= now();

        return DB::transaction(function () use ($descripcion, $lineas, $tipo, $fecha, $origenTipo, $origenId, $revierte, $debe, $haber) {
            $periodo = $this->periodoPara($fecha);

            if ($periodo->estaCerrado()) {
                throw new DomainException("El período {$periodo->nombre()} está cerrado.");
            }

            $numero = (int) Partida::query()->where('periodo_id', $periodo->id)->lockForUpdate()->max('numero') + 1;
            $cuentas = CuentaContable::query()->whereIn('codigo', array_column($lineas, 0))->get()->keyBy('codigo');

            $partida = new Partida;
            $partida->forceFill([
                'numero' => $numero,
                'fecha' => $fecha->toDateString(),
                'periodo_id' => $periodo->id,
                'descripcion' => mb_substr($descripcion, 0, 255),
                'tipo' => $tipo,
                'origen_tipo' => $origenTipo,
                'origen_id' => $origenId,
                'revierte_partida_id' => $revierte,
                'total_debe' => $debe,
                'total_haber' => $haber,
                'usuario_id' => Auth::id(),
            ])->save();

            foreach ($lineas as [$codigo, $lineaDebe, $lineaHaber, $detalle]) {
                $cuenta = $cuentas->get($codigo) ?? throw new DomainException("La cuenta {$codigo} no existe.");
                $partida->detalle()->forceCreate([
                    'cuenta_id' => $cuenta->id,
                    'debe' => $lineaDebe,
                    'haber' => $lineaHaber,
                    'descripcion' => $detalle,
                ]);
            }

            return $partida;
        });
    }

    /**
     * Compra recibida: inventario al costo y IVA crédito fiscal contra proveedores.
     */
    public function porCompra(Compra $compra): Partida
    {
        return $this->registrar("Compra {$compra->factura()} a {$compra->proveedor->nombre}", [
            [N::INVENTARIO, (float) $compra->subtotal, 0],
            [N::IVA_CREDITO, (float) $compra->iva, 0],
            [N::PROVEEDORES, 0, (float) $compra->total],
        ], origenTipo: 'Compra', origenId: $compra->id);
    }

    /**
     * Pago aprobado: venta (ingresos e IVA débito), costo de venta y comisión de la pasarela.
     */
    public function porVenta(Pago $pago): Partida
    {
        $pedido = $pago->pedido()->with('items')->first();
        $factor = 1 + ($pedido->total > 0 ? (float) $pedido->iva / (float) $pedido->base_imponible : 0.12);
        $cuentaCobro = $pago->metodo === MetodoPago::Paypal ? N::PAYPAL : N::BANCOS;

        $baseArmado = round((float) $pedido->costo_armado / $factor, 2);
        $baseEnvio = round((float) $pedido->costo_envio / $factor, 2);
        $baseVentas = round((float) $pedido->base_imponible - $baseArmado - $baseEnvio, 2);
        $costo = round($pedido->items->sum(fn ($item) => (float) $item->costo_unitario * $item->cantidad), 2);
        $comision = round((float) $pago->comision, 2);

        return $this->registrar("Venta del pedido {$pedido->numero}", [
            [$cuentaCobro, (float) $pedido->total - $comision, 0, 'Cobro del pedido'],
            [N::COMISIONES, $comision, 0, 'Comisión de la pasarela'],
            [N::VENTAS, 0, $baseVentas],
            [N::SERVICIO_ARMADO, 0, $baseArmado],
            [N::INGRESOS_ENVIO, 0, $baseEnvio],
            [N::IVA_DEBITO, 0, (float) $pedido->iva],
            [N::COSTO_VENTAS, $costo, 0, 'Costo de la mercadería vendida'],
            [N::INVENTARIO, 0, $costo],
        ], origenTipo: 'Pago', origenId: $pago->id);
    }

    /**
     * Reembolso: devolución sobre ventas y su IVA; si la mercadería regresa, revierte el costo.
     */
    public function porReembolso(Reembolso $reembolso): Partida
    {
        $pago = $reembolso->pago()->with('pedido.items')->first();
        $pedido = $pago->pedido;
        $monto = (float) $reembolso->monto;
        $base = round($monto * (float) $pedido->base_imponible / (float) $pedido->total, 2);
        $costo = $reembolso->devuelve_inventario
            ? round($pedido->items->sum(fn ($item) => (float) $item->costo_unitario * $item->cantidad), 2)
            : 0.0;

        return $this->registrar("Reembolso del pedido {$pedido->numero}: {$reembolso->motivo}", [
            [N::DEVOLUCIONES, $base, 0],
            [N::IVA_DEBITO, round($monto - $base, 2), 0],
            [$pago->metodo === MetodoPago::Paypal ? N::PAYPAL : N::BANCOS, 0, $monto],
            [N::INVENTARIO, $costo, 0, 'Mercadería devuelta al inventario'],
            [N::COSTO_VENTAS, 0, $costo],
        ], origenTipo: 'Reembolso', origenId: $reembolso->id);
    }

    /**
     * Ajuste de inventario: merma como gasto o sobrante como otro ingreso, al costo promedio.
     */
    public function porAjuste(MovimientoInventario $movimiento): Partida
    {
        $valor = round($movimiento->cantidad * (float) $movimiento->costo_unitario, 2);
        $producto = $movimiento->producto;

        $lineas = $movimiento->tipo === TipoMovimiento::AjusteSalida
            ? [[N::MERMAS, $valor, 0], [N::INVENTARIO, 0, $valor]]
            : [[N::INVENTARIO, $valor, 0], [N::SOBRANTES, 0, $valor]];

        return $this->registrar("Ajuste de inventario {$producto->sku}: {$movimiento->motivo}", $lineas, origenTipo: 'MovimientoInventario', origenId: $movimiento->id);
    }

    /**
     * Partida de apertura con el valor del inventario existente (para instalaciones con mercadería previa).
     */
    public function apertura(float $valorInventario): Partida
    {
        return $this->registrar('Partida de apertura: inventario inicial', [
            [N::INVENTARIO, $valorInventario, 0],
            [N::CAPITAL, 0, $valorInventario],
        ], tipo: 'MANUAL', origenTipo: 'Apertura');
    }

    /**
     * Anula una partida registrando otra con los mismos montos invertidos.
     */
    public function revertir(Partida $partida, string $motivo): Partida
    {
        if ($partida->tipo === 'REVERSION') {
            throw new DomainException('Una partida de reversión no se puede revertir.');
        }

        if ($partida->reversion()->exists()) {
            throw new DomainException('Esta partida ya fue revertida.');
        }

        $lineas = $partida->detalle()->with('cuenta')->get()
            ->map(fn ($d) => [$d->cuenta->codigo, (float) $d->haber, (float) $d->debe, $d->descripcion])
            ->all();

        return $this->registrar("Reversión de la partida {$partida->referencia()}: {$motivo}", $lineas, 'REVERSION', revierte: $partida->id);
    }

    public function cerrarPeriodo(PeriodoContable $periodo, User $usuario): void
    {
        if ($periodo->estaCerrado()) {
            throw new DomainException('El período ya está cerrado.');
        }

        if (PeriodoContable::query()->where('estado', 'ABIERTO')->where(fn ($q) => $q->where('anio', '<', $periodo->anio)
            ->orWhere(fn ($q) => $q->where('anio', $periodo->anio)->where('mes', '<', $periodo->mes)))->exists()) {
            throw new DomainException('Primero cierra los períodos anteriores.');
        }

        $periodo->forceFill(['estado' => 'CERRADO', 'cerrado_por' => $usuario->id, 'cerrado_at' => now()])->save();
    }

    public function periodoPara(CarbonInterface $fecha): PeriodoContable
    {
        $periodo = PeriodoContable::query()->where('anio', $fecha->year)->where('mes', $fecha->month)->first();

        if ($periodo === null) {
            $periodo = new PeriodoContable;
            $periodo->forceFill(['anio' => $fecha->year, 'mes' => $fecha->month, 'estado' => 'ABIERTO'])->save();
        }

        return $periodo;
    }

    /**
     * Quita líneas en cero y redondea a centavos.
     *
     * @param  list<array<int, mixed>>  $lineas
     * @return list<array{0: string, 1: float, 2: float, 3: ?string}>
     */
    private function normalizar(array $lineas): array
    {
        return array_values(array_filter(array_map(
            fn (array $l) => [(string) $l[0], round((float) $l[1], 2), round((float) $l[2], 2), $l[3] ?? null],
            $lineas,
        ), fn (array $l) => $l[1] > 0 || $l[2] > 0));
    }
}
