<?php

namespace App\Support\Configurador;

use App\Enums\TipoComponente;
use App\Models\Producto;
use Illuminate\Support\Collection;

/**
 * Componentes elegidos en el configurador, con sus especificaciones cargadas.
 * Solo el almacenamiento admite varios productos; la RAM admite varios kits del mismo producto.
 */
final class Seleccion
{
    public const RELACIONES = [
        'categoria', 'especificacionCpu.socket', 'especificacionPlaca.socket', 'especificacionRam',
        'especificacionGpu', 'especificacionAlmacenamiento', 'especificacionFuente',
        'especificacionGabinete', 'especificacionDisipador.sockets',
    ];

    /**
     * @param  array<string, list<array{producto: Producto, cantidad: int}>>  $lineas
     */
    private function __construct(private array $lineas) {}

    /**
     * @param  array<string, array<int|string, int|string>>  $ids  tipo => [producto_id => cantidad]
     */
    public static function desde(array $ids): self
    {
        $todos = collect($ids)->flatMap(fn ($productos) => array_keys((array) $productos))->unique()->all();
        $productos = Producto::query()->with(self::RELACIONES)->whereKey($todos)->get()->keyBy('id');

        $lineas = [];
        foreach ($ids as $tipo => $porProducto) {
            $tipoComponente = TipoComponente::tryFrom((string) $tipo);
            foreach ((array) $porProducto as $id => $cantidad) {
                $producto = $productos->get((int) $id);
                if ($tipoComponente === null || $producto === null || $producto->tipoComponente() !== $tipoComponente) {
                    continue;
                }
                $lineas[$tipoComponente->value][] = ['producto' => $producto, 'cantidad' => max(1, (int) $cantidad)];
            }
        }

        return new self($lineas);
    }

    /**
     * Copia de la selección con un producto colocado en su tipo (reemplaza, salvo almacenamiento que se agrega).
     */
    public function con(Producto $producto, int $cantidad = 1): self
    {
        $tipo = $producto->tipoComponente();
        $lineas = $this->lineas;

        if ($tipo === TipoComponente::Almacenamiento) {
            $lineas[$tipo->value] = array_values(array_filter($lineas[$tipo->value] ?? [], fn ($l) => $l['producto']->id !== $producto->id));
            $lineas[$tipo->value][] = ['producto' => $producto, 'cantidad' => $cantidad];
        } else {
            $lineas[$tipo->value] = [['producto' => $producto, 'cantidad' => $cantidad]];
        }

        return new self($lineas);
    }

    public function producto(TipoComponente $tipo): ?Producto
    {
        return $this->lineas[$tipo->value][0]['producto'] ?? null;
    }

    public function cantidad(TipoComponente $tipo): int
    {
        return (int) array_sum(array_column($this->lineas[$tipo->value] ?? [], 'cantidad'));
    }

    public function tiene(TipoComponente $tipo): bool
    {
        return isset($this->lineas[$tipo->value][0]);
    }

    /**
     * @return list<array{producto: Producto, cantidad: int}>
     */
    public function de(TipoComponente $tipo): array
    {
        return $this->lineas[$tipo->value] ?? [];
    }

    /**
     * Todas las líneas en el orden del configurador.
     *
     * @return Collection<int, array{tipo: TipoComponente, producto: Producto, cantidad: int}>
     */
    public function lineas(): Collection
    {
        return collect(Configurador::PASOS)->flatMap(
            fn (TipoComponente $tipo) => array_map(fn ($l) => ['tipo' => $tipo] + $l, $this->lineas[$tipo->value] ?? [])
        )->values();
    }

    public function vacia(): bool
    {
        return $this->lineas === [];
    }

    public function totalComponentes(): float
    {
        return (float) $this->lineas()->sum(fn ($l) => (float) $l['producto']->precio * $l['cantidad']);
    }
}
