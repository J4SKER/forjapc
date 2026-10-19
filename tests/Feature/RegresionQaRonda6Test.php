<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Una prueba por cada defecto y observación de la ronda 6 de QA (política de contenido estricta).
 */
class RegresionQaRonda6Test extends TestCase
{
    use RefreshDatabase;

    public function test_def_051_llamada_livewire_manipulada_es_422_sin_registrar_error(): void
    {
        preg_match_all('/wire:snapshot="([^"]+)"/', $this->get('/arma-tu-pc')->assertOk()->getContent(), $m);
        $snapshot = collect($m[1])->map(fn ($s) => html_entity_decode($s))
            ->first(fn ($s) => str_contains(json_decode($s, true)['memo']['name'], 'configurador'));
        $llamar = fn (array $params, string $metodo = 'elegir') => $this->withHeader('X-Livewire', '1')->postJson(app('livewire')->getUpdateUri(), [
            'components' => [['snapshot' => $snapshot, 'updates' => new \stdClass, 'calls' => [['path' => '', 'method' => $metodo, 'params' => $params]]]],
        ]);

        Log::spy();
        $llamar(['abc'])->assertStatus(422);
        $llamar([], 'noExiste')->assertStatus(422);
        Log::shouldNotHaveReceived('error');

        // Con el tipo correcto la acción responde normalmente.
        $llamar([999999])->assertOk();
    }

    public function test_regresar_de_la_pagina_de_error_no_lleva_a_otro_sitio(): void
    {
        $this->withHeader('referer', 'https://otro-sitio.example/trampa')->get('/no-existe')
            ->assertNotFound()->assertDontSee('otro-sitio.example');

        $this->withHeader('referer', url('/').'.evil.example/x')->get('/no-existe')
            ->assertNotFound()->assertDontSee('evil.example');

        $this->withHeader('referer', url('/carrito'))->get('/no-existe')
            ->assertNotFound()->assertSee('href="'.url('/carrito').'"', false);
    }
}
