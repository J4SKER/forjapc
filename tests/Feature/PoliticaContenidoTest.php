<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Política de contenido estricta: solo se ejecutan los scripts y estilos con el nonce de la
 * respuesta, y las vistas no tienen JavaScript ni estilos en línea que la política bloquearía.
 */
class PoliticaContenidoTest extends TestCase
{
    use RefreshDatabase;

    private function directiva(string $csp, string $nombre): string
    {
        preg_match('/(?:^|;\s*)'.preg_quote($nombre, '/').'\s([^;]*)/', $csp, $m);

        return $m[1] ?? '';
    }

    public function test_scripts_y_estilos_solo_con_nonce_y_sin_eval(): void
    {
        $csp = $this->get('/')->assertOk()->headers->get('Content-Security-Policy');

        $scripts = $this->directiva($csp, 'script-src');
        $estilos = $this->directiva($csp, 'style-src');

        $this->assertMatchesRegularExpression("/'nonce-[A-Za-z0-9+\/=]{16,}'/", $scripts);
        $this->assertStringContainsString("'strict-dynamic'", $scripts);
        $this->assertStringNotContainsString('unsafe-inline', $scripts);
        $this->assertStringNotContainsString('unsafe-eval', $scripts);
        $this->assertStringNotContainsString('unsafe-inline', $estilos);
        $this->assertStringContainsString("'nonce-", $estilos);
    }

    public function test_cada_script_y_estilo_de_la_pagina_lleva_el_nonce_de_la_respuesta(): void
    {
        $producto = Producto::factory()->create();

        foreach (['/', "/producto/{$producto->slug}", '/arma-tu-pc', '/login'] as $ruta) {
            $respuesta = $this->get($ruta)->assertOk();
            preg_match("/'nonce-([^']+)'/", $respuesta->headers->get('Content-Security-Policy'), $m);
            $html = $respuesta->getContent();
            $inicio ??= $html;

            preg_match_all('/<(script|style)\b[^>]*>/i', $html, $etiquetas);
            $this->assertNotEmpty($etiquetas[0], $ruta);
            foreach ($etiquetas[0] as $etiqueta) {
                if (str_contains($etiqueta, 'type="application/ld+json"')) {
                    continue;
                }
                $this->assertStringContainsString('nonce="'.$m[1].'"', $etiqueta, "{$ruta}: {$etiqueta}");
            }
        }

        // Livewire sirve su versión CSP de Alpine, que evalúa las expresiones sin eval().
        preg_match('#src="[^"]*(/livewire-[^"/]+/livewire[^"]*\.js[^"]*)"#', $inicio, $script);
        $this->assertNotEmpty($script, 'No se encontró el script de Livewire');
        $this->assertStringContainsString('CSP Parser Error', $this->get(html_entity_decode($script[1]))->assertOk()->streamedContent());
    }

    public function test_el_nonce_cambia_en_cada_respuesta(): void
    {
        $uno = $this->get('/')->headers->get('Content-Security-Policy');
        $dos = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertNotSame($uno, $dos);
    }

    public function test_las_vistas_no_tienen_javascript_ni_estilos_en_linea(): void
    {
        $prohibido = [
            'manejador on*=""' => '/<[a-z][^>]*\son[a-z]+\s*=\s*["\']/i',
            '<script> sin src' => '/<script(?![^>]*\bsrc=)(?![^>]*type="application\/(ld\+)?json")[^>]*>/i',
            'enlace javascript:' => '/href\s*=\s*["\']\s*javascript:/i',
            'atributo style=""' => '/<[a-z][^>]*\sstyle\s*=\s*["\']/i',
        ];

        foreach (File::allFiles(resource_path('views')) as $archivo) {
            if (str_starts_with($archivo->getRelativePath(), 'vendor')) {
                continue;
            }
            $contenido = $archivo->getContents();
            foreach ($prohibido as $nombre => $patron) {
                $this->assertDoesNotMatchRegularExpression($patron, $contenido, "{$archivo->getRelativePathname()}: {$nombre}");
            }
        }
    }
}
