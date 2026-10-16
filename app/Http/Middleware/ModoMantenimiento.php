<?php

namespace App\Http\Middleware;

use App\Models\ParametroSistema;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Modo de mantenimiento controlado desde Parámetros: la tienda muestra un aviso a los
 * clientes mientras el personal sigue entrando al panel y a la sesión.
 */
class ModoMantenimiento
{
    /**
     * Rutas que siguen disponibles durante el mantenimiento.
     *
     * @var list<string>
     */
    private const PERMITIDAS = ['login', 'logout', 'password.*', 'verification.*', 'admin.*', 'profile.*', 'webhooks.*'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (ParametroSistema::valor('modo_mantenimiento', false)
            && ! $request->user()?->esPersonal()
            && ! $request->routeIs(...self::PERMITIDAS)
            && ! $request->is('livewire*', 'up', 'build/*')) {
            return response()->view('errors.mantenimiento', [], 503);
        }

        return $next($request);
    }
}
