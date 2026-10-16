<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Services\CompatibilidadService;
use App\Support\Configurador\Seleccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Configuraciones de PC guardadas: abrir una compartida y "Mis configuraciones".
 */
class ConfiguracionController extends Controller
{
    public function configurador(): View
    {
        return view('tienda.configurador');
    }

    /**
     * Abre una configuración compartida en el configurador (si no es propia, como copia).
     */
    public function ver(Configuracion $configuracion): View
    {
        return view('tienda.configurador', ['configuracion' => $configuracion]);
    }

    public function index(Request $request, CompatibilidadService $compatibilidad): View
    {
        $configuraciones = $request->user()->configuraciones()->with('componentes')->latest('updated_at')->get()
            ->map(function (Configuracion $configuracion) use ($compatibilidad) {
                $seleccion = Seleccion::desde($configuracion->seleccion());

                return [
                    'modelo' => $configuracion,
                    'seleccion' => $seleccion,
                    'total' => $seleccion->totalComponentes(),
                    'valida' => $compatibilidad->puedeComprarse($seleccion),
                ];
            });

        return view('cuenta.configuraciones', ['configuraciones' => $configuraciones]);
    }

    public function destroy(Request $request, Configuracion $configuracion): RedirectResponse
    {
        abort_unless($configuracion->usuario_id === $request->user()->id, 403);

        $configuracion->delete();

        return back()->with('estado', "Configuración «{$configuracion->nombre}» eliminada.");
    }
}
