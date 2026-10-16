<?php

namespace Tests\Feature\Auth;

use App\Models\BitacoraAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class BloqueoCuentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_cuenta_se_bloquea_despues_de_cinco_intentos_fallidos(): void
    {
        $user = User::factory()->create();

        for ($intentos = 0; $intentos < 5; $intentos++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta']);
        }

        $user->refresh();
        $this->assertTrue($user->estaBloqueadoTemporalmente());
        $this->assertDatabaseHas(BitacoraAuditoria::class, ['accion' => 'cuenta_bloqueada', 'entidad_id' => $user->id]);

        // Aunque se limpie el límite por IP, la cuenta sigue bloqueada con la contraseña correcta.
        RateLimiter::clear(strtolower($user->email).'|127.0.0.1');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => __('auth.cuenta_bloqueada', ['minutes' => 15])]);

        $this->assertGuest();
    }

    public function test_el_bloqueo_vence_y_el_ingreso_exitoso_reinicia_los_contadores(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['intentos_fallidos' => 3, 'bloqueado_hasta' => now()->subMinute()])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertSame(0, $user->intentos_fallidos);
        $this->assertNull($user->bloqueado_hasta);
        $this->assertNotNull($user->ultimo_acceso);
    }

    public function test_una_cuenta_inactiva_no_puede_ingresar(): void
    {
        $user = User::factory()->inactivo()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => __('auth.cuenta_inactiva')]);

        $this->assertGuest();
    }
}
