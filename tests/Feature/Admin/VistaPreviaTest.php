<?php

namespace Tests\Feature\Admin;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VistaPreviaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->conRol(Rol::Admin)->create();
    }

    public function test_el_admin_ve_el_panel_como_tecnico(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/vista-previa', ['rol' => Rol::Tecnico->value])
            ->assertRedirect(route('admin.inicio'));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Estás viendo el panel como')
            ->assertSee('Órdenes de armado')
            ->assertDontSee('Bitácora');

        $this->get('/admin/usuarios')->assertForbidden();
    }

    public function test_la_vista_previa_es_de_solo_lectura(): void
    {
        $cliente = User::factory()->create();

        $this->actingAs($this->admin)->post('/admin/vista-previa', ['rol' => Rol::Vendedor->value]);

        $this->from('/admin')->put("/admin/usuarios/{$cliente->id}", [
            'nombre' => 'Cambio',
            'email' => $cliente->email,
            'rol' => Rol::Cliente->value,
            'activo' => '1',
        ])->assertRedirect('/admin')->assertSessionHas('estado');

        $this->from('/admin')->post('/admin/parametros/tipo-cambio', ['fecha' => today()->toDateString(), 'tasa' => 7.7])
            ->assertRedirect('/admin');

        $this->assertNotSame('Cambio', $cliente->refresh()->nombre);
        $this->assertDatabaseMissing('tipos_cambio', ['tasa' => 7.7]);
    }

    public function test_al_salir_recupera_los_accesos_de_administrador(): void
    {
        $this->actingAs($this->admin)->post('/admin/vista-previa', ['rol' => Rol::Contador->value]);

        $this->delete('/admin/vista-previa')->assertRedirect(route('admin.inicio'));

        $this->get('/admin/usuarios')->assertOk();
    }

    public function test_solo_un_admin_real_puede_usar_la_vista_previa(): void
    {
        $vendedor = User::factory()->conRol(Rol::Vendedor)->create();

        $this->actingAs($vendedor)->post('/admin/vista-previa', ['rol' => Rol::Contador->value])->assertForbidden();

        $this->actingAs($vendedor)->withSession([User::SESION_VISTA_PREVIA => Rol::Admin->value])
            ->get('/admin/usuarios')
            ->assertForbidden();
    }

    public function test_no_se_puede_previsualizar_como_cliente_ni_admin(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/vista-previa', ['rol' => Rol::Cliente->value])
            ->assertSessionHasErrors('rol');
    }
}
