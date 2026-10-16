<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\Direccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DireccionTest extends TestCase
{
    use RefreshDatabase;

    private Departamento $departamento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->departamento = Departamento::create(['nombre' => 'Guatemala', 'tarifa_envio' => 25]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(array $cambios = []): array
    {
        return [
            'alias' => 'Casa',
            'nombre_receptor' => 'Ana López',
            'telefono' => '5555 1234',
            'departamento_id' => $this->departamento->id,
            'municipio' => 'Mixco',
            'direccion' => '5a avenida 10-20 zona 1',
            'es_principal' => '0',
            ...$cambios,
        ];
    }

    public function test_la_primera_direccion_queda_como_principal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/mi-cuenta/direcciones', $this->datos())
            ->assertRedirect(route('direcciones.index'));

        $this->assertTrue($user->direcciones()->sole()->es_principal);
    }

    public function test_solo_hay_una_direccion_principal(): void
    {
        $user = User::factory()->create();
        $primera = Direccion::factory()->for($user, 'usuario')->create(['es_principal' => true, 'departamento_id' => $this->departamento->id]);

        $this->actingAs($user)->post('/mi-cuenta/direcciones', $this->datos(['alias' => 'Trabajo', 'es_principal' => '1']));

        $this->assertFalse($primera->refresh()->es_principal);
        $this->assertSame(1, $user->direcciones()->where('es_principal', true)->count());
    }

    public function test_un_cliente_no_puede_editar_ni_borrar_direcciones_ajenas(): void
    {
        $ajena = Direccion::factory()->create(['departamento_id' => $this->departamento->id]);
        $user = User::factory()->create();

        $this->actingAs($user)->get("/mi-cuenta/direcciones/{$ajena->id}/edit")->assertForbidden();
        $this->actingAs($user)->put("/mi-cuenta/direcciones/{$ajena->id}", $this->datos())->assertForbidden();
        $this->actingAs($user)->delete("/mi-cuenta/direcciones/{$ajena->id}")->assertForbidden();

        $this->assertModelExists($ajena);
    }

    public function test_un_usuario_sin_correo_verificado_no_administra_direcciones(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get('/mi-cuenta/direcciones')
            ->assertRedirect(route('verification.notice'));
    }
}
