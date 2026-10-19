<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
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
        // Número único por respuesta: solo los <script> y <style> que lo llevan se ejecutan.
        Vite::useCspNonce();

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
     * Política estricta: el navegador solo ejecuta los scripts y estilos que llevan el nonce de
     * esta respuesta (Vite, Livewire y el SDK de PayPal) y lo que ellos carguen ('strict-dynamic').
     * Un <script>, un <style> o un atributo on*="" inyectado en la página no se ejecuta, y no hay
     * 'unsafe-eval': Livewire usa su versión CSP de Alpine, que evalúa las expresiones de las
     * vistas sin eval() y sin acceso a variables globales. 'self' y https: solo los usan los
     * navegadores antiguos que no entienden 'strict-dynamic'. No se aplica con el servidor de
     * Vite en desarrollo.
     */
    private function politicaDeContenido(Request $request): string
    {
        $nonce = "'nonce-".Vite::cspNonce()."'";
        $paypal = 'https://*.paypal.com https://*.paypalobjects.com';

        $directivas = [
            "default-src 'self'",
            "script-src {$nonce} 'strict-dynamic' 'self' https:",
            "style-src 'self' {$nonce} https://fonts.bunny.net",
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
