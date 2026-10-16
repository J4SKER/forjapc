<?php

namespace App\Http\Controllers\Tienda;

use App\Enums\TipoComponente;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function categoria(string $slug): View
    {
        $categoria = Categoria::query()->activas()->where('slug', $slug)->firstOrFail();

        return view('tienda.catalogo', ['categoria' => $categoria, 'q' => '']);
    }

    public function buscar(Request $request): View
    {
        return view('tienda.catalogo', ['categoria' => null, 'q' => trim((string) $request->string('q'))]);
    }

    public function producto(string $slug): View
    {
        $producto = Producto::query()->visibles()->where('slug', $slug)
            ->with(['categoria', 'marca', 'imagenes', 'resenasAprobadas.usuario:id,nombre'])
            ->firstOrFail();

        $tipo = $producto->tipoComponente();
        if ($tipo !== null) {
            $producto->load(match ($tipo) {
                TipoComponente::Cpu, TipoComponente::Placa => $tipo->relacion().'.socket',
                TipoComponente::Disipador => $tipo->relacion().'.sockets',
                default => $tipo->relacion(),
            });
        }

        return view('tienda.producto', [
            'producto' => $producto,
            'especificacion' => $producto->especificacion(),
            'relacionados' => Producto::query()->visibles()
                ->where('categoria_id', $producto->categoria_id)
                ->whereKeyNot($producto->id)
                ->with(['categoria', 'marca', 'imagenes'])
                ->orderByRaw('existencia > reservado DESC')
                ->orderByRaw('ABS(precio - ?)', [$producto->precio])
                ->limit(4)
                ->get(),
            'calificacion' => $producto->calificacionPromedio(),
        ]);
    }
}
