<?php

namespace Tests\Feature\Tienda;

use App\Livewire\Tienda\ConfiguradorPc;
use App\Models\Configuracion;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConfiguradorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogoSeeder::class);
    }

    private function id(string $slug): int
    {
        return (int) Producto::query()->where('slug', $slug)->value('id');
    }

    public function test_la_pagina_del_configurador_carga(): void
    {
        $this->get('/arma-tu-pc')->assertOk()->assertSeeLivewire(ConfiguradorPc::class)->assertSee('Elige procesador');
    }

    public function test_al_elegir_el_procesador_solo_muestra_placas_compatibles(): void
    {
        Livewire::test(ConfiguradorPc::class)
            ->call('elegir', $this->id('amd-ryzen-5-7600'))
            ->assertSet('paso', 'placa')
            ->assertSee('MSI B650 GAMING PLUS WIFI')
            ->assertDontSee('MSI PRO B760M-A WIFI DDR4')
            ->assertSee('Ocultamos');
    }

    public function test_no_permite_elegir_una_pieza_incompatible_ni_sin_existencia(): void
    {
        Livewire::test(ConfiguradorPc::class)
            ->call('elegir', $this->id('msi-b650-gaming-plus-wifi'))
            ->call('elegir', $this->id('intel-core-i5-14400f'))
            ->assertSet('seleccion.cpu', null)
            ->call('elegir', $this->id('amd-ryzen-7-7800x3d'))
            ->assertSet('seleccion.cpu', null);
    }

    public function test_muestra_errores_de_compatibilidad_y_el_total_con_armado(): void
    {
        Livewire::test(ConfiguradorPc::class)
            ->call('elegir', $this->id('intel-core-i5-14400f'))
            ->assertSee('Falta tarjeta de video')
            ->assertSee('Q1,940.00'); // 1,690 + 250 de armado
    }

    public function test_guardar_requiere_iniciar_sesion(): void
    {
        Livewire::test(ConfiguradorPc::class)
            ->call('elegir', $this->id('amd-ryzen-5-7600'))
            ->call('guardar')
            ->assertRedirect(route('login'));
    }

    public function test_guarda_y_comparte_una_configuracion(): void
    {
        $cliente = User::factory()->create();

        Livewire::actingAs($cliente)->test(ConfiguradorPc::class)
            ->call('elegir', $this->id('amd-ryzen-5-7600'))
            ->call('elegir', $this->id('msi-b650-gaming-plus-wifi'))
            ->set('nombre', 'PC gamer de Ana')
            ->call('guardar')
            ->assertHasNoErrors();

        $configuracion = Configuracion::query()->with('componentes')->firstOrFail();
        $this->assertSame('PC gamer de Ana', $configuracion->nombre);
        $this->assertCount(2, $configuracion->componentes);

        // Otro usuario abre el enlace y obtiene una copia, no la original.
        Livewire::actingAs(User::factory()->create())
            ->test(ConfiguradorPc::class, ['configuracion' => $configuracion])
            ->assertSet('codigoGuardado', null)
            ->assertSet('nombre', 'PC gamer de Ana (copia)')
            ->assertSee('AMD Ryzen 5 7600');

        $this->actingAs($cliente)->get('/mi-cuenta/configuraciones')->assertOk()->assertSee('PC gamer de Ana');
        $this->get("/arma-tu-pc/c/{$configuracion->codigo}")->assertOk();
    }

    public function test_solo_el_dueno_elimina_su_configuracion(): void
    {
        $duena = User::factory()->create();
        $configuracion = new Configuracion(['nombre' => 'Mía', 'incluye_armado' => true]);
        $configuracion->usuario_id = $duena->id;
        $configuracion->save();

        $this->actingAs(User::factory()->create())->delete("/mi-cuenta/configuraciones/{$configuracion->codigo}")->assertForbidden();
        $this->actingAs($duena)->delete("/mi-cuenta/configuraciones/{$configuracion->codigo}")->assertRedirect();
        $this->assertModelMissing($configuracion);
    }
}
