<?php

namespace Tests\Feature\Ventas;

use App\Enums\Rol;
use App\Livewire\Tienda\BotonCarrito;
use App\Models\Producto;
use App\Models\User;
use App\Notifications\PagoRechazado;
use App\Notifications\PedidoActualizado;
use App\Notifications\ReembolsoProcesado;
use App\Services\CarritoService;
use App\Services\PagoService;
use App\Services\PedidoService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Una prueba por cada defecto de la ronda 3 de QA (correos y rediseño).
 */
class RegresionQaRonda3Test extends TestCase
{
    use AyudasVentas, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararTienda();
    }

    private function texto(object $notificacion): string
    {
        return html_entity_decode(strip_tags((string) $notificacion->toMail($this->cliente)->render()));
    }

    public function test_def_026_las_vistas_de_dos_columnas_no_se_salen_en_telefono(): void
    {
        foreach (['livewire/tienda/carrito', 'cuenta/pedidos/show', 'tienda/checkout', 'tienda/producto', 'livewire/tienda/configurador-pc', 'livewire/tienda/catalogo'] as $vista) {
            $html = file_get_contents(resource_path("views/{$vista}.blade.php"));
            $this->assertDoesNotMatchRegularExpression('/["\s]grid items-start/', $html, "La cuadrícula de {$vista} necesita grid-cols-1 para no ensancharse en teléfono.");
        }
    }

    public function test_def_027_el_reembolso_total_despues_de_un_parcial_aclara_el_total_devuelto(): void
    {
        Notification::fake();
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);
        $pago = $this->pagoTransferencia($pedido);
        app(PagoService::class)->aprobar($pago);

        $this->actingAs(User::factory()->conRol(Rol::Vendedor)->create());
        app(PagoService::class)->reembolsar($pago->refresh(), 50, 'Parcial', false);
        app(PagoService::class)->reembolsar($pago->refresh(), round((float) $pedido->total - 50, 2), 'Resto', false);

        Notification::assertSentTo($this->cliente, ReembolsoProcesado::class, fn (ReembolsoProcesado $n) => $n->esTotal
            && str_contains($this->texto($n), 'el saldo que quedaba')
            && str_contains($this->texto($n), 'En total te reembolsamos Q'.number_format((float) $pedido->total, 2)));
    }

    public function test_def_028_rechazar_un_comprobante_vencido_no_promete_otro_plazo(): void
    {
        Notification::fake();
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);
        $pago = $this->pagoTransferencia($pedido);
        $pedido->forceFill(['vence_at' => now()->subHour()])->save();

        app(PagoService::class)->rechazar($pago, 'Boleta ilegible', User::factory()->conRol(Rol::Vendedor)->create());

        Notification::assertSentTo($this->cliente, PagoRechazado::class, fn (PagoRechazado $n) => str_contains($this->texto($n), 'ya venció')
            && ! str_contains($this->texto($n), 'antes del'));
    }

    public function test_def_029_una_sola_unidad_se_escribe_en_singular(): void
    {
        $producto = $this->producto('logitech-g502-hero');
        Producto::conCambioDeInventario(fn () => $producto->forceFill(['existencia' => 1, 'reservado' => 0])->save());

        $this->get('/producto/logitech-g502-hero')->assertOk()->assertSee('¡Última unidad para entrega inmediata!')->assertDontSee('Últimas 1');
    }

    public function test_def_031_el_motivo_de_cancelacion_no_lleva_doble_punto(): void
    {
        Notification::fake();
        $pedido = $this->pedidoCon(['logitech-g502-hero' => 1]);
        app(PedidoService::class)->cancelar($pedido, 'Venció el plazo para pagar.');

        Notification::assertSentTo($this->cliente, PedidoActualizado::class, fn (PedidoActualizado $n) => str_contains($this->texto($n), 'Motivo: Venció el plazo para pagar.')
            && ! str_contains($this->texto($n), 'pagar..'));
    }

    public function test_def_032_los_plurales_no_usan_parentesis(): void
    {
        $this->pedidoCon(['logitech-g502-hero' => 1]);

        $this->actingAs($this->cliente)->get('/mi-cuenta/pedidos')->assertOk()->assertSee('1 artículo')->assertDontSee('artículo(s)');
        $this->actingAs($this->cliente)->get('/mi-cuenta/direcciones')->assertOk()->assertDontSee('día(s)');
    }

    public function test_def_034_no_dice_agregado_si_ya_no_cabe_mas_en_el_carrito(): void
    {
        $producto = $this->producto('logitech-g502-hero');
        Producto::conCambioDeInventario(fn () => $producto->forceFill(['existencia' => 2, 'reservado' => 0])->save());
        $this->actingAs($this->cliente);
        app(CarritoService::class)->agregarProducto($producto, 2);

        Livewire::test(BotonCarrito::class, ['productoId' => $producto->id, 'conCantidad' => true])
            ->call('agregar')
            ->assertSet('agregado', false)
            ->assertSee('Ya tienes en tu carrito todas las unidades disponibles');

        $this->expectException(DomainException::class);
        app(CarritoService::class)->agregarProducto($producto->refresh(), 1);
    }
}
