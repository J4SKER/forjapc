<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CuentaController extends Controller
{
    /**
     * Inicio de "Mi cuenta". El personal entra directo al panel administrativo.
     */
    public function inicio(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->esPersonal()) {
            return redirect()->route('admin.inicio');
        }

        return view('cuenta.inicio', [
            'user' => $user,
            'direccionPrincipal' => $user->direcciones()->with('departamento')->orderByDesc('es_principal')->first(),
        ]);
    }
}
