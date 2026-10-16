<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\BitacoraAuditoria;
use App\Models\TipoCambio;
use App\Models\User;
use App\Services\ReporteService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tablero del panel: indicadores de ventas, alertas y tareas pendientes según el rol.
 */
class InicioController extends Controller
{
    public function __invoke(Request $request, ReporteService $reportes): View
    {
        $rol = $request->user()->rolEfectivo();

        return view('admin.inicio', [
            'rol' => $rol,
            'tablero' => $reportes->tablero(),
            'tipoCambio' => TipoCambio::vigente(),
            'totalClientes' => User::query()->where('rol', Rol::Cliente)->count(),
            'clientesNuevosMes' => User::query()->where('rol', Rol::Cliente)->where('created_at', '>=', now()->startOfMonth())->count(),
            'actividadReciente' => $rol === Rol::Admin
                ? BitacoraAuditoria::query()->with('usuario:id,nombre')->latest('id')->limit(8)->get()
                : collect(),
        ]);
    }
}
