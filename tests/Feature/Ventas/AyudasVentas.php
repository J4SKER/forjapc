<?php

namespace Tests\Feature\Ventas;

use App\Enums\EstadoPago;
use App\Models\Departamento;
use App\Models\Direccion;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Services\CarritoService;
use App\Services\PedidoService;
use App\Support\Configurador\Seleccion;
use Database\Seeders\CatalogoSeeder;
use Database\Seeders\ParametroSistemaSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

/**
 * Ayudas compartidas por las pruebas de carrito, checkout, pagos y taller.
 */
trait AyudasVentas
{
    protected User $cliente;

    protected function prepararTienda(): void
    {
        Notification::fake();
        $this->seed([ParametroSistemaSeeder::class, CatalogoSeeder::class]);
        $this->cliente = User::factory()->create();
    }

    protected function producto(string $slug): Producto
    {
        return Producto::query()->where('slug', $slug)->firstOrFail();
    }

    protected function direccionDe(User $usuario, string $departamento = 'Guatemala', float $tarifa = 25): Direccion
    {
        $depto = Departamento::query()->firstOrCreate(['nombre' => $departamento], ['tarifa_envio' => $tarifa]);

        return Direccion::factory()->for($usuario, 'usuario')->create(['departamento_id' => $depto->id]);
    }

    /**
     * Crea un pedido con los productos indicados (slug => cantidad) como si el cliente pasara por el checkout.
     *
     * @param  array<string, int>  $productos
     * @param  array<string, mixed>  $datos
     */
    protected function pedidoCon(array $productos, array $datos = [], ?User $cliente = null): Pedido
    {
        $cliente ??= $this->cliente;
        Auth::login($cliente);
        $carrito = app(CarritoService::class);

        foreach ($productos as $slug => $cantidad) {
            $carrito->agregarProducto($this->producto($slug), $cantidad);
        }

        return app(PedidoService::class)->crearDesdeCarrito($cliente, $carrito->actual(), array_merge([
            'metodo_entrega' => 'RECOGER',
            'nombre_facturacion' => $cliente->nombre,
            'nit' => 'CF',
            'telefono' => '55551234',
            'metodo_pago' => 'TRANSFERENCIA',
        ], $datos));
    }

    /**
     * Pedido con una PC armada (configuración válida con servicio de armado).
     */
    protected function pedidoConPcArmada(): Pedido
    {
        Auth::login($this->cliente);
        $ids = [];
        foreach ([
            'amd-ryzen-5-7600', 'msi-b650-gaming-plus-wifi', 'kingston-fury-beast-ddr5-32gb-2x16-6000mhz',
            'samsung-990-pro-1tb-nvme-m2', 'nzxt-h5-flow', 'msi-mag-a750gl-pcie5-750w-80-plus-gold',
        ] as $slug) {
            $producto = $this->producto($slug)->load('categoria');
            $ids[$producto->tipoComponente()->value][$producto->id] = 1;
        }

        $carrito = app(CarritoService::class);
        $carrito->agregarConfiguracion(Seleccion::desde($ids), 'PC de prueba', true);

        return app(PedidoService::class)->crearDesdeCarrito($this->cliente, $carrito->actual(), [
            'metodo_entrega' => 'RECOGER',
            'nombre_facturacion' => $this->cliente->nombre,
            'nit' => 'CF',
            'telefono' => '55551234',
            'metodo_pago' => 'TRANSFERENCIA',
        ]);
    }

    protected function pagoTransferencia(Pedido $pedido): Pago
    {
        $pago = new Pago;
        $pago->forceFill([
            'pedido_id' => $pedido->id,
            'metodo' => 'TRANSFERENCIA',
            'estado' => EstadoPago::Pendiente,
            'monto' => $pedido->total,
            'banco' => 'Banco Industrial',
            'numero_boleta' => '12345',
            'comprobante_ruta' => 'comprobantes/prueba.jpg',
        ])->save();

        return $pago;
    }
}
