<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad para todas las respuestas (OWASP: clickjacking, MIME sniffing,
 * fuga de referencias, permisos del navegador y HTTPS estricto en producción).
 */
class CabecerasSeguridad
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self "https://www.paypal.com")');
        $respuesta->headers->remove('X-Powered-By');

        if (! $respuesta->headers->has('Content-Security-Policy') && ! is_file(public_path('hot'))) {
            $respuesta->headers->set('Content-Security-Policy', $this->politicaDeContenido($request));
        }

        if ($request->isSecure()) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $respuesta;
    }

    /**
     * Solo se cargan recursos del propio sitio, de Bunny Fonts y de PayPal (botones, ventana
     * de pago y campos de tarjeta). Un script inyectado no puede traer código de otro dominio
     * ni enviar datos fuera. 'unsafe-inline' y 'unsafe-eval' siguen permitidos porque Alpine,
     * Livewire y el SDK de PayPal los necesitan. No se aplica con el servidor de Vite en desarrollo.
     */
    private function politicaDeContenido(Request $request): string
    {
        $paypal = 'https://*.paypal.com https://*.paypalobjects.com';

        $directivas = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$paypal}",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' data: https://fonts.bunny.net",
            "img-src 'self' data: blob: {$paypal}",
            "connect-src 'self' {$paypal}",
            "frame-src {$paypal}",
            "form-action 'self' {$paypal}",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        if ($request->isSecure()) {
            $directivas[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directivas);
    }
}
