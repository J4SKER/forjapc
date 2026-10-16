<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * "Ver como": el administrador previsualiza el panel con los accesos de otro rol del personal.
 */
class VistaPreviaController extends Controller
{
    public function __construct(private BitacoraService $bitacora) {}

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->rol === Rol::Admin, 403);

        $datos = $request->validate([
            'rol' => ['required', Rule::enum(Rol::class)->only([Rol::Vendedor, Rol::Tecnico, Rol::Contador])],
        ]);

        $request->session()->put(User::SESION_VISTA_PREVIA, $datos['rol']);
        $this->bitacora->registrar('vista_previa_iniciada', null, null, ['rol' => $datos['rol']]);

        return redirect()->route('admin.inicio')
            ->with('estado', 'Ahora ves el panel como '.Rol::from($datos['rol'])->etiqueta().'.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(User::SESION_VISTA_PREVIA);

        return redirect()->route('admin.inicio')->with('estado', 'Volviste a tu vista de administrador.');
    }
}
