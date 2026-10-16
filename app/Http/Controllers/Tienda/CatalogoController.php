<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Catálogo de la tienda. El listado de productos se implementa en el sprint de la semana 3;
 * por ahora las categorías y la búsqueda muestran una página informativa.
 */
class CatalogoController extends Controller
{
    public function categoria(string $slug): View
    {
        $categoria = collect(config('tienda.categorias'))->firstWhere('slug', $slug);

        abort_if($categoria === null, 404);

        return view('tienda.proximamente', [
            'titulo' => $categoria['nombre'],
            'icono' => $categoria['icono'],
            'mensaje' => 'Estamos cargando el catálogo de esta categoría. Muy pronto podrás ver precios y existencias actualizados.',
        ]);
    }

    public function buscar(Request $request): View
    {
        $termino = trim((string) $request->string('q'));

        return view('tienda.proximamente', [
            'titulo' => $termino === '' ? 'Buscar productos' : "Resultados para «{$termino}»",
            'icono' => 'buscar',
            'mensaje' => 'La búsqueda de productos estará disponible cuando el catálogo esté publicado.',
        ]);
    }
}
