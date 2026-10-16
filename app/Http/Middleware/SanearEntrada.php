<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reemplaza secuencias UTF-8 inválidas en los datos enviados, para que un texto mal
 * codificado se valide como cualquier otro en lugar de provocar un error en MySQL.
 */
class SanearEntrada
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sanear = function (mixed $valor) use (&$sanear): mixed {
            if (is_array($valor)) {
                return array_map($sanear, $valor);
            }

            return is_string($valor) && ! mb_check_encoding($valor, 'UTF-8') ? mb_scrub($valor, 'UTF-8') : $valor;
        };

        $request->query->replace($sanear($request->query->all()));
        $request->request->replace($sanear($request->request->all()));

        return $next($request);
    }
}
