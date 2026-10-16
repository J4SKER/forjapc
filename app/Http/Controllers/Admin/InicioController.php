<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\BitacoraAuditoria;
use App\Models\TipoCambio;
use App\Models\User;
use Illuminate\View\View;

/**
 * Inicio del panel administrativo. El tablero de ventas llega con el sprint de reportes.
 */
class InicioController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.inicio', [
            'totalClientes' => User::query()->where('rol', Rol::Cliente)->count(),
            'clientesNuevosMes' => User::query()->where('rol', Rol::Cliente)->where('created_at', '>=', now()->startOfMonth())->count(),
            'totalPersonal' => User::query()->where('rol', '!=', Rol::Cliente)->count(),
            'cuentasBloqueadas' => User::query()->where('bloqueado_hasta', '>', now())->count(),
            'tipoCambio' => TipoCambio::vigente(),
            'actividadReciente' => BitacoraAuditoria::query()->with('usuario:id,nombre')->latest('id')->limit(8)->get(),
        ]);
    }
}
