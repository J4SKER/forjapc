<?php

namespace Tests\Feature\Ventas;

use App\Enums\EstadoOrdenArmado;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\Rol;
use App\Enums\TipoMovimiento;
use App\Events\PagoAprobado;
use App\Models\EventoWebhook;
use App\Models\Pago;
use App\Models\User;
use App\Notifications\PagoRechazado;
use App\Services\PagoService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PagosTest extends TestCase
{
    use AyudasVentas, RefreshDatabase;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararTienda();
        $this->vendedor = User::factory()->conRol(Rol::Vendedor)->create();
    }

    public function test_el_cliente_sube_su_comprobante_y_ventas_lo_aprueba(): void
    {
        Storage::fake('local');
        Event::fake([PagoAprobado::class]);
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 2]);

        $this->actingAs($this->cliente)->post("/mi-cuenta/pedidos/{$pedido->numero}/transferencia", [
            'banco' => 'Banrural',
            'numero_boleta' => '998877',
            'monto' => $pedido->total,
            'comprobante' => UploadedFile::fake()->image('boleta.jpg'),
        ])->assertSessionHasNoErrors();

        $pago = Pago::query()->firstOrFail();
        Storage::disk('local')->assertExists($pago->comprobante_ruta);
        $this->actingAs($this->vendedor)->get('/admin/pagos')->assertOk()->assertSee($pedido->numero);

        $this->actingAs($this->vendedor)->post("/admin/pagos/{$pago->id}/aprobar")->assertRedirect();

        $pedido->refresh();
        $mouse = $this->producto('logitech-g502-hero');
        $this->assertSame(EstadoPedido::EnPreparacion, $pedido->estado);
        $this->assertNotNull($pedido->pagado_at);
        $this->assertSame(13, $mouse->existencia);
        $this->assertSame(0, $mouse->reservado);
        $this->assertSame(TipoMovimiento::SalidaVenta, $mouse->movimientos()->latest('id')->first()->tipo);
        $this->assertNotNull($pedido->items()->first()->costo_unitario);
        Event::assertDispatched(PagoAprobado::class);
    }

    public function test_un_pedido_con_armado_genera_su_orden_de_taller_al_pagarse(): void
    {
        $pedido = $this->pedidoConPcArmada();
        app(PagoService::class)->aprobar($this->pagoTransferencia($pedido), $this->vendedor);

        $this->assertSame(EstadoPedido::EnArmado, $pedido->refresh()->estado);
        $this->assertSame(EstadoOrdenArmado::Pendiente, $pedido->ordenesArmado()->sole()->estado);
    }

    public function test_rechazar_un_comprobante_avisa_al_cliente_y_el_pedido_sigue_pendiente(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);
        $pago = $this->pagoTransferencia($pedido);

        $this->actingAs($this->vendedor)->post("/admin/pagos/{$pago->id}/rechazar", ['motivo' => 'El monto no coincide'])->assertRedirect();

        $this->assertSame(EstadoPago::Rechazado, $pago->refresh()->estado);
        $this->assertSame(EstadoPedido::PendientePago, $pedido->refresh()->estado);
        Notification::assertSentTo($this->cliente, PagoRechazado::class);
    }

    public function test_solo_puede_haber_un_pago_aprobado_por_pedido(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);
        $primero = $this->pagoTransferencia($pedido);
        $segundo = $this->pagoTransferencia($pedido);

        app(PagoService::class)->aprobar($primero, $this->vendedor);

        try {
            app(PagoService::class)->aprobar($segundo, $this->vendedor);
            $this->fail('Se aprobó un segundo pago.');
        } catch (DomainException) {
            $this->assertSame(EstadoPago::Pendiente, $segundo->refresh()->estado);
        }

        // La base de datos también lo impide aunque se salte el servicio.
        $this->expectException(QueryException::class);
        $segundo->forceFill(['estado' => EstadoPago::Aprobado])->save();
    }

    public function test_pago_con_paypal_sandbox_crea_la_orden_y_la_captura(): void
    {
        config(['services.paypal.client_id' => 'cliente', 'services.paypal.secret' => 'secreto']);
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']); // Q395
        $usd = round(395 / 7.75, 2);

        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/v2/checkout/orders' => Http::response(['id' => 'ORDEN-1']),
            '*/v2/checkout/orders/ORDEN-1/capture' => Http::response(['status' => 'COMPLETED', 'purchase_units' => [['payments' => ['captures' => [[
                'id' => 'CAPTURA-1', 'status' => 'COMPLETED',
                'amount' => ['currency_code' => 'USD', 'value' => number_format($usd, 2, '.', '')],
                'seller_receivable_breakdown' => ['paypal_fee' => ['value' => '2.50']],
            ]]]]]]),
        ]);

        $this->actingAs($this->cliente)->postJson("/mi-cuenta/pedidos/{$pedido->numero}/paypal/orden")->assertOk()->assertJson(['id' => 'ORDEN-1']);
        $this->actingAs($this->cliente)->postJson("/mi-cuenta/pedidos/{$pedido->numero}/paypal/capturar", ['orderID' => 'ORDEN-1'])->assertOk()->assertJson(['ok' => true]);

        $pago = $pedido->pagos()->sole();
        $this->assertSame(EstadoPago::Aprobado, $pago->estado);
        $this->assertEquals($usd, (float) $pago->monto_usd);
        $this->assertEquals(7.75, (float) $pago->tipo_cambio);
        $this->assertEquals(19.38, (float) $pago->comision);
        $this->assertSame(EstadoPedido::EnPreparacion, $pedido->refresh()->estado);
    }

    public function test_si_paypal_no_completa_el_monto_esperado_no_se_aprueba(): void
    {
        config(['services.paypal.client_id' => 'cliente', 'services.paypal.secret' => 'secreto']);
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']);

        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/v2/checkout/orders' => Http::response(['id' => 'ORDEN-2']),
            '*/capture' => Http::response(['purchase_units' => [['payments' => ['captures' => [[
                'id' => 'C', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'USD', 'value' => '1.00'],
            ]]]]]]),
        ]);

        $this->actingAs($this->cliente)->postJson("/mi-cuenta/pedidos/{$pedido->numero}/paypal/orden");
        $this->actingAs($this->cliente)->postJson("/mi-cuenta/pedidos/{$pedido->numero}/paypal/capturar", ['orderID' => 'ORDEN-2'])->assertStatus(422);

        $this->assertSame(EstadoPago::Rechazado, $pedido->pagos()->sole()->estado);
        $this->assertSame(EstadoPedido::PendientePago, $pedido->refresh()->estado);
    }

    public function test_el_webhook_de_paypal_se_procesa_una_sola_vez(): void
    {
        config(['services.paypal.client_id' => 'cliente', 'services.paypal.secret' => 'secreto', 'services.paypal.webhook_id' => 'WH']);
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']);
        $pago = new Pago;
        $pago->forceFill(['pedido_id' => $pedido->id, 'metodo' => 'PAYPAL', 'estado' => 'PENDIENTE', 'monto' => $pedido->total, 'monto_usd' => 50.97, 'tipo_cambio' => 7.75, 'referencia_externa' => 'ORDEN-3'])->save();

        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
        ]);

        $evento = ['id' => 'WH-EVT-1', 'event_type' => 'PAYMENT.CAPTURE.COMPLETED', 'resource' => [
            'id' => 'CAP-3', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'USD', 'value' => '50.97'],
            'seller_receivable_breakdown' => ['paypal_fee' => ['value' => '2.00']],
            'supplementary_data' => ['related_ids' => ['order_id' => 'ORDEN-3']],
        ]];

        $this->postJson('/webhooks/paypal', $evento)->assertOk();
        $this->postJson('/webhooks/paypal', $evento)->assertOk();

        $this->assertSame(1, EventoWebhook::query()->count());
        $this->assertSame(EstadoPago::Aprobado, $pago->refresh()->estado);
        $this->assertEquals(15.50, (float) $pago->comision); // DEF-020: US$2.00 × 7.75
        $this->assertSame(1, $this->producto('logitech-g502-hero')->movimientos()->where('tipo', 'SALIDA_VENTA')->count());
    }

    public function test_si_el_navegador_no_avisa_la_aprobacion_el_pedido_se_sincroniza_al_abrirlo(): void
    {
        config(['services.paypal.client_id' => 'cliente', 'services.paypal.secret' => 'secreto']);
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']);
        $usd = round(395 / 7.75, 2);

        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/v2/checkout/orders' => Http::response(['id' => 'ORDEN-4']),
            '*/v2/checkout/orders/ORDEN-4' => Http::response(['id' => 'ORDEN-4', 'status' => 'APPROVED']),
            '*/v2/checkout/orders/ORDEN-4/capture' => Http::response(['status' => 'COMPLETED', 'purchase_units' => [['payments' => ['captures' => [[
                'id' => 'CAPTURA-4', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'USD', 'value' => number_format($usd, 2, '.', '')],
            ]]]]]]),
        ]);

        $this->actingAs($this->cliente)->postJson("/mi-cuenta/pedidos/{$pedido->numero}/paypal/orden")->assertOk();
        // La llamada de captura del navegador nunca llega; el cliente vuelve a abrir su pedido.
        $this->actingAs($this->cliente)->get("/mi-cuenta/pedidos/{$pedido->numero}")->assertOk();

        $this->assertSame(EstadoPago::Aprobado, $pedido->pagos()->sole()->estado);
        $this->assertSame(EstadoPedido::EnPreparacion, $pedido->refresh()->estado);
    }

    public function test_el_webhook_de_orden_aprobada_captura_el_pago(): void
    {
        config(['services.paypal.client_id' => 'cliente', 'services.paypal.secret' => 'secreto', 'services.paypal.webhook_id' => 'WH']);
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']);
        $pago = new Pago;
        $pago->forceFill(['pedido_id' => $pedido->id, 'metodo' => 'PAYPAL', 'estado' => 'PENDIENTE', 'monto' => $pedido->total, 'monto_usd' => 50.97, 'tipo_cambio' => 7.75, 'referencia_externa' => 'ORDEN-5'])->save();

        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
            '*/v2/checkout/orders/ORDEN-5/capture' => Http::response(['status' => 'COMPLETED', 'purchase_units' => [['payments' => ['captures' => [[
                'id' => 'CAPTURA-5', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'USD', 'value' => '50.97'],
            ]]]]]]),
        ]);

        $this->postJson('/webhooks/paypal', ['id' => 'WH-EVT-5', 'event_type' => 'CHECKOUT.ORDER.APPROVED', 'resource' => ['id' => 'ORDEN-5']])->assertOk();

        $this->assertSame(EstadoPago::Aprobado, $pago->refresh()->estado);
    }

    public function test_un_webhook_con_firma_invalida_se_rechaza(): void
    {
        config(['services.paypal.client_id' => 'cliente', 'services.paypal.secret' => 'secreto', 'services.paypal.webhook_id' => 'WH']);
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/verify-webhook-signature' => Http::response(['verification_status' => 'FAILURE']),
        ]);

        $this->postJson('/webhooks/paypal', ['id' => 'X', 'event_type' => 'PAYMENT.CAPTURE.COMPLETED'])->assertStatus(400);
        $this->assertSame(0, EventoWebhook::query()->count());
    }

    public function test_el_reembolso_total_devuelve_la_mercaderia_y_el_parcial_no_excede_lo_pagado(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 2]); // Q790
        $pago = $this->pagoTransferencia($pedido);
        app(PagoService::class)->aprobar($pago, $this->vendedor);

        $this->actingAs($this->vendedor);
        app(PagoService::class)->reembolsar($pago, 100, 'Descuento por demora', false);

        try {
            app(PagoService::class)->reembolsar($pago, 700, 'Excede', false);
            $this->fail('Se permitió reembolsar más de lo pagado.');
        } catch (DomainException) {
            $this->assertEquals(690.00, $pago->refresh()->saldoReembolsable());
        }

        $this->post("/admin/pedidos/{$pedido->numero}/pagos/{$pago->id}/reembolso", [
            'monto' => '690', 'motivo' => 'Cliente desistió de la compra', 'devolver_inventario' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(EstadoPedido::Reembolsado, $pedido->refresh()->estado);
        $this->assertSame(15, $this->producto('logitech-g502-hero')->existencia);
    }
}
