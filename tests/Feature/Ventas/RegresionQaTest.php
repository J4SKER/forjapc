<?php

namespace Tests\Feature\Ventas;

use App\Enums\EstadoOrdenArmado;
use App\Enums\EstadoPedido;
use App\Enums\Rol;
use App\Models\Direccion;
use App\Models\OrdenArmado;
use App\Models\PeriodoContable;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\ContabilidadService;
use App\Services\PagoService;
use App\Services\PedidoService;
use App\Services\TallerService;
use App\Support\Csv;
use Carbon\CarbonImmutable;
use Database\Seeders\NomenclaturaSeeder as N;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Una prueba por cada defecto encontrado en la ronda de QA (DEF-001 a DEF-015).
 */
class RegresionQaTest extends TestCase
{
    use AyudasVentas, RefreshDatabase;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararTienda();
        $this->vendedor = User::factory()->conRol(Rol::Vendedor)->create();
    }

    public function test_def_001_la_orden_de_taller_lista_sus_piezas(): void
    {
        $pedido = $this->pedidoConPcArmada();
        app(PagoService::class)->aprobar($this->pagoTransferencia($pedido));

        $this->actingAs(User::factory()->conRol(Rol::Tecnico)->create())
            ->get('/admin/taller/'.OrdenArmado::query()->value('id'))
            ->assertOk()
            ->assertSee('AMD Ryzen 5 7600')
            ->assertSee('NZXT H5 Flow');
    }

    public function test_def_002_un_error_por_fila_en_la_compra_se_muestra_sin_error_500(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Proveedor', 'nit' => '7654321-0']);
        $id = $this->producto('logitech-g502-hero')->id;

        $this->actingAs($this->vendedor)->from('/admin/compras/create')->followingRedirects()->post('/admin/compras', [
            'proveedor_id' => $proveedor->id, 'numero_factura' => '9', 'fecha_factura' => today()->toDateString(),
            'lineas' => [['producto_id' => $id, 'cantidad' => 1, 'costo_unitario' => '10'], ['producto_id' => $id, 'cantidad' => 1, 'costo_unitario' => '10']],
        ])->assertOk()->assertSee('Un producto aparece más de una vez');
    }

    public function test_def_003_desmarcar_la_principal_no_deja_al_cliente_sin_principal(): void
    {
        $direccion = Direccion::factory()->for($this->cliente, 'usuario')->create(['es_principal' => true]);

        $this->actingAs($this->cliente)->put("/mi-cuenta/direcciones/{$direccion->id}", [
            ...$direccion->only(['alias', 'nombre_receptor', 'telefono', 'departamento_id', 'municipio', 'direccion']),
            'alias' => 'Casa nueva',
            'es_principal' => '0',
        ])->assertRedirect();

        $this->assertTrue($direccion->refresh()->es_principal);
    }

    public function test_def_004_tras_el_bloqueo_se_informa_la_cuenta_bloqueada(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $this->cliente->email, 'password' => 'incorrecta']);
        }

        $this->post('/login', ['email' => $this->cliente->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => __('auth.cuenta_bloqueada', ['minutes' => 15])]);
    }

    public function test_def_005_ventas_no_salta_el_taller_ni_entrega_un_envio_sin_guia(): void
    {
        $conArmado = $this->pedidoConPcArmada();
        app(PagoService::class)->aprobar($this->pagoTransferencia($conArmado));

        $this->actingAs($this->vendedor)->post("/admin/pedidos/{$conArmado->numero}/estado", ['estado' => 'LISTO_ENTREGA']);
        $this->assertSame(EstadoPedido::EnArmado, $conArmado->refresh()->estado);

        $envio = $this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_entrega' => 'ENVIO', 'direccion_id' => $this->direccionDe($this->cliente)->id]);
        app(PagoService::class)->aprobar($this->pagoTransferencia($envio));
        app(PedidoService::class)->cambiarEstado($envio, EstadoPedido::ListoEntrega);

        $this->actingAs($this->vendedor)->post("/admin/pedidos/{$envio->numero}/estado", ['estado' => 'ENTREGADO']);
        $this->assertSame(EstadoPedido::ListoEntrega, $envio->refresh()->estado);
    }

    public function test_def_006_no_se_reporta_incidencia_en_una_orden_completada(): void
    {
        $pedido = $this->pedidoConPcArmada();
        app(PagoService::class)->aprobar($this->pagoTransferencia($pedido));
        $orden = OrdenArmado::query()->sole();
        $orden->forceFill(['estado' => EstadoOrdenArmado::Completada])->save();

        $this->expectException(DomainException::class);
        app(TallerService::class)->reportarIncidencia($orden, 'Ya entregada');
    }

    public function test_def_007_el_reembolso_total_retira_las_ordenes_pendientes(): void
    {
        $pedido = $this->pedidoConPcArmada();
        $pago = $this->pagoTransferencia($pedido);
        app(PagoService::class)->aprobar($pago);

        $this->actingAs($this->vendedor);
        app(PagoService::class)->reembolsar($pago, (float) $pedido->total, 'Cliente desistió', true);

        $this->assertSame(0, OrdenArmado::query()->count());
    }

    public function test_def_008_no_se_aprueba_una_transferencia_menor_al_total(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);
        $pago = $this->pagoTransferencia($pedido);
        $pago->forceFill(['monto' => 1])->save();

        $this->expectException(DomainException::class);
        app(PagoService::class)->aprobar($pago, $this->vendedor);
    }

    public function test_def_009_no_se_registran_partidas_antes_de_un_periodo_cerrado(): void
    {
        $servicio = app(ContabilidadService::class);
        $mesPasado = CarbonImmutable::now()->startOfMonth()->subMonthNoOverflow();
        $servicio->registrar('Mes pasado', [[N::BANCOS, 10, 0], [N::CAPITAL, 0, 10]], 'MANUAL', $mesPasado);
        $servicio->cerrarPeriodo(PeriodoContable::query()->where('anio', $mesPasado->year)->where('mes', $mesPasado->month)->firstOrFail(), $this->vendedor);

        $this->expectException(DomainException::class);
        $servicio->registrar('Dos meses atrás', [[N::BANCOS, 10, 0], [N::CAPITAL, 0, 10]], 'MANUAL', $mesPasado->subMonthNoOverflow());
    }

    public function test_def_010_un_usuario_desactivado_pierde_su_sesion(): void
    {
        $this->actingAs($this->cliente)->get('/mi-cuenta')->assertOk();
        $this->cliente->forceFill(['activo' => false])->save();

        $this->actingAs($this->cliente->refresh())->get('/mi-cuenta')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_def_011_el_csv_neutraliza_formulas(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://x\")", Csv::celda('=HYPERLINK("http://x")'));
        $this->assertSame("'@SUM(A1)", Csv::celda('@SUM(A1)'));
        $this->assertSame('-12.50', Csv::celda('-12.50'));
        $this->assertSame('Ana López', Csv::celda('Ana López'));
    }

    public function test_def_012_una_fecha_invalida_en_los_filtros_no_provoca_error_500(): void
    {
        $admin = User::factory()->conRol(Rol::Admin)->create();

        $this->actingAs($admin)->get('/admin/pedidos?desde=2026-99-99')->assertRedirect();
        $this->actingAs($admin)->get('/admin/bitacora?hasta=hola')->assertRedirect();
        $this->actingAs($admin)->get('/admin/reportes/estado-resultados?desde=2026-02-31&hasta=xx')->assertOk();
    }

    public function test_def_013_los_conteos_por_estado_son_enteros(): void
    {
        $this->pedidoCon(['logitech-g502-hero' => 1]);

        $this->actingAs($this->vendedor)->get('/admin/pedidos')->assertOk()->assertSee('Pendiente de pago (1)')->assertDontSee('1.00');
    }

    public function test_def_014_un_texto_con_utf8_invalido_no_provoca_error_500(): void
    {
        $this->actingAs($this->cliente)->post('/mi-cuenta/direcciones', [
            'alias' => "Casa \xB1\xFF", 'nombre_receptor' => 'Ana', 'telefono' => '55551234',
            'departamento_id' => $this->direccionDe($this->cliente)->departamento_id,
            'municipio' => 'Mixco', 'direccion' => "Zona \xC3\x28 1",
        ])->assertRedirect();
    }

    public function test_def_015_el_inicio_de_sesion_tiene_limite_por_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['email' => "nadie{$i}@example.com", 'password' => 'x']);
        }

        $this->post('/login', ['email' => 'otro@example.com', 'password' => 'x'])->assertStatus(429);
    }
}
