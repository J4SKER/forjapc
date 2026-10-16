<?php

namespace App\Services;

use App\Enums\EstadoCompra;
use App\Enums\TipoMovimiento;
use App\Events\AjusteInventarioRegistrado;
use App\Events\CompraRecibida;
use App\Models\Compra;
use App\Models\ParametroSistema;
use App\Models\Producto;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Compras a proveedores: se registran como borrador y, al recibir la mercadería,
 * entran al kardex con su costo sin IVA (el IVA es crédito fiscal).
 */
class CompraService
{
    public function __construct(private InventarioService $inventario, private BitacoraService $bitacora) {}

    /**
     * @param  array{proveedor_id: int, serie_factura?: string|null, numero_factura: string, fecha_factura: string, notas?: string|null}  $datos
     * @param  list<array{producto_id: int, cantidad: int, costo_unitario: float|string}>  $lineas
     */
    public function guardarBorrador(array $datos, array $lineas, ?Compra $compra = null): Compra
    {
        if ($compra !== null && ! $compra->esBorrador()) {
            throw new DomainException('Solo se pueden modificar compras en borrador.');
        }

        return DB::transaction(function () use ($datos, $lineas, $compra) {
            $compra ??= new Compra;
            $compra->fill($datos);
            $compra->usuario_id ??= Auth::id();
            $compra->save();

            $compra->detalle()->delete();

            $subtotal = 0.0;
            foreach ($lineas as $linea) {
                $importe = round($linea['cantidad'] * (float) $linea['costo_unitario'], 2);
                $subtotal += $importe;
                $compra->detalle()->create([
                    'producto_id' => $linea['producto_id'],
                    'cantidad' => $linea['cantidad'],
                    'costo_unitario' => $linea['costo_unitario'],
                    'subtotal' => $importe,
                ]);
            }

            $iva = round($subtotal * ParametroSistema::valor('iva_porcentaje', 12) / 100, 2);
            $compra->forceFill(['subtotal' => $subtotal, 'iva' => $iva, 'total' => $subtotal + $iva])->save();

            return $compra->load('detalle');
        });
    }

    /**
     * Recibe la mercadería: cada línea entra al kardex y se recalcula el costo promedio.
     */
    public function recibir(Compra $compra): Compra
    {
        return DB::transaction(function () use ($compra) {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);

            if (! $compra->esBorrador()) {
                throw new DomainException('La compra ya fue recibida o anulada.');
            }

            if ($compra->detalle()->doesntExist()) {
                throw new DomainException('La compra no tiene productos.');
            }

            foreach ($compra->detalle()->with('producto')->get() as $linea) {
                $this->inventario->entrada(
                    $linea->producto,
                    $linea->cantidad,
                    (float) $linea->costo_unitario,
                    TipoMovimiento::EntradaCompra,
                    $compra,
                    "Factura {$compra->factura()}",
                );
            }

            $compra->forceFill([
                'estado' => EstadoCompra::Recibida,
                'recibida_por' => Auth::id(),
                'recibida_at' => now(),
            ])->save();

            $this->bitacora->registrar('compra_recibida', $compra, null, ['total' => $compra->total]);
            CompraRecibida::dispatch($compra);

            return $compra;
        });
    }

    public function anular(Compra $compra): Compra
    {
        if (! $compra->esBorrador()) {
            throw new DomainException('Solo se anulan compras en borrador; una compra recibida se corrige con un ajuste de inventario.');
        }

        $compra->forceFill(['estado' => EstadoCompra::Anulada])->save();
        $this->bitacora->registrar('compra_anulada', $compra);

        return $compra;
    }

    /**
     * Ajuste manual de inventario con motivo obligatorio.
     */
    public function ajustarInventario(Producto $producto, int $diferencia, string $motivo): void
    {
        DB::transaction(function () use ($producto, $diferencia, $motivo) {
            $movimiento = $this->inventario->ajustar($producto, $diferencia, $motivo);
            $this->bitacora->registrar('ajuste_inventario', $producto, null, [
                'diferencia' => $diferencia,
                'motivo' => $motivo,
                'movimiento_id' => $movimiento->id,
            ]);
            AjusteInventarioRegistrado::dispatch($movimiento);
        });
    }
}
