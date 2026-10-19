<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión del personal tras un tiempo sin actividad (por defecto 30 minutos), para que
 * un panel abierto en una computadora sin atender no quede disponible.
 */
class CerrarSesionInactiva
{
    public const SESION = 'ultima_actividad_personal';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario?->esPersonal()) {
            return $next($request);
        }

        $ultima = (int) $request->session()->get(self::SESION, time());

        // El personal no puede reabrir una sesión vencida con «Recordarme»: debe volver a
        // escribir su contraseña (si no, la inactividad se saltaría esperando a que venza la sesión).
        if (Auth::guard('web')->viaRemember() || time() - $ultima > (int) config('forjapc.inactividad_personal') * 60) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Cerramos tu sesión por inactividad. Vuelve a iniciar sesión.');
        }

        $request->session()->put(self::SESION, time());

        return $next($request);
    }
}
