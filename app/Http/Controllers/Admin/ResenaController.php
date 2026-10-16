<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoResena;
use App\Http\Controllers\Controller;
use App\Models\ResenaProducto;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Moderación de reseñas: solo las aprobadas se publican en la ficha del producto.
 */
class ResenaController extends Controller
{
    public function index(Request $request): View
    {
        $estado = EstadoResena::tryFrom((string) $request->input('estado', 'PENDIENTE')) ?? EstadoResena::Pendiente;

        return view('admin.resenas.index', [
            'resenas' => ResenaProducto::query()->with(['producto:id,nombre,slug', 'usuario:id,nombre,email'])
                ->where('estado', $estado)->latest()->paginate(20)->withQueryString(),
            'estado' => $estado,
            'pendientes' => ResenaProducto::query()->where('estado', EstadoResena::Pendiente)->count(),
        ]);
    }

    public function update(Request $request, ResenaProducto $resena, BitacoraService $bitacora): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', Rule::enum(EstadoResena::class)->only([EstadoResena::Aprobada, EstadoResena::Rechazada])]]);

        $anterior = $resena->estado->value;
        $resena->forceFill(['estado' => $datos['estado']])->save();
        $bitacora->registrar('resena_moderada', $resena, ['estado' => $anterior], ['estado' => $datos['estado']]);

        return back()->with('estado', 'Reseña '.mb_strtolower($resena->estado->etiqueta()).'.');
    }
}
