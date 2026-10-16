<?php

namespace Tests\Feature;

use App\Enums\TipoMovimiento;
use App\Exceptions\InventarioInsuficienteException;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class InventarioServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventarioService $inventario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventario = app(InventarioService::class);
    }

    public function test_la_entrada_recalcula_el_costo_promedio_ponderado(): void
    {
        $producto = Producto::factory()->create();

        $this->inventario->entrada($producto, 10, 100, TipoMovimiento::EntradaCompra);
        $this->inventario->entrada($producto, 30, 140, TipoMovimiento::EntradaCompra);

        // (10 × 100 + 30 × 140) / 40 = 130
        $producto->refresh();
        $this->assertSame(40, $producto->existencia);
        $this->assertEquals(130.0, (float) $producto->costo_promedio);
        $this->assertSame(2, $producto->movimientos()->count());
    }

    public function test_la_salida_usa_el_costo_promedio_y_no_permite_vender_lo_que_no_hay(): void
    {
        $producto = Producto::factory()->create();
        $this->inventario->entrada($producto, 5, 200, TipoMovimiento::EntradaCompra);

        $movimiento = $this->inventario->salida($producto, 2, TipoMovimiento::SalidaVenta);
        $this->assertEquals(200.0, (float) $movimiento->costo_unitario);
        $this->assertSame(3, $producto->refresh()->existencia);

        $this->expectException(InventarioInsuficienteException::class);
        $this->inventario->salida($producto, 4, TipoMovimiento::SalidaVenta);
    }

    public function test_las_reservas_reducen_lo_disponible_y_la_venta_las_consume(): void
    {
        $producto = Producto::factory()->create();
        $this->inventario->entrada($producto, 3, 100, TipoMovimiento::EntradaCompra);

        $this->inventario->reservar($producto, 2);
        $this->assertSame(1, $producto->refresh()->disponible());

        try {
            $this->inventario->reservar($producto, 2);
            $this->fail('Se reservaron más unidades de las disponibles.');
        } catch (InventarioInsuficienteException) {
            $this->assertSame(2, $producto->refresh()->reservado);
        }

        $this->inventario->salida($producto, 2, TipoMovimiento::SalidaVenta, desdeReserva: true);
        $producto->refresh();
        $this->assertSame(1, $producto->existencia);
        $this->assertSame(0, $producto->reservado);

        $this->inventario->reservar($producto, 1);
        $this->inventario->liberarReserva($producto, 1);
        $this->assertSame(0, $producto->refresh()->reservado);
    }

    public function test_el_ajuste_exige_motivo(): void
    {
        $producto = Producto::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->inventario->ajustar($producto, 2, '  ');
    }

    public function test_la_existencia_no_se_puede_cambiar_fuera_del_kardex(): void
    {
        $producto = Producto::factory()->create();

        $this->expectException(LogicException::class);
        $producto->forceFill(['existencia' => 99])->save();
    }

    public function test_los_movimientos_del_kardex_son_inmutables(): void
    {
        $producto = Producto::factory()->create();
        $movimiento = $this->inventario->entrada($producto, 1, 50, TipoMovimiento::EntradaCompra);

        $this->expectException(LogicException::class);
        MovimientoInventario::find($movimiento->id)->delete();
    }
}
