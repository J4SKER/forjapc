<?php

namespace Tests\Feature\Ventas;

use App\Enums\EstadoPedido;
use App\Livewire\Tienda\BotonCarrito;
use App\Livewire\Tienda\Carrito as CarritoLivewire;
use App\Models\Carrito;
use App\Models\Pedido;
use App\Models\User;
use App\Notifications\PedidoRecibido;
use App\Services\CarritoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class CarritoCheckoutTest extends TestCase
{
    use AyudasVentas, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararTienda();
    }

    public function test_un_visitante_agrega_productos_y_se_conservan_al_iniciar_sesion(): void
    {
        $mouse = $this->producto('logitech-g502-hero');

        Livewire::test(BotonCarrito::class, ['productoId' => $mouse->id])
            ->set('cantidad', 2)
            ->call('agregar')
            ->assertSet('agregado', true)
            ->assertDispatched('carrito-actualizado');

        $this->assertSame(2, app(CarritoService::class)->cantidadArticulos());
        $carritoVisitante = session(CarritoService::SESION);

        $this->post('/login', ['email' => $this->cliente->email, 'password' => 'password']);

        $carrito = Carrito::query()->where('usuario_id', $this->cliente->id)->firstOrFail();
        $this->assertSame(2, (int) $carrito->items()->sum('cantidad'));
        $this->assertDatabaseMissing('carritos', ['id' => $carritoVisitante]);
    }

    public function test_la_cantidad_no_supera_lo_disponible_ni_se_agrega_algo_agotado(): void
    {
        $this->actingAs($this->cliente);
        $placa = $this->producto('asus-rog-strix-z790-e-gaming-wifi-ii'); // 2 en existencia

        app(CarritoService::class)->agregarProducto($placa, 5);
        $this->assertSame(2, app(CarritoService::class)->actual()->items()->first()->cantidad);

        Livewire::test(BotonCarrito::class, ['productoId' => $this->producto('logitech-g435-lightspeed')->id])
            ->call('agregar')
            ->assertSet('agregado', false)
            ->assertSet('mensaje', fn ($mensaje) => str_contains($mensaje, 'no tiene existencia'));
    }

    public function test_quitar_y_cambiar_cantidades_en_la_pagina_del_carrito(): void
    {
        $this->actingAs($this->cliente);
        $item = app(CarritoService::class)->agregarProducto($this->producto('kingston-nv3-1tb-nvme-m2'), 1);

        Livewire::test(CarritoLivewire::class)
            ->call('cambiarCantidad', $item->id, 3)
            ->assertSee('Q1,680.00')
            ->call('quitar', $item->id)
            ->assertSee('Tu carrito está vacío');
    }

    public function test_el_checkout_crea_el_pedido_con_iva_envio_y_reserva(): void
    {
        $direccion = $this->direccionDe($this->cliente, 'Petén', 60);
        $this->actingAs($this->cliente);
        app(CarritoService::class)->agregarProducto($this->producto('logitech-g502-hero'), 2); // 2 × 395

        $this->get('/checkout')->assertOk()->assertSee('Finalizar compra');

        $this->post('/checkout', [
            'metodo_entrega' => 'ENVIO',
            'direccion_id' => $direccion->id,
            'nombre_facturacion' => 'Ana López',
            'nit' => '1234567-k',
            'telefono' => '5555 1234',
            'metodo_pago' => 'TRANSFERENCIA',
            'acepto' => '1',
        ])->assertRedirect();

        $pedido = Pedido::query()->with('items')->firstOrFail();
        $this->assertSame('FPC-'.str_pad((string) $pedido->id, 6, '0', STR_PAD_LEFT), $pedido->numero);
        $this->assertSame(EstadoPedido::PendientePago, $pedido->estado);
        $this->assertEquals(790.00, (float) $pedido->subtotal);
        $this->assertEquals(60.00, (float) $pedido->costo_envio);
        $this->assertEquals(850.00, (float) $pedido->total);
        $this->assertEquals(758.93, (float) $pedido->base_imponible);
        $this->assertEquals(91.07, (float) $pedido->iva);
        $this->assertSame('1234567-K', $pedido->nit);
        $this->assertSame(2, $this->producto('logitech-g502-hero')->reservado);
        $this->assertSame(0, app(CarritoService::class)->cantidadArticulos());
        $this->assertTrue($pedido->vence_at->between(now()->addHours(47), now()->addHours(49)));
        Notification::assertSentTo($this->cliente, PedidoRecibido::class);
    }

    public function test_el_envio_exige_una_direccion_propia(): void
    {
        $ajena = $this->direccionDe(User::factory()->create());
        $this->actingAs($this->cliente);
        app(CarritoService::class)->agregarProducto($this->producto('logitech-g502-hero'));

        $this->post('/checkout', [
            'metodo_entrega' => 'ENVIO',
            'direccion_id' => $ajena->id,
            'nombre_facturacion' => 'Ana',
            'nit' => 'CF',
            'telefono' => '55551234',
            'metodo_pago' => 'TRANSFERENCIA',
            'acepto' => '1',
        ])->assertSessionHasErrors('direccion_id');

        $this->assertSame(0, Pedido::query()->count());
    }

    public function test_una_configuracion_entra_al_carrito_como_un_solo_articulo_con_armado(): void
    {
        $pedido = $this->pedidoConPcArmada();

        $this->assertEquals(250.00, (float) $pedido->costo_armado);
        $this->assertSame(6, $pedido->items()->count());
        $this->assertSame(1, $pedido->items()->distinct()->count('grupo_configuracion'));
        $this->assertTrue((bool) $pedido->items()->first()->con_armado);
    }

    public function test_si_otro_cliente_compro_las_ultimas_unidades_no_se_crea_el_pedido(): void
    {
        $this->pedidoCon(['asus-rog-strix-z790-e-gaming-wifi-ii' => 2]); // agota las 2 unidades

        $otro = User::factory()->create();
        $this->actingAs($otro);
        $this->expectException(\DomainException::class);
        app(CarritoService::class)->agregarProducto($this->producto('asus-rog-strix-z790-e-gaming-wifi-ii'));
    }
}
