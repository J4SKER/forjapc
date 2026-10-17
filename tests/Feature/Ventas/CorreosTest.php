<?php

namespace Tests\Feature\Ventas;

use App\Enums\EstadoPedido;
use App\Enums\Rol;
use App\Models\User;
use App\Notifications\PedidoActualizado;
use App\Notifications\PedidoRecibido;
use App\Notifications\ReembolsoProcesado;
use App\Services\PagoService;
use App\Services\PedidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Los correos explican qué pasó, por qué y qué sigue, con el resumen del pedido.
 */
class CorreosTest extends TestCase
{
    use AyudasVentas, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararTienda();
        Notification::fake();
    }

    private function html(object $notificacion): string
    {
        return html_entity_decode(strip_tags((string) $notificacion->toMail($this->cliente)->render()));
    }

    public function test_el_pedido_recibido_incluye_resumen_plazo_y_como_pagar(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);

        Notification::assertSentTo($this->cliente, PedidoRecibido::class, function (PedidoRecibido $n) use ($pedido) {
            $texto = $this->html($n);

            return str_contains($texto, 'apartamos los productos a tu nombre')
                && str_contains($texto, '¿Cómo pago?')
                && str_contains($texto, "Resumen del pedido {$pedido->numero}")
                && str_contains($texto, 'Total: Q'.number_format((float) $pedido->total, 2));
        });
    }

    public function test_el_reembolso_parcial_avisa_monto_motivo_y_que_el_pedido_sigue_activo(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);
        $pago = $this->pagoTransferencia($pedido);
        app(PagoService::class)->aprobar($pago);

        $this->actingAs(User::factory()->conRol(Rol::Vendedor)->create());
        app(PagoService::class)->reembolsar($pago->refresh(), 50, 'Diferencia de precio por promoción', false);

        Notification::assertSentTo($this->cliente, ReembolsoProcesado::class, function (ReembolsoProcesado $n) {
            $texto = $this->html($n);

            return ! $n->esTotal
                && str_contains($texto, 'Te devolvimos Q50.00')
                && str_contains($texto, 'Motivo: Diferencia de precio por promoción')
                && str_contains($texto, 'Tu pedido sigue activo');
        });
    }

    public function test_el_reembolso_total_envia_un_solo_correo_con_el_motivo(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);
        $pago = $this->pagoTransferencia($pedido);
        app(PagoService::class)->aprobar($pago);

        $this->actingAs(User::factory()->conRol(Rol::Vendedor)->create());
        app(PagoService::class)->reembolsar($pago->refresh(), (float) $pedido->total, 'Producto defectuoso', true);

        Notification::assertSentTo($this->cliente, ReembolsoProcesado::class, fn (ReembolsoProcesado $n) => $n->esTotal && str_contains($this->html($n), 'Motivo: Producto defectuoso'));
        Notification::assertNotSentTo($this->cliente, PedidoActualizado::class, fn (PedidoActualizado $n) => $n->pedido->estado === EstadoPedido::Reembolsado);
    }

    public function test_el_comentario_del_equipo_y_el_motivo_de_cancelacion_llegan_al_cliente(): void
    {
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);
        app(PagoService::class)->aprobar($this->pagoTransferencia($pedido));

        $this->actingAs(User::factory()->conRol(Rol::Vendedor)->create())
            ->post("/admin/pedidos/{$pedido->numero}/estado", ['estado' => 'LISTO_ENTREGA', 'comentario' => 'Incluimos un mousepad de regalo.']);

        Notification::assertSentTo($this->cliente, PedidoActualizado::class, fn (PedidoActualizado $n) => str_contains($this->html($n), 'Nota de nuestro equipo: Incluimos un mousepad de regalo.')
            && str_contains($this->html($n), '¿Qué sigue?'));

        $otro = $this->pedidoCon(['logitech-g502-hero' => 1]);
        app(PedidoService::class)->cancelar($otro, 'Venció el plazo para pagar.');

        Notification::assertSentTo($this->cliente, PedidoActualizado::class, fn (PedidoActualizado $n) => $n->pedido->is($otro)
            && str_contains($this->html($n), 'Motivo: Venció el plazo para pagar.')
            && str_contains($this->html($n), 'No se realizó ningún cobro'));
    }
}
