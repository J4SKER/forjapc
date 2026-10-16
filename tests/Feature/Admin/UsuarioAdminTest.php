<?php

namespace Tests\Feature\Admin;

use App\Enums\Rol;
use App\Models\BitacoraAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->conRol(Rol::Admin)->create();
    }

    public function test_el_admin_crea_un_usuario_interno_verificado(): void
    {
        $this->actingAs($this->admin)->post('/admin/usuarios', [
            'nombre' => 'Técnico Nuevo',
            'email' => 'tecnico@forjapc.test',
            'rol' => Rol::Tecnico->value,
            'activo' => '1',
            'password' => 'ClaveSegura123',
            'password_confirmation' => 'ClaveSegura123',
        ])->assertRedirect(route('admin.usuarios.index'));

        $nuevo = User::query()->where('email', 'tecnico@forjapc.test')->firstOrFail();
        $this->assertSame(Rol::Tecnico, $nuevo->rol);
        $this->assertNotNull($nuevo->email_verified_at);
        $this->assertDatabaseHas(BitacoraAuditoria::class, ['accion' => 'usuario_creado', 'entidad_id' => $nuevo->id, 'usuario_id' => $this->admin->id]);
    }

    public function test_cambiar_el_rol_queda_en_la_bitacora(): void
    {
        $cliente = User::factory()->create();

        $this->actingAs($this->admin)->put("/admin/usuarios/{$cliente->id}", [
            'nombre' => $cliente->nombre,
            'email' => $cliente->email,
            'rol' => Rol::Vendedor->value,
            'activo' => '1',
        ])->assertRedirect(route('admin.usuarios.index'));

        $this->assertSame(Rol::Vendedor, $cliente->refresh()->rol);

        $registro = BitacoraAuditoria::query()->where('accion', 'rol_cambiado')->firstOrFail();
        $this->assertSame(['rol' => 'CLIENTE'], $registro->datos_anteriores);
        $this->assertSame(['rol' => 'VENDEDOR'], $registro->datos_nuevos);
    }

    public function test_el_rol_no_se_puede_asignar_desde_el_registro_publico(): void
    {
        $this->post('/register', [
            'nombre' => 'Intruso',
            'email' => 'intruso@example.com',
            'rol' => Rol::Admin->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertSame(Rol::Cliente, User::query()->where('email', 'intruso@example.com')->firstOrFail()->rol);
    }

    public function test_el_admin_no_puede_quitarse_su_propio_rol(): void
    {
        $this->actingAs($this->admin)->put("/admin/usuarios/{$this->admin->id}", [
            'nombre' => $this->admin->nombre,
            'email' => $this->admin->email,
            'rol' => Rol::Cliente->value,
            'activo' => '1',
        ])->assertSessionHasErrors('rol');

        $this->assertSame(Rol::Admin, $this->admin->refresh()->rol);
    }

    public function test_el_admin_desbloquea_una_cuenta(): void
    {
        $bloqueado = User::factory()->create();
        $bloqueado->forceFill(['bloqueado_hasta' => now()->addMinutes(10), 'intentos_fallidos' => 4])->save();

        $this->actingAs($this->admin)->post("/admin/usuarios/{$bloqueado->id}/desbloquear")->assertRedirect();

        $this->assertFalse($bloqueado->refresh()->estaBloqueadoTemporalmente());
        $this->assertDatabaseHas(BitacoraAuditoria::class, ['accion' => 'cuenta_desbloqueada', 'entidad_id' => $bloqueado->id]);
    }

    public function test_la_bitacora_no_se_puede_modificar(): void
    {
        $registro = BitacoraAuditoria::create(['accion' => 'prueba']);

        $this->expectException(\LogicException::class);

        $registro->update(['accion' => 'alterada']);
    }
}
