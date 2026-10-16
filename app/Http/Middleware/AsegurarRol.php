<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite el paso solo a usuarios activos con alguno de los roles indicados.
 * Uso en rutas: ->middleware('rol:ADMIN,VENDEDOR'). Sin roles, exige ser personal.
 */
class AsegurarRol
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if($user === null || ! $user->activo, Response::HTTP_FORBIDDEN);

        $permitido = $roles === []
            ? $user->esPersonal()
            : $user->tieneRol(...array_map(fn (string $rol) => Rol::from($rol), $roles));

        abort_unless($permitido, Response::HTTP_FORBIDDEN, 'No tienes permiso para acceder a esta sección.');

        return $next($request);
    }
}
