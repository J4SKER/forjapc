<?php

namespace App\Livewire\Tienda;

use App\Services\CarritoService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Ícono del carrito del encabezado con la cantidad de artículos y el subtotal.
 */
class ContadorCarrito extends Component
{
    #[On('carrito-actualizado')]
    public function refrescar(): void
    {
        // Volver a dibujar el componente basta para actualizar los números.
    }

    public function render(CarritoService $carrito): View
    {
        $resumen = $carrito->resumen();

        return view('livewire.tienda.contador-carrito', [
            'articulos' => $resumen['articulos'],
            'total' => $resumen['subtotal'] + $resumen['armado'],
        ]);
    }
}
