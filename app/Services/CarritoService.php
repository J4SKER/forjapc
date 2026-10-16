<?php

namespace App\Services;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\ParametroSistema;
use App\Models\Producto;
use App\Models\User;
use App\Support\Configurador\Seleccion;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Carrito de compras persistente: para clientes con sesión se guarda por usuario y para
 * visitantes en la sesión; al iniciar sesión se fusionan.
 */
class CarritoService
{
    public const SESION = 'carrito_id';

    public const MAXIMO_POR_PRODUCTO = 10;

    public function __construct(private CompatibilidadService $compatibilidad) {}

    /**
     * Carrito actual (lo crea solo si $crear es verdadero).
     */
    public function actual(bool $crear = false): ?Carrito
    {
        if ($usuario = Auth::user()) {
            return $crear
                ? Carrito::query()->firstOrCreate(['usuario_id' => $usuario->id])
                : Carrito::query()->where('usuario_id', $usuario->id)->first();
        }

        $carrito = Carrito::query()->whereNull('usuario_id')->find(session(self::SESION));

        if ($carrito === null && $crear) {
            $carrito = Carrito::query()->create();
            session()->put(self::SESION, $carrito->id);
        }

        return $carrito;
    }

    public function agregarProducto(Producto $producto, int $cantidad = 1): CarritoItem
    {
        if (! $producto->activo || ! $producto->estaDisponible()) {
            throw new DomainException("«{$producto->nombre}» no tiene existencia disponible.");
        }

        $carrito = $this->actual(crear: true);
        $item = $carrito->items()->firstOrNew(['tipo' => CarritoItem::PRODUCTO, 'producto_id' => $producto->id]);
        $nueva = min(($item->cantidad ?? 0) + $cantidad, self::MAXIMO_POR_PRODUCTO, $producto->disponible());
        $item->cantidad = max(1, $nueva);
        $item->save();
        $carrito->touch();

        return $item;
    }

    public function agregarConfiguracion(Seleccion $seleccion, string $nombre, bool $incluyeArmado): CarritoItem
    {
        if (! $this->compatibilidad->puedeComprarse($seleccion)) {
            throw new DomainException('La configuración está incompleta, tiene piezas incompatibles o sin existencia.');
        }

        $carrito = $this->actual(crear: true);
        $item = $carrito->items()->create([
            'tipo' => CarritoItem::CONFIGURACION,
            'cantidad' => 1,
            'nombre' => mb_substr($nombre, 0, 120),
            'incluye_armado' => $incluyeArmado,
            'componentes' => $seleccion->lineas()->map(fn ($l) => [
                'tipo' => $l['tipo']->value,
                'producto_id' => $l['producto']->id,
                'cantidad' => $l['cantidad'],
            ])->all(),
        ]);
        $carrito->touch();

        return $item;
    }

    public function actualizarCantidad(CarritoItem $item, int $cantidad): void
    {
        $this->asegurarPropiedad($item);

        if ($item->esConfiguracion()) {
            return;
        }

        $item->cantidad = max(1, min($cantidad, self::MAXIMO_POR_PRODUCTO, max(1, $item->producto->disponible())));
        $item->save();
    }

    public function quitar(CarritoItem $item): void
    {
        $this->asegurarPropiedad($item);
        $item->delete();
    }

    public function vaciar(Carrito $carrito): void
    {
        $carrito->items()->delete();
    }

    /**
     * Mueve el carrito del visitante al del usuario que acaba de iniciar sesión.
     */
    public function fusionar(User $usuario, ?int $carritoVisitanteId): void
    {
        $visitante = $carritoVisitanteId ? Carrito::query()->whereNull('usuario_id')->find($carritoVisitanteId) : null;

        if ($visitante === null) {
            return;
        }

        DB::transaction(function () use ($usuario, $visitante) {
            $carrito = Carrito::query()->firstOrCreate(['usuario_id' => $usuario->id]);

            foreach ($visitante->items as $item) {
                $existente = $item->esConfiguracion() ? null
                    : $carrito->items()->where('tipo', CarritoItem::PRODUCTO)->where('producto_id', $item->producto_id)->first();

                if ($existente) {
                    $existente->update(['cantidad' => min($existente->cantidad + $item->cantidad, self::MAXIMO_POR_PRODUCTO)]);
                    $item->delete();
                } else {
                    $item->forceFill(['carrito_id' => $carrito->id])->save();
                }
            }

            $visitante->delete();
        });
    }

