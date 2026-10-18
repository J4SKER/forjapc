<?php

namespace Tests\Feature\Admin;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Una prueba por cada defecto de la ronda 4 de QA (seguridad y panel).
 */
class RegresionQaRonda4Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->conRol(Rol::Admin)->create(['es_propietario' => true]);
    }

    public function test_def_035_solo_se_confia_en_el_dominio_de_la_tienda(): void
    {
        $this->assertStringContainsString('trustHosts()', file_get_contents(base_path('bootstrap/app.php')));
    }

    public function test_def_036_un_cliente_ascendido_a_personal_debe_cambiar_su_contrasena(): void
    {
        $cliente = User::factory()->create(['debe_cambiar_password' => false]);

        $this->actingAs($this->admin)->put("/admin/usuarios/{$cliente->id}", [
            ...$cliente->only(['nombre', 'email', 'telefono']), 'rol' => 'VENDEDOR', 'activo' => '1',
        ])->assertRedirect('/admin/usuarios');

        $this->assertTrue($cliente->refresh()->debe_cambiar_password);
        $this->actingAs($cliente)->get('/admin')->assertRedirect(route('profile.edit'));
    }

    public function test_def_037_confirmar_no_redirige_a_otro_dominio(): void
    {
        $this->sinConfirmarPassword()->actingAs($this->admin)
            ->withHeader('Referer', 'https://evil.example/robar')
            ->put('/admin/parametros', [])
            ->assertRedirect('/confirm-password');

        $this->assertSame(route('admin.inicio'), session('url.intended'));
    }

    public function test_def_038_la_confirmacion_tiene_limite_de_intentos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->admin)->post('/confirm-password', ['password' => 'incorrecta']);
        }

        $this->actingAs($this->admin)->post('/confirm-password', ['password' => 'incorrecta'])->assertStatus(429);
    }

    public function test_def_039_un_rol_en_arreglo_se_rechaza_sin_error_500(): void
    {
        $vendedor = User::factory()->conRol(Rol::Vendedor)->create();

        $this->actingAs($this->admin)->put("/admin/usuarios/{$vendedor->id}", [
            ...$vendedor->only(['nombre', 'email', 'telefono']), 'rol' => ['ADMIN'], 'activo' => '1',
        ])->assertSessionHasErrors('rol');
    }

    public function test_def_042_el_cambio_de_correo_no_da_la_bienvenida(): void
    {
        Notification::fake();
        $cliente = User::factory()->create(['created_at' => now()->subMonth()]);

        $this->actingAs($cliente)->patch('/mi-cuenta/perfil', ['nombre' => $cliente->nombre, 'nit' => 'CF', 'email' => 'nuevo@forjapc.test', 'password_actual' => 'password']);

        Notification::assertSentTo($cliente, VerifyEmail::class, fn (VerifyEmail $n) => $n->toMail($cliente)->subject === 'Confirma tu nuevo correo en ForjaPC');
    }
}
