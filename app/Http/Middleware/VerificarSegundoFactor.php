<?php

namespace App\Http\Middleware;

use App\Services\SegundoFactorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El personal no puede usar nada (salvo verificar el código o cerrar sesión) hasta superar
 * el segundo factor en esta sesión o desde un equipo recordado.
 */
class VerificarSegundoFactor
{
    public function __construct(private SegundoFactorService $segundoFactor) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $this->segundoFactor->requerido($usuario)
            && ! $request->routeIs('dos-pasos.*', 'logout')
            && ! $this->segundoFactor->verificado($request, $usuario)) {
            if (! $request->isMethodSafe() || $request->expectsJson()) {
                abort(403, 'Verifica tu acceso con el código enviado a tu correo.');
            }

            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('dos-pasos.show');
        }

        return $next($request);
    }
}
