<?php

namespace Tests\Feature\Admin;

use App\Enums\EstadoCompra;
use App\Enums\Rol;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendedor = User::factory()->conRol(Rol::Vendedor)->create();
    }

    public function test_registra_y_recibe_una_compra_desde_el_panel(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Proveedor', 'nit' => '7654321-0']);
        $a = Producto::factory()->create();
        $b = Producto::factory()->create();

        $this->actingAs($this->vendedor)->post('/admin/compras', [
            'proveedor_id' => $proveedor->id,
            'numero_factura' => '555',
            'fecha_factura' => today()->toDateString(),
            'lineas' => [
                ['producto_id' => $a->id, 'cantidad' => 3, 'costo_unitario' => '100'],
                ['producto_id' => '', 'cantidad' => '', 'costo_unitario' => ''], // fila vacía se ignora
                ['producto_id' => $b->id, 'cantidad' => 2, 'costo_unitario' => '50'],
            ],
        ])->assertRedirect();

        $compra = Compra::query()->firstOrFail();
        $this->assertCount(2, $compra->detalle);
        $this->assertEquals(400.00, (float) $compra->subtotal);

        $this->actingAs($this->vendedor)->post("/admin/compras/{$compra->id}/recibir")->assertRedirect();

        $this->assertSame(EstadoCompra::Recibida, $compra->refresh()->estado);
        $this->assertSame(3, $a->refresh()->existencia);
        $this->assertSame(2, $b->refresh()->existencia);
    }

    public function test_no_acepta_productos_repetidos_en_la_compra(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Proveedor', 'nit' => '7654321-0']);
        $producto = Producto::factory()->create();

        $this->actingAs($this->vendedor)->post('/admin/compras', [
            'proveedor_id' => $proveedor->id,
            'numero_factura' => '556',
            'fecha_factura' => today()->toDateString(),
            'lineas' => [
                ['producto_id' => $producto->id, 'cantidad' => 1, 'costo_unitario' => '10'],
                ['producto_id' => $producto->id, 'cantidad' => 2, 'costo_unitario' => '10'],
            ],
        ])->assertSessionHasErrors('lineas.1.producto_id');
    }

    public function test_el_ajuste_de_merma_no_puede_dejar_existencia_negativa(): void
    {
        $producto = Producto::factory()->create();

        $this->actingAs($this->vendedor)->from("/admin/inventario/{$producto->id}/kardex")
            ->post("/admin/inventario/{$producto->id}/ajuste", ['tipo' => 'merma', 'cantidad' => 1, 'motivo' => 'Producto dañado'])
            ->assertSessionHasErrors('cantidad');

        $this->actingAs($this->vendedor)
            ->post("/admin/inventario/{$producto->id}/ajuste", ['tipo' => 'sobrante', 'cantidad' => 2, 'motivo' => 'Conteo físico'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $producto->refresh()->existencia);
    }

    public function test_el_contador_consulta_el_inventario_pero_no_ajusta(): void
    {
        $contador = User::factory()->conRol(Rol::Contador)->create();
        $producto = Producto::factory()->create();

        $this->actingAs($contador)->get('/admin/inventario')->assertOk();
        $this->actingAs($contador)->get("/admin/inventario/{$producto->id}/kardex")->assertOk()->assertDontSee('Registrar ajuste');
        $this->actingAs($contador)->post("/admin/inventario/{$producto->id}/ajuste", ['tipo' => 'sobrante', 'cantidad' => 1, 'motivo' => 'Intento'])
            ->assertForbidden();
    }
}
