<?php

namespace Tests\Feature\Admin;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccesoPorRolTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_visitante_es_enviado_al_inicio_de_sesion(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_un_cliente_no_puede_entrar_al_panel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_el_cliente_ve_su_cuenta_y_el_personal_es_enviado_al_panel(): void
    {
        $this->actingAs(User::factory()->create())->get('/mi-cuenta')->assertOk();

        $this->actingAs(User::factory()->conRol(Rol::Tecnico)->create())
            ->get('/mi-cuenta')
            ->assertRedirect(route('admin.inicio'));
    }

    #[DataProvider('rolesDePersonal')]
    public function test_el_personal_entra_al_panel_pero_solo_el_admin_administra_usuarios(Rol $rol, int $estadoUsuarios): void
    {
        $user = User::factory()->conRol($rol)->create();

        $this->actingAs($user)->get('/admin')->assertOk();
        $this->actingAs($user)->get('/admin/usuarios')->assertStatus($estadoUsuarios);
        $this->actingAs($user)->get('/admin/parametros')->assertStatus($estadoUsuarios);
        $this->actingAs($user)->get('/admin/bitacora')->assertStatus($estadoUsuarios);
    }

    /**
     * @return array<string, array{Rol, int}>
     */
    public static function rolesDePersonal(): array
    {
        return [
            'vendedor' => [Rol::Vendedor, 403],
            'técnico' => [Rol::Tecnico, 403],
            'contador' => [Rol::Contador, 403],
            'administrador' => [Rol::Admin, 200],
        ];
    }

    public function test_un_usuario_de_personal_desactivado_no_entra_al_panel(): void
    {
        $user = User::factory()->conRol(Rol::Admin)->inactivo()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
