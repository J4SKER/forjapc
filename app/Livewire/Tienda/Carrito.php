<?php

namespace App\Livewire\Tienda;

use App\Models\CarritoItem;
use App\Services\CarritoService;
use DomainException;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Página del carrito: cantidades, quitar artículos y paso al checkout.
 */
class Carrito extends Component
{
    public function cambiarCantidad(int $itemId, int $cantidad, CarritoService $carrito): void
    {
        $this->conItem($itemId, fn (CarritoItem $item) => $carrito->actualizarCantidad($item, $cantidad));
    }

    public function quitar(int $itemId, CarritoService $carrito): void
    {
        $this->conItem($itemId, fn (CarritoItem $item) => $carrito->quitar($item));
    }

    public function render(CarritoService $carrito): View
    {
        return view('livewire.tienda.carrito', ['resumen' => $carrito->resumen()]);
    }

    private function conItem(int $itemId, callable $accion): void
    {
        $item = CarritoItem::query()->with('producto')->find($itemId);

        try {
            if ($item !== null) {
                $accion($item);
            }
        } catch (DomainException) {
            // El artículo no pertenece a este carrito: se ignora.
        }

        $this->dispatch('carrito-actualizado');
    }
}
