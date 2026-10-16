<?php

namespace App\Livewire\Tienda;

use App\Models\Producto;
use App\Services\CarritoService;
use DomainException;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Botón "Agregar al carrito" de la ficha y de las tarjetas del catálogo.
 */
class BotonCarrito extends Component
{
    public int $productoId;

    public int $cantidad = 1;

    public bool $conCantidad = false;

    public ?string $mensaje = null;

    public bool $agregado = false;

    public function agregar(CarritoService $carrito): void
    {
        $producto = Producto::query()->visibles()->find($this->productoId);

        try {
            if ($producto === null) {
                throw new DomainException('Este producto ya no está a la venta.');
            }
            $carrito->agregarProducto($producto, max(1, min($this->cantidad, CarritoService::MAXIMO_POR_PRODUCTO)));
            $this->agregado = true;
            $this->mensaje = null;
            $this->dispatch('carrito-actualizado');
        } catch (DomainException $e) {
            $this->mensaje = $e->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.tienda.boton-carrito', [
            'producto' => Producto::query()->find($this->productoId),
        ]);
    }
}
