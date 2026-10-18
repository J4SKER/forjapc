<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acciones sensibles (usuarios, parámetros, reembolsos, contabilidad): exigen haber confirmado
 * la contraseña hace poco. A diferencia del middleware de Laravel, en un POST no se pierde el
 * formulario: al confirmar se vuelve a la página anterior para enviarlo de nuevo.
 */
class ConfirmarPassword
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $confirmada = (int) $request->session()->get('auth.password_confirmed_at', 0);

        if (time() - $confirmada < (int) config('auth.password_timeout')) {
            return $next($request);
        }

        $volverA = $request->isMethodSafe() ? $request->fullUrl() : url()->previous();
        $request->session()->put('url.intended', $volverA);

        return redirect()->route('password.confirm')->with('estado', $request->isMethodSafe()
            ? 'Por seguridad, confirma tu contraseña para continuar.'
            : 'Por seguridad, confirma tu contraseña. Después vuelve a enviar el formulario.');
    }
}
