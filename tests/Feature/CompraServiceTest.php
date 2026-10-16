<?php

namespace Tests\Feature;

use App\Enums\EstadoCompra;
use App\Enums\TipoMovimiento;
use App\Events\CompraRecibida;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\CompraService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CompraServiceTest extends TestCase
{
    use RefreshDatabase;

    private function borrador(Producto $producto, int $cantidad = 4, float $costo = 250): Compra
    {
        $proveedor = Proveedor::create(['nombre' => 'Proveedor de prueba', 'nit' => '1234567-8']);

        return app(CompraService::class)->guardarBorrador([
            'proveedor_id' => $proveedor->id,
            'numero_factura' => 'F-'.fake()->unique()->numerify('####'),
            'fecha_factura' => today()->toDateString(),
        ], [['producto_id' => $producto->id, 'cantidad' => $cantidad, 'costo_unitario' => $costo]]);
    }

    public function test_el_borrador_calcula_subtotal_iva_y_total(): void
    {
        $compra = $this->borrador(Producto::factory()->create(), 4, 250);

        $this->assertSame(EstadoCompra::Borrador, $compra->estado);
        $this->assertEquals(1000.00, (float) $compra->subtotal);
        $this->assertEquals(120.00, (float) $compra->iva);
        $this->assertEquals(1120.00, (float) $compra->total);
    }

    public function test_al_recibir_entra_al_kardex_una_sola_vez(): void
    {
        Event::fake([CompraRecibida::class]);
        $producto = Producto::factory()->create();
        $compra = $this->borrador($producto, 4, 250);

        app(CompraService::class)->recibir($compra);

        $producto->refresh();
        $this->assertSame(4, $producto->existencia);
        $this->assertEquals(250.0, (float) $producto->costo_promedio);
        $this->assertSame(TipoMovimiento::EntradaCompra, $producto->movimientos()->first()->tipo);
        $this->assertSame(EstadoCompra::Recibida, $compra->refresh()->estado);
        Event::assertDispatched(CompraRecibida::class);

        $this->expectException(DomainException::class);
        app(CompraService::class)->recibir($compra);
    }

    public function test_solo_se_anula_un_borrador(): void
    {
        $compra = $this->borrador(Producto::factory()->create());
        app(CompraService::class)->recibir($compra);

        $this->expectException(DomainException::class);
        app(CompraService::class)->anular($compra->refresh());
    }
}
