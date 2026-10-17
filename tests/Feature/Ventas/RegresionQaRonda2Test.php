<?php

namespace Tests\Feature\Ventas;

use App\Enums\EstadoPago;
use App\Enums\Rol;
use App\Models\EventoWebhook;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use App\Services\PagoService;
use App\Services\PedidoService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Una prueba por cada defecto de la ronda 2 de QA (pagos con PayPal sandbox).
 */
class RegresionQaRonda2Test extends TestCase
{
    use AyudasVentas, RefreshDatabase;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararTienda();
        $this->vendedor = User::factory()->conRol(Rol::Vendedor)->create();
        config(['services.paypal.client_id' => 'cliente', 'services.paypal.secret' => 'secreto', 'services.paypal.webhook_id' => 'WH']);
    }

    /**
     * Pago de PayPal pendiente de un pedido de Q195 (US$25.16 a 7.75).
     *
     * @param  array<string, mixed>  $extra
     */
    private function pagoPaypal(Pedido $pedido, string $orden, array $extra = []): Pago
    {
        $pago = new Pago;
        $pago->forceFill(['pedido_id' => $pedido->id, 'metodo' => 'PAYPAL', 'estado' => 'PENDIENTE', 'monto' => $pedido->total,
            'monto_usd' => round((float) $pedido->total / 7.75, 2), 'tipo_cambio' => 7.75, 'referencia_externa' => $orden, ...$extra])->save();

        return $pago;
    }

    public function test_def_017_un_pago_paypal_sin_captura_no_se_aprueba_a_mano(): void
    {
        $pago = $this->pagoPaypal($this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']), 'ORDEN-17');

        $this->actingAs($this->vendedor)->post("/admin/pagos/{$pago->id}/aprobar")->assertRedirect();

        $this->assertSame(EstadoPago::Pendiente, $pago->refresh()->estado);
    }

    public function test_def_018_un_reembolso_rechazado_por_paypal_muestra_un_mensaje(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']);
        $pago = $this->pagoPaypal($pedido, 'ORDEN-18', ['datos' => ['captura_id' => 'CAP-18']]);
        app(PagoService::class)->aprobar($pago);

        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/refund' => Http::response(['name' => 'UNPROCESSABLE_ENTITY', 'details' => [['description' => 'Capture has already been fully refunded']]], 422),
        ]);

        $this->actingAs($this->vendedor)->from("/admin/pedidos/{$pedido->numero}")
            ->post("/admin/pedidos/{$pedido->numero}/pagos/{$pago->id}/reembolso", ['monto' => '50', 'motivo' => 'Prueba de rechazo'])
            ->assertRedirect()->assertSessionHas('estado', fn (string $m) => str_contains($m, 'PayPal no aceptó el reembolso'));

        $this->assertSame(0, $pago->reembolsos()->count());
    }

    public function test_def_019_los_reembolsos_parciales_no_superan_la_captura_en_dolares(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']);
        $pago = $this->pagoPaypal($pedido, 'ORDEN-19', ['datos' => ['captura_id' => 'CAP-19']]);
        app(PagoService::class)->aprobar($pago);
        Http::fake(['*/v1/oauth2/token' => Http::response(['access_token' => 'token']), '*/refund' => Http::response(['id' => 'R'])]);

        $this->actingAs($this->vendedor);
        $total = (float) $pedido->total;
        // Q10.02 → US$1.29 y Q384.98 → US$49.67: convertidos por separado suman 50.96, no los 50.97 capturados.
        app(PagoService::class)->reembolsar($pago, 10.02, 'Parcial', false);
        app(PagoService::class)->reembolsar($pago->refresh(), round($total - 10.02, 2), 'Resto', false);

        $usd = Http::recorded(fn ($request) => str_ends_with($request->url(), '/refund'))->sum(fn ($par) => (float) $par[0]['amount']['value']);
        $this->assertEqualsWithDelta((float) $pago->monto_usd, $usd, 0.001);
    }

    public function test_def_020_el_webhook_valida_el_monto_y_guarda_la_comision(): void
    {
        $pago = $this->pagoPaypal($this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']), 'ORDEN-20');
        Http::fake(['*/v1/oauth2/token' => Http::response(['access_token' => 'token']), '*/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'])]);

        $evento = fn (string $id, string $monto) => ['id' => $id, 'event_type' => 'PAYMENT.CAPTURE.COMPLETED', 'resource' => [
            'id' => 'CAP-20', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'USD', 'value' => $monto],
            'seller_receivable_breakdown' => ['paypal_fee' => ['value' => '1.20']],
            'supplementary_data' => ['related_ids' => ['order_id' => 'ORDEN-20']],
        ]];

        $this->postJson('/webhooks/paypal', $evento('WH-20A', '1.00'))->assertOk();
        $this->assertSame(EstadoPago::Pendiente, $pago->refresh()->estado);
        $this->assertNotNull(EventoWebhook::query()->where('evento_id', 'WH-20A')->value('error'));

        $this->postJson('/webhooks/paypal', $evento('WH-20B', number_format((float) $pago->monto_usd, 2, '.', '')))->assertOk();
        $this->assertSame(EstadoPago::Aprobado, $pago->refresh()->estado);
        $this->assertEquals(9.30, (float) $pago->comision);
    }

    public function test_def_021_capturar_una_orden_no_aprobada_responde_422(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']);
        $this->pagoPaypal($pedido, 'ORDEN-21');
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/capture' => Http::response(['name' => 'UNPROCESSABLE_ENTITY', 'details' => [['issue' => 'ORDER_NOT_APPROVED']]], 422),
        ]);

        $this->actingAs($this->cliente)->postJson("/mi-cuenta/pedidos/{$pedido->numero}/paypal/capturar", ['orderID' => 'ORDEN-21'])
            ->assertStatus(422)->assertJson(['error' => 'PayPal no aprobó este pago, así que no se realizó ningún cobro. Intenta de nuevo.']);
    }

    public function test_def_022_las_ordenes_abandonadas_no_quedan_por_verificar(): void
    {
        $pagado = $this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']);
        $abandonada = $this->pagoPaypal($pagado, 'ORDEN-22A');
        app(PagoService::class)->aprobar($this->pagoPaypal($pagado, 'ORDEN-22B', ['datos' => ['captura_id' => 'CAP-22']]));

        $cancelado = $this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']);
        $sinPagar = $this->pagoPaypal($cancelado, 'ORDEN-22C');
        app(PedidoService::class)->cancelar($cancelado, 'Venció el plazo para pagar.');

        $this->assertSame(EstadoPago::Rechazado, $abandonada->refresh()->estado);
        $this->assertSame(EstadoPago::Rechazado, $sinPagar->refresh()->estado);
    }

    public function test_def_023_la_bandeja_muestra_dolares_tasa_y_comision(): void
    {
        $pago = $this->pagoPaypal($this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']), 'ORDEN-23', ['comision' => 9.30, 'datos' => ['captura_id' => 'CAP-23']]);
        app(PagoService::class)->aprobar($pago);

        $this->actingAs($this->vendedor)->get('/admin/pagos?estado=APROBADO')->assertOk()
            ->assertSee('US$'.number_format((float) $pago->monto_usd, 2))->assertSee('Q7.75000 por dólar')->assertSee('Comisión de PayPal: Q9.30');
    }

    public function test_def_024_los_textos_de_contrasena_usan_tu(): void
    {
        $this->assertSame('Tu contraseña se restableció.', __('passwords.reset'));
        $this->assertStringNotContainsString('su ', __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.'));
    }

    public function test_un_pago_paypal_aprobado_a_mano_desde_el_servicio_tambien_se_rechaza(): void
    {
        $pago = $this->pagoPaypal($this->pedidoCon(['logitech-g502-hero' => 1], ['metodo_pago' => 'PAYPAL']), 'ORDEN-25');

        $this->expectException(DomainException::class);
        app(PagoService::class)->aprobar($pago, $this->vendedor);
    }
}
