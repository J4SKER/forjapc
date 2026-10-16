<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\ParametroSistema;
use App\Models\User;
use Database\Seeders\ParametroSistemaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_respuestas_llevan_cabeceras_de_seguridad(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_el_modo_mantenimiento_bloquea_la_tienda_pero_no_al_personal(): void
    {
        $this->seed(ParametroSistemaSeeder::class);
        ParametroSistema::query()->where('clave', 'modo_mantenimiento')->first()->update(['valor' => '1']);

        $this->get('/')->assertStatus(503)->assertSee('Tienda en mantenimiento');
        $this->get('/login')->assertOk();
        $this->actingAs(User::factory()->conRol(Rol::Admin)->create())->get('/')->assertOk();
        $this->actingAs(User::factory()->create())->get('/carrito')->assertStatus(503);
    }

    public function test_el_registro_tiene_limite_de_intentos(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/register', ['nombre' => 'X', 'email' => "x{$i}@", 'password' => 'a', 'password_confirmation' => 'b']);
        }

        $this->post('/register', [])->assertStatus(429);
    }

    public function test_las_paginas_de_error_estan_en_espanol(): void
    {
        $this->get('/no-existe-esta-pagina')->assertNotFound()->assertSee('Página no encontrada');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden()->assertSee('Acceso denegado');
    }

    public function test_los_comprobantes_no_son_publicos(): void
    {
        // El disco privado solo sirve archivos con URL firmada: sin firma responde 403 o 404.
        $this->assertContains($this->get('/storage/comprobantes/cualquiera.jpg')->getStatusCode(), [403, 404]);
        $this->get('/pagos/1/comprobante')->assertRedirect('/login');
    }
}
