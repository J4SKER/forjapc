<?php

namespace Tests\Feature\Admin;

use App\Enums\Rol;
use App\Models\User;
use App\Notifications\CuentaModificada;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Propietario, confirmación de contraseña, avisos de cambios y sesiones (revisión de seguridad).
 */
class SeguridadCuentasTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->propietario = User::factory()->conRol(Rol::Admin)->create(['es_propietario' => true]);
        $this->admin = User::factory()->conRol(Rol::Admin)->create();
    }

    private function datos(User $usuario, array $cambios = []): array
    {
        return [...$usuario->only(['nombre', 'email', 'telefono']), 'rol' => $usuario->rol->value, 'activo' => '1', ...$cambios];
    }

    public function test_un_admin_comun_no_puede_nombrar_administradores(): void
    {
        $vendedor = User::factory()->conRol(Rol::Vendedor)->create();

        $this->actingAs($this->admin)->put("/admin/usuarios/{$vendedor->id}", $this->datos($vendedor, ['rol' => 'ADMIN']))
            ->assertSessionHasErrors(['rol' => 'Solo el propietario puede asignar el rol de administrador.']);
        $this->actingAs($this->admin)->post('/admin/usuarios', ['nombre' => 'Nuevo', 'email' => 'nuevo@forjapc.test', 'rol' => 'ADMIN', 'activo' => '1', 'password' => 'Segura12345', 'password_confirmation' => 'Segura12345'])
            ->assertSessionHasErrors('rol');

        $this->assertSame(Rol::Vendedor, $vendedor->refresh()->rol);
        $this->assertDatabaseMissing('usuarios', ['email' => 'nuevo@forjapc.test']);
    }

    public function test_un_admin_comun_no_modifica_a_otro_admin_ni_al_propietario(): void
    {
        $otroAdmin = User::factory()->conRol(Rol::Admin)->create();

        $this->actingAs($this->admin)->get("/admin/usuarios/{$otroAdmin->id}/edit")->assertForbidden();
        $this->actingAs($this->admin)->put("/admin/usuarios/{$otroAdmin->id}", $this->datos($otroAdmin, ['activo' => '0']))->assertForbidden();
        $this->actingAs($this->admin)->put("/admin/usuarios/{$this->propietario->id}", $this->datos($this->propietario, ['rol' => 'VENDEDOR']))->assertForbidden();
        $this->actingAs($this->admin)->post("/admin/usuarios/{$this->propietario->id}/restablecer")->assertForbidden();

        $this->assertTrue($otroAdmin->refresh()->activo);
        $this->assertTrue($this->propietario->refresh()->esPropietario());
    }

    public function test_el_propietario_nombra_y_modifica_administradores(): void
    {
        $vendedor = User::factory()->conRol(Rol::Vendedor)->create();

        $this->actingAs($this->propietario)->put("/admin/usuarios/{$vendedor->id}", $this->datos($vendedor, ['rol' => 'ADMIN']))->assertRedirect('/admin/usuarios');
        $this->assertSame(Rol::Admin, $vendedor->refresh()->rol);

        $this->actingAs($this->propietario)->put("/admin/usuarios/{$this->admin->id}", $this->datos($this->admin, ['activo' => '0']))->assertRedirect('/admin/usuarios');
        $this->assertFalse($this->admin->refresh()->activo);
        $this->assertFalse($vendedor->refresh()->esPropietario());
    }

    public function test_nadie_cambia_la_contrasena_ajena_solo_se_envia_un_enlace(): void
    {
        $cliente = User::factory()->create();

        $this->actingAs($this->admin)->put("/admin/usuarios/{$cliente->id}", $this->datos($cliente, ['password' => 'Otra12345678', 'password_confirmation' => 'Otra12345678']))
            ->assertSessionHasErrors('password');

        $this->actingAs($this->admin)->post("/admin/usuarios/{$cliente->id}/restablecer")->assertRedirect();
        Notification::assertSentTo($cliente, ResetPassword::class);
        $this->assertDatabaseHas('bitacora_auditoria', ['accion' => 'restablecimiento_enviado', 'entidad_id' => $cliente->id]);
    }

    public function test_las_acciones_sensibles_piden_confirmar_la_contrasena(): void
    {
        $this->sinConfirmarPassword()->actingAs($this->admin)->get('/admin/usuarios')->assertRedirect('/confirm-password');

        $this->sinConfirmarPassword()->actingAs($this->admin)->from('/admin/parametros')->put('/admin/parametros', [])
            ->assertRedirect('/confirm-password');
        $this->assertSame(url('/admin/parametros'), session('url.intended'));

        $this->actingAs($this->admin)->post('/confirm-password', ['password' => 'password'])->assertRedirect('/admin/parametros');
        $this->actingAs($this->admin)->get('/admin/usuarios')->assertOk();
    }

    public function test_el_afectado_recibe_aviso_de_cambios_en_su_cuenta(): void
    {
        $vendedor = User::factory()->conRol(Rol::Vendedor)->create(['email' => 'antes@forjapc.test']);

        $this->actingAs($this->admin)->put("/admin/usuarios/{$vendedor->id}", $this->datos($vendedor, ['rol' => 'TECNICO', 'email' => 'despues@forjapc.test']));

        Notification::assertSentTo($vendedor, CuentaModificada::class, fn (CuentaModificada $n) => count($n->cambios) === 2);
        Notification::assertSentOnDemand(CuentaModificada::class, fn ($n, $canales, $destino) => $destino->routes['mail'] === 'antes@forjapc.test');
    }

    public function test_el_cliente_necesita_su_contrasena_para_cambiar_su_correo(): void
    {
        $cliente = User::factory()->create(['email' => 'mio@forjapc.test']);
        $datos = ['nombre' => $cliente->nombre, 'nit' => 'CF', 'email' => 'otro@forjapc.test'];

        $this->actingAs($cliente)->patch('/mi-cuenta/perfil', $datos)->assertSessionHasErrors('password_actual');
        $this->assertSame('mio@forjapc.test', $cliente->refresh()->email);

        $this->actingAs($cliente)->patch('/mi-cuenta/perfil', [...$datos, 'password_actual' => 'password'])->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(CuentaModificada::class, fn ($n, $canales, $destino) => $destino->routes['mail'] === 'mio@forjapc.test');
    }

    public function test_cambiar_la_contrasena_cierra_las_otras_sesiones(): void
    {
        $cliente = User::factory()->create();

        // Otra sesión abierta con el hash de una contraseña anterior queda cerrada.
        $this->actingAs($cliente)->withSession(['password_hash_web' => 'hash-de-otra-contrasena'])
            ->get('/mi-cuenta')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_el_personal_necesita_una_contrasena_robusta(): void
    {
        $base = ['nombre' => 'Nueva Vendedora', 'email' => 'vendedora@forjapc.test', 'rol' => 'VENDEDOR', 'activo' => '1'];

        $this->actingAs($this->admin)->post('/admin/usuarios', [...$base, 'password' => 'password1', 'password_confirmation' => 'password1'])->assertSessionHasErrors('password');
        $this->actingAs($this->admin)->post('/admin/usuarios', [...$base, 'password' => 'Segura12345', 'password_confirmation' => 'Segura12345'])->assertSessionHasNoErrors();
    }

    public function test_el_propietario_solo_se_designa_por_consola(): void
    {
        $this->artisan('forjapc:crear-admin', ['email' => 'dueno@forjapc.test', '--propietario' => true])->assertSuccessful();
        $this->assertTrue(User::query()->where('email', 'dueno@forjapc.test')->firstOrFail()->esPropietario());

        $this->artisan('forjapc:crear-admin', ['email' => $this->admin->email, '--propietario' => true])->assertSuccessful();
        $this->assertTrue($this->admin->refresh()->esPropietario());

        $vendedor = User::factory()->conRol(Rol::Vendedor)->create();
        $this->artisan('forjapc:crear-admin', ['email' => $vendedor->email, '--propietario' => true])->assertFailed();
    }
}
