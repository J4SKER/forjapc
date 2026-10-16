<?php

namespace Tests\Feature\Ventas;

use App\Enums\EstadoOrdenArmado;
use App\Enums\EstadoPedido;
use App\Enums\Rol;
use App\Models\OrdenArmado;
use App\Models\User;
use App\Notifications\PedidoActualizado;
use App\Services\PagoService;
use App\Services\PedidoService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PedidosTallerTest extends TestCase
{
    use AyudasVentas, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararTienda();
    }

    public function test_solo_se_permiten_transiciones_validas_y_quedan_en_el_historial(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);

        try {
            app(PedidoService::class)->cambiarEstado($pedido, EstadoPedido::Entregado);
            $this->fail('Se permitió entregar un pedido sin pagar.');
        } catch (DomainException) {
            $this->assertSame(EstadoPedido::PendientePago, $pedido->refresh()->estado);
        }

        app(PagoService::class)->aprobar($this->pagoTransferencia($pedido));
        $this->assertSame(
            ['PENDIENTE_PAGO', 'PAGADO', 'EN_PREPARACION'],
            $pedido->historial()->reorder('id')->pluck('estado_nuevo')->map->value->all(),
        );
    }

    public function test_el_vencimiento_cancela_y_libera_reservas_salvo_con_comprobante_por_verificar(): void
    {
        $vencido = $this->pedidoCon(['logitech-g502-hero' => 3]);
        $conComprobante = $this->pedidoCon(['razer-deathadder-essential' => 1]);
        $this->pagoTransferencia($conComprobante);
        $vencido->forceFill(['vence_at' => now()->subMinute()])->save();
        $conComprobante->forceFill(['vence_at' => now()->subMinute()])->save();

        $this->artisan('forjapc:vencer-pedidos')->expectsOutputToContain('Pedidos vencidos: 1')->assertSuccessful();

        $this->assertSame(EstadoPedido::Cancelado, $vencido->refresh()->estado);
        $this->assertSame(0, $this->producto('logitech-g502-hero')->reservado);
        $this->assertSame(EstadoPedido::PendientePago, $conComprobante->refresh()->estado);
        Notification::assertSentTo($this->cliente, PedidoActualizado::class);
    }

    public function test_el_cliente_cancela_su_pedido_pendiente_y_no_el_de_otro(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);

        $this->actingAs(User::factory()->create())->post("/mi-cuenta/pedidos/{$pedido->numero}/cancelar")->assertNotFound();
        $this->actingAs($this->cliente)->post("/mi-cuenta/pedidos/{$pedido->numero}/cancelar")->assertRedirect();

        $this->assertSame(EstadoPedido::Cancelado, $pedido->refresh()->estado);
    }

    public function test_ventas_registra_el_envio_y_la_entrega(): void
    {
        $direccion = $this->direccionDe($this->cliente);
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_entrega' => 'ENVIO', 'direccion_id' => $direccion->id]);
        app(PagoService::class)->aprobar($this->pagoTransferencia($pedido));
        $vendedor = User::factory()->conRol(Rol::Vendedor)->create();

        $this->actingAs($vendedor)->post("/admin/pedidos/{$pedido->numero}/estado", ['estado' => 'LISTO_ENTREGA'])->assertRedirect();
        $this->actingAs($vendedor)->post("/admin/pedidos/{$pedido->numero}/envio", ['mensajeria' => 'Cargo Expreso', 'numero_guia' => 'CE-123'])->assertRedirect();
        $this->assertSame(EstadoPedido::Enviado, $pedido->refresh()->estado);

        $this->actingAs($vendedor)->post("/admin/pedidos/{$pedido->numero}/estado", ['estado' => 'ENTREGADO'])->assertRedirect();
        $this->assertSame(EstadoPedido::Entregado, $pedido->refresh()->estado);
        $this->assertNotNull($pedido->entregado_at);

        $this->actingAs($this->cliente)->get("/mi-cuenta/pedidos/{$pedido->numero}")->assertOk()->assertSee('CE-123');
    }

    public function test_flujo_completo_del_taller_hasta_listo_para_entrega(): void
    {
        $pedido = $this->pedidoConPcArmada();
        app(PagoService::class)->aprobar($this->pagoTransferencia($pedido));
        $orden = OrdenArmado::query()->sole();
        $tecnico = User::factory()->conRol(Rol::Tecnico)->create();

        $this->actingAs($tecnico)->get('/admin/taller')->assertOk()->assertSee('PC de prueba');
        $this->actingAs($tecnico)->post("/admin/taller/{$orden->id}/tomar")->assertRedirect();

        // Sin lista de verificación completa no se registran pruebas.
        $this->actingAs($tecnico)->post("/admin/taller/{$orden->id}/pruebas", ['temp_cpu_max' => 70, 'minutos_estres' => 30, 'aprobada' => '1']);
        $this->assertNull($orden->refresh()->pruebas);

        $this->actingAs($tecnico)->post("/admin/taller/{$orden->id}/checklist", ['checklist' => array_fill_keys(array_keys(OrdenArmado::CHECKLIST), '1')]);
        $this->actingAs($tecnico)->post("/admin/taller/{$orden->id}/pruebas", ['temp_cpu_max' => 72, 'temp_gpu_max' => 65, 'minutos_estres' => 30, 'aprobada' => '1']);
        $this->assertSame(EstadoOrdenArmado::EnPruebas, $orden->refresh()->estado);

        $this->actingAs($tecnico)->post("/admin/taller/{$orden->id}/completar")->assertRedirect();

        $this->assertSame(EstadoOrdenArmado::Completada, $orden->refresh()->estado);
        $this->assertSame(EstadoPedido::ListoEntrega, $pedido->refresh()->estado);
    }

    public function test_el_cliente_no_entra_al_taller_ni_a_la_bandeja_de_pedidos(): void
    {
        $this->actingAs($this->cliente)->get('/admin/taller')->assertForbidden();
        $this->actingAs($this->cliente)->get('/admin/pedidos')->assertForbidden();
        $this->actingAs(User::factory()->conRol(Rol::Tecnico)->create())->get('/admin/pedidos')->assertForbidden();
    }

    public function test_solo_quien_recibio_el_producto_puede_opinar(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);
        $datos = ['calificacion' => 5, 'titulo' => 'Excelente', 'comentario' => 'Muy cómodo para jugar.'];

        $this->actingAs($this->cliente)->post('/producto/logitech-g502-hero/resenas', $datos)->assertForbidden();

        app(PagoService::class)->aprobar($this->pagoTransferencia($pedido));
        foreach ([EstadoPedido::ListoEntrega, EstadoPedido::Entregado] as $estado) {
            app(PedidoService::class)->cambiarEstado($pedido, $estado);
        }

        $this->actingAs($this->cliente)->post('/producto/logitech-g502-hero/resenas', $datos)->assertRedirect();
        $this->assertDatabaseHas('resenas_producto', ['usuario_id' => $this->cliente->id, 'estado' => 'PENDIENTE', 'compra_verificada' => true]);
    }
}
