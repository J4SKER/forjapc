<?php

namespace App\Services;

use App\Enums\TipoMovimiento;
use App\Exceptions\InventarioInsuficienteException;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Kardex: único punto que modifica existencias, reservas y costo promedio ponderado.
 * Cada operación bloquea la fila del producto para evitar vender lo que no hay.
 */
class InventarioService
{
    /**
     * Ingresa mercadería y recalcula el costo promedio ponderado.
     */
    public function entrada(Producto $producto, int $cantidad, float $costoUnitario, TipoMovimiento $tipo, ?Model $referencia = null, ?string $motivo = null): MovimientoInventario
    {
        $this->validarCantidad($cantidad);

        if (! $tipo->esEntrada()) {
            throw new InvalidArgumentException("{$tipo->value} no es un movimiento de entrada.");
        }

        if ($costoUnitario <= 0) {
            throw new InvalidArgumentException('El costo unitario debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($producto, $cantidad, $costoUnitario, $tipo, $referencia, $motivo) {
            $fila = $this->bloquear($producto);

            $existenciaNueva = $fila->existencia + $cantidad;
            $promedioNuevo = (($fila->existencia * (float) $fila->costo_promedio) + ($cantidad * $costoUnitario)) / $existenciaNueva;

            $movimiento = $this->registrar($fila, $tipo, $cantidad, $existenciaNueva, $costoUnitario, $promedioNuevo, $fila->reservado, $referencia, $motivo);
            $producto->setRawAttributes($fila->getAttributes(), true);

            return $movimiento;
        });
    }

    /**
     * Saca mercadería al costo promedio vigente. Con $desdeReserva consume unidades
     * reservadas por un pedido; si no, solo puede usar las disponibles.
     */
    public function salida(Producto $producto, int $cantidad, TipoMovimiento $tipo, ?Model $referencia = null, ?string $motivo = null, bool $desdeReserva = false): MovimientoInventario
    {
        $this->validarCantidad($cantidad);

        if ($tipo->esEntrada()) {
            throw new InvalidArgumentException("{$tipo->value} no es un movimiento de salida.");
        }

        return DB::transaction(function () use ($producto, $cantidad, $tipo, $referencia, $motivo, $desdeReserva) {
            $fila = $this->bloquear($producto);

            $reservadoNuevo = $fila->reservado;

            if ($desdeReserva) {
                if ($fila->reservado < $cantidad) {
                    throw InventarioInsuficienteException::para($fila, $cantidad, $fila->reservado);
                }
                $reservadoNuevo -= $cantidad;
            } elseif ($fila->disponible() < $cantidad) {
                throw InventarioInsuficienteException::para($fila, $cantidad, $fila->disponible());
            }

            $promedio = (float) $fila->costo_promedio;

            $movimiento = $this->registrar($fila, $tipo, $cantidad, $fila->existencia - $cantidad, $promedio, $promedio, $reservadoNuevo, $referencia, $motivo);
            $producto->setRawAttributes($fila->getAttributes(), true);

            return $movimiento;
        });
    }

    /**
     * Ajuste por conteo físico: diferencia positiva (sobrante) o negativa (merma). El motivo es obligatorio.
     */
    public function ajustar(Producto $producto, int $diferencia, string $motivo): MovimientoInventario
    {
        if (trim($motivo) === '') {
            throw new InvalidArgumentException('El ajuste de inventario requiere un motivo.');
        }

        if ($diferencia > 0) {
            $costo = (float) $producto->costo_promedio > 0 ? (float) $producto->costo_promedio : (float) $producto->precio / 1.12;

            return $this->entrada($producto, $diferencia, $costo, TipoMovimiento::AjusteEntrada, null, $motivo);
        }

        return $this->salida($producto, abs($diferencia), TipoMovimiento::AjusteSalida, null, $motivo);
    }

    /**
     * Aparta unidades para un pedido sin sacarlas de bodega.
     */
    public function reservar(Producto $producto, int $cantidad): void
    {
        $this->validarCantidad($cantidad);

        DB::transaction(function () use ($producto, $cantidad) {
            $fila = $this->bloquear($producto);

            if ($fila->disponible() < $cantidad) {
                throw InventarioInsuficienteException::para($fila, $cantidad, $fila->disponible());
            }

            Producto::conCambioDeInventario(fn () => $fila->forceFill(['reservado' => $fila->reservado + $cantidad])->save());
            $producto->setRawAttributes($fila->getAttributes(), true);
        });
    }

    /**
     * Devuelve a disponible unidades reservadas (pedido cancelado o vencido).
     */
    public function liberarReserva(Producto $producto, int $cantidad): void
    {
        $this->validarCantidad($cantidad);

        DB::transaction(function () use ($producto, $cantidad) {
            $fila = $this->bloquear($producto);

            Producto::conCambioDeInventario(fn () => $fila->forceFill(['reservado' => max(0, $fila->reservado - $cantidad)])->save());
            $producto->setRawAttributes($fila->getAttributes(), true);
        });
    }

    private function bloquear(Producto $producto): Producto
    {
        return Producto::query()->withTrashed()->lockForUpdate()->findOrFail($producto->getKey());
    }

    private function registrar(
        Producto $fila,
        TipoMovimiento $tipo,
        int $cantidad,
        int $existenciaNueva,
        float $costoUnitario,
        float $promedioNuevo,
        int $reservadoNuevo,
        ?Model $referencia,
        ?string $motivo,
    ): MovimientoInventario {
        $movimiento = MovimientoInventario::create([
            'producto_id' => $fila->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'existencia_anterior' => $fila->existencia,
            'existencia_nueva' => $existenciaNueva,
            'costo_unitario' => round($costoUnitario, 4),
            'costo_promedio_anterior' => $fila->costo_promedio,
            'costo_promedio_nuevo' => round($promedioNuevo, 4),
            'referencia_tipo' => $referencia ? class_basename($referencia) : null,
            'referencia_id' => $referencia?->getKey(),
            'motivo' => $motivo,
            'usuario_id' => Auth::id(),
        ]);

        Producto::conCambioDeInventario(fn () => $fila->forceFill([
            'existencia' => $existenciaNueva,
            'reservado' => $reservadoNuevo,
            'costo_promedio' => round($promedioNuevo, 4),
        ])->save());

        return $movimiento;
    }

    private function validarCantidad(int $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero.');
        }
    }
}
