<?php

namespace Tests\Feature\Ventas;

use App\Enums\Rol;
use App\Models\Partida;
use App\Models\PeriodoContable;
use App\Models\User;
use App\Services\CompraService;
use App\Services\ContabilidadService;
use App\Services\PagoService;
use App\Services\ReporteService;
use Carbon\CarbonImmutable;
use Database\Seeders\NomenclaturaSeeder as N;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContabilidadTest extends TestCase
{
    use AyudasVentas, RefreshDatabase;

    private User $contador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararTienda();
        $this->contador = User::factory()->conRol(Rol::Contador)->create();
    }

    /**
     * @return array<string, array{debe: float, haber: float}>
     */
    private function lineas(Partida $partida): array
    {
        return $partida->detalle()->with('cuenta')->get()
            ->mapWithKeys(fn ($d) => [$d->cuenta->codigo => ['debe' => (float) $d->debe, 'haber' => (float) $d->haber]])
            ->all();
    }

    public function test_la_compra_inicial_del_catalogo_genero_su_partida(): void
    {
        $partida = Partida::query()->where('origen_tipo', 'Compra')->sole();
        $lineas = $this->lineas($partida);

        $this->assertEquals($partida->total_debe, $partida->total_haber);
        $this->assertGreaterThan(0, $lineas[N::INVENTARIO]['debe']);
        $this->assertEqualsWithDelta($lineas[N::INVENTARIO]['debe'] * 0.12, $lineas[N::IVA_CREDITO]['debe'], 0.05);
    }

    public function test_la_venta_registra_ingresos_iva_costo_y_comision(): void
    {
        $pedido = $this->pedidoConPcArmada(); // incluye Q250 de armado
        $pago = $this->pagoTransferencia($pedido);
        app(PagoService::class)->aprobar($pago);

        $partida = Partida::query()->where('origen_tipo', 'Pago')->where('origen_id', $pago->id)->sole();
        $lineas = $this->lineas($partida);
        $pedido->refresh();

        $this->assertEquals((float) $pedido->total, $lineas[N::BANCOS]['debe']);
        $this->assertEquals((float) $pedido->iva, $lineas[N::IVA_DEBITO]['haber']);
        $this->assertEquals(223.21, $lineas[N::SERVICIO_ARMADO]['haber']); // 250 / 1.12
        $this->assertEqualsWithDelta((float) $pedido->base_imponible, $lineas[N::VENTAS]['haber'] + $lineas[N::SERVICIO_ARMADO]['haber'], 0.001);
        $this->assertEquals($lineas[N::COSTO_VENTAS]['debe'], $lineas[N::INVENTARIO]['haber']);
        $this->assertEquals($partida->total_debe, $partida->total_haber);
    }

    public function test_el_reembolso_y_el_ajuste_generan_partidas(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);
        $pago = $this->pagoTransferencia($pedido);
        app(PagoService::class)->aprobar($pago);
        $this->actingAs($this->contador);
        app(PagoService::class)->reembolsar($pago, (float) $pedido->total, 'Producto defectuoso', true);

        $reembolso = $this->lineas(Partida::query()->where('origen_tipo', 'Reembolso')->sole());
        $this->assertEquals((float) $pedido->total, $reembolso[N::BANCOS]['haber']);
        $this->assertEquals((float) $pedido->iva, $reembolso[N::IVA_DEBITO]['debe']);
        $this->assertArrayHasKey(N::COSTO_VENTAS, $reembolso);

        app(CompraService::class)->ajustarInventario($this->producto('razer-deathadder-essential'), -1, 'Mouse dañado en bodega');
        $ajuste = $this->lineas(Partida::query()->where('origen_tipo', 'MovimientoInventario')->sole());
        $this->assertGreaterThan(0, $ajuste[N::MERMAS]['debe']);
    }

    public function test_la_base_de_datos_rechaza_partidas_descuadradas_o_modificadas(): void
    {
        try {
            app(ContabilidadService::class)->registrar('Descuadrada', [[N::BANCOS, 100, 0], [N::CAPITAL, 0, 90]]);
            $this->fail('Se registró una partida descuadrada.');
        } catch (DomainException) {
            $this->assertTrue(true);
        }

        $partida = app(ContabilidadService::class)->registrar('Aporte de capital', [[N::BANCOS, 100, 0], [N::CAPITAL, 0, 100]], 'MANUAL');

        // Aunque se salte el servicio, los disparadores lo impiden.
        foreach ([
            fn () => DB::table('partida_detalle')->insert(['partida_id' => $partida->id, 'cuenta_id' => DB::table('cuentas_contables')->where('codigo', N::BANCOS)->value('id'), 'debe' => 50, 'haber' => 0]),
            fn () => DB::table('partidas')->where('id', $partida->id)->update(['descripcion' => 'Alterada']),
            fn () => DB::table('partida_detalle')->where('partida_id', $partida->id)->delete(),
            fn () => DB::table('partida_detalle')->insert(['partida_id' => $partida->id, 'cuenta_id' => DB::table('cuentas_contables')->where('codigo', '1')->value('id'), 'debe' => 0, 'haber' => 1]),
        ] as $intento) {
            try {
                $intento();
                $this->fail('La base de datos permitió alterar la partida.');
            } catch (QueryException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_la_reversion_anula_una_sola_vez(): void
    {
        $partida = app(ContabilidadService::class)->registrar('Aporte', [[N::BANCOS, 500, 0], [N::CAPITAL, 0, 500]], 'MANUAL');

        $this->actingAs($this->contador)->post("/admin/contabilidad/partidas/{$partida->id}/revertir", ['motivo' => 'Registro duplicado'])->assertRedirect();

        $reversion = Partida::query()->where('revierte_partida_id', $partida->id)->sole();
        $this->assertSame(['debe' => 0.0, 'haber' => 500.0], $this->lineas($reversion)[N::BANCOS]);

        $this->expectException(DomainException::class);
        app(ContabilidadService::class)->revertir($partida->refresh(), 'Otra vez');
    }

    public function test_un_periodo_cerrado_no_acepta_partidas(): void
    {
        $servicio = app(ContabilidadService::class);
        $mesPasado = CarbonImmutable::now()->subMonthNoOverflow();
        $servicio->registrar('Mes pasado', [[N::BANCOS, 10, 0], [N::CAPITAL, 0, 10]], 'MANUAL', $mesPasado);

        $periodo = PeriodoContable::query()->where('anio', $mesPasado->year)->where('mes', $mesPasado->month)->firstOrFail();
        $this->actingAs($this->contador)->post("/admin/contabilidad/periodos/{$periodo->id}/cerrar")->assertRedirect();

        $this->expectException(DomainException::class);
        $servicio->registrar('Tarde', [[N::BANCOS, 10, 0], [N::CAPITAL, 0, 10]], 'MANUAL', $mesPasado);
    }

    public function test_partida_manual_desde_el_panel(): void
    {
        $this->actingAs($this->contador)->post('/admin/contabilidad/partidas', [
            'fecha' => today()->toDateString(),
            'descripcion' => 'Pago a proveedor',
            'lineas' => [['codigo' => N::PROVEEDORES, 'debe' => '1000'], ['codigo' => N::BANCOS, 'haber' => '900']],
        ])->assertSessionHasErrors('lineas');

        $this->actingAs($this->contador)->post('/admin/contabilidad/partidas', [
            'fecha' => today()->toDateString(),
            'descripcion' => 'Pago a proveedor',
            'lineas' => [['codigo' => N::PROVEEDORES, 'debe' => '1000'], ['codigo' => N::BANCOS, 'haber' => '1000']],
        ])->assertRedirect();

        $this->assertSame(1, Partida::query()->where('tipo', 'MANUAL')->count());
    }

    public function test_el_balance_cuadra_y_el_estado_de_resultados_calcula_la_utilidad(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 2]); // Q790
        app(PagoService::class)->aprobar($this->pagoTransferencia($pedido));

        $reportes = app(ReporteService::class);
        $balance = $reportes->balanceComprobacion(CarbonImmutable::today());
        $this->assertEquals($balance['totales']['debe'], $balance['totales']['haber']);
        $this->assertEquals($balance['totales']['saldo_deudor'], $balance['totales']['saldo_acreedor']);

        $resultados = $reportes->estadoResultados(CarbonImmutable::today()->startOfMonth(), CarbonImmutable::today());
        $costo = round(2 * (float) $this->producto('logitech-g502-hero')->costo_promedio, 2);
        $this->assertEquals(705.36, $resultados['total_ingresos']); // 790 / 1.12
        $this->assertEquals($costo, $resultados['total_costos']);
        $this->assertEquals(round(705.36 - $costo, 2), $resultados['utilidad_neta']);
    }

    public function test_permisos_y_exportacion_de_reportes(): void
    {
        $vendedor = User::factory()->conRol(Rol::Vendedor)->create();

        $this->actingAs($vendedor)->get('/admin/reportes/ventas')->assertOk();
        $this->actingAs($vendedor)->get('/admin/reportes/libro-diario')->assertForbidden();
        $this->actingAs($vendedor)->get('/admin/contabilidad/partidas')->assertForbidden();

        foreach (['libro-diario', 'libro-mayor', 'balance-comprobacion', 'estado-resultados', 'inventario', 'ventas'] as $reporte) {
            $this->actingAs($this->contador)->get("/admin/reportes/{$reporte}")->assertOk();
        }

        $this->actingAs($this->contador)->get('/admin/reportes/balance-comprobacion?formato=csv')->assertOk()->assertDownload();
        $this->actingAs($this->contador)->get('/admin')->assertOk()->assertSee('Libro diario');
    }
}
