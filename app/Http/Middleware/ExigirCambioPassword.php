<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obliga a cambiar la contraseña temporal (cuentas creadas por el administrador)
 * antes de usar cualquier otra parte del sistema.
 */
class ExigirCambioPassword
{
    /**
     * Rutas que se pueden usar mientras la contraseña está pendiente de cambio.
     *
     * @var list<string>
     */
    private const RUTAS_PERMITIDAS = ['profile.edit', 'password.update', 'logout', 'verification.*', 'dos-pasos.*'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->debe_cambiar_password && ! $request->routeIs(...self::RUTAS_PERMITIDAS)) {
            return redirect()->route('profile.edit')
                ->with('estado', 'Por seguridad, cambia tu contraseña temporal antes de continuar.');
        }

        return $next($request);
    }
}