    /**
     * Resumen del carrito con precios vigentes y validación de existencias.
     *
     * @return array{lineas: list<array<string, mixed>>, subtotal: float, armado: float, articulos: int, hay_problemas: bool}
     */
    public function resumen(?Carrito $carrito = null): array
    {
        $carrito ??= $this->actual();
        $items = $carrito?->items()->with('producto.categoria')->get() ?? collect();
        $costoArmado = (float) ParametroSistema::valor('costo_servicio_armado', 250);

        $lineas = [];
        foreach ($items as $item) {
            $lineas[] = $item->esConfiguracion() ? $this->lineaConfiguracion($item, $costoArmado) : $this->lineaProducto($item);
        }

        return [
            'lineas' => $lineas,
            'subtotal' => round(array_sum(array_column($lineas, 'subtotal')), 2),
            'armado' => round(array_sum(array_column($lineas, 'armado')), 2),
            'articulos' => (int) array_sum(array_column($lineas, 'cantidad')),
            'hay_problemas' => collect($lineas)->contains(fn ($l) => $l['problema'] !== null),
        ];
    }

    public function cantidadArticulos(): int
    {
        $carrito = $this->actual();

        return $carrito ? (int) $carrito->items()->sum('cantidad') : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function lineaProducto(CarritoItem $item): array
    {
        $producto = $item->producto;
        $problema = match (true) {
            $producto === null || ! $producto->activo => 'Este producto ya no está a la venta.',
            $producto->disponible() < $item->cantidad => $producto->disponible() > 0
                ? "Solo quedan {$producto->disponible()} unidad(es)."
                : 'Sin existencia.',
            default => null,
        };

        return [
            'item' => $item,
            'tipo' => CarritoItem::PRODUCTO,
            'descripcion' => $producto?->nombre ?? 'Producto no disponible',
            'producto' => $producto,
            'componentes' => [],
            'precio' => (float) ($producto?->precio ?? 0),
            'cantidad' => $item->cantidad,
            'subtotal' => round((float) ($producto?->precio ?? 0) * $item->cantidad, 2),
            'armado' => 0.0,
            'problema' => $problema,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lineaConfiguracion(CarritoItem $item, float $costoArmado): array
    {
        $ids = [];
        foreach ($item->componentes ?? [] as $componente) {
            $ids[$componente['tipo']][$componente['producto_id']] = $componente['cantidad'];
        }
        $seleccion = Seleccion::desde($ids);

        $problema = null;
        if ($seleccion->lineas()->count() !== count($item->componentes ?? [])) {
            $problema = 'Alguna pieza de la configuración ya no está disponible.';
        } elseif (! $this->compatibilidad->puedeComprarse($seleccion)) {
            $problema = 'Alguna pieza ya no tiene existencia o la configuración dejó de ser válida.';
        }

        return [
            'item' => $item,
            'tipo' => CarritoItem::CONFIGURACION,
            'descripcion' => $item->nombre,
            'producto' => null,
            'componentes' => $seleccion->lineas()->all(),
            'precio' => $seleccion->totalComponentes(),
            'cantidad' => 1,
            'subtotal' => round($seleccion->totalComponentes(), 2),
            'armado' => $item->incluye_armado ? $costoArmado : 0.0,
            'problema' => $problema,
        ];
    }

    private function asegurarPropiedad(CarritoItem $item): void
    {
        if ($item->carrito_id !== $this->actual()?->id) {
            throw new DomainException('El artículo no pertenece a tu carrito.');
        }
    }
}
