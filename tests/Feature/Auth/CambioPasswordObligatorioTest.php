<?php

namespace Tests\Feature\Auth;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CambioPasswordObligatorioTest extends TestCase
{
    use RefreshDatabase;

    private function conPasswordTemporal(): User
    {
        $user = User::factory()->conRol(Rol::Vendedor)->create();
        $user->forceFill(['debe_cambiar_password' => true])->save();

        return $user;
    }

    public function test_con_password_temporal_solo_puede_ir_a_su_perfil(): void
    {
        $user = $this->conPasswordTemporal();

        $this->actingAs($user)->get('/admin')->assertRedirect(route('profile.edit'));
        $this->actingAs($user)->get('/mi-cuenta/perfil')->assertOk()->assertSee('contraseña temporal');
    }

    public function test_al_cambiarla_se_libera_el_acceso(): void
    {
        $user = $this->conPasswordTemporal();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'NuevaClave2026',
            'password_confirmation' => 'NuevaClave2026',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($user->refresh()->debe_cambiar_password);
        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_la_nueva_password_debe_ser_distinta(): void
    {
        $user = $this->conPasswordTemporal();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->assertTrue($user->refresh()->debe_cambiar_password);
    }

    public function test_el_usuario_creado_por_el_admin_debe_cambiarla(): void
    {
        $admin = User::factory()->conRol(Rol::Admin)->create();

        $this->actingAs($admin)->post('/admin/usuarios', [
            'nombre' => 'Contadora',
            'email' => 'conta@forjapc.test',
            'rol' => Rol::Contador->value,
            'activo' => '1',
            'password' => 'Temporal2026',
            'password_confirmation' => 'Temporal2026',
        ]);

        $this->assertTrue(User::query()->where('email', 'conta@forjapc.test')->firstOrFail()->debe_cambiar_password);
    }
}
