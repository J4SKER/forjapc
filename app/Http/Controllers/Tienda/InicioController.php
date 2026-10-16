<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(): View
    {
        $relaciones = ['categoria', 'marca', 'imagenes'];

        return view('tienda.inicio', [
            'ofertas' => Producto::query()->visibles()->conDisponibilidad()
                ->whereNotNull('precio_anterior')->whereColumn('precio_anterior', '>', 'precio')
                ->with($relaciones)->orderByRaw('(precio_anterior - precio) / precio_anterior DESC')->limit(4)->get(),
            'destacados' => Producto::query()->visibles()->where('destacado', true)
                ->with($relaciones)->orderByRaw('existencia > reservado DESC')->latest('id')->limit(8)->get(),
        ]);
    }
}
