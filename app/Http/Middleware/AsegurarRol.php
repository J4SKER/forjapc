<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite el paso solo a usuarios activos con alguno de los roles indicados.
 * Uso en rutas: ->middleware('rol:ADMIN,VENDEDOR'). Sin roles, exige ser personal.
 *
 * Se evalúa el rol efectivo, así la vista previa "Ver como" del administrador
 * muestra exactamente los accesos del rol elegido, y en ese modo el panel es de solo lectura.
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
            ? $user->rolEfectivo()->esPersonal()
            : $user->tieneRolEfectivo(...array_map(fn (string $rol) => Rol::from($rol), $roles));

        abort_unless($permitido, Response::HTTP_FORBIDDEN, 'No tienes permiso para acceder a esta sección.');

        if ($user->estaEnVistaPrevia() && ! $request->isMethodSafe() && ! $request->routeIs('admin.vista-previa.*')) {
            return back()->with('estado', 'Estás en vista previa: el panel es de solo lectura. Sal de la vista previa para hacer cambios.');
        }

        return $next($request);
    }
}
