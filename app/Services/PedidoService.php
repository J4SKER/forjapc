<?php

namespace App\Services;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Enums\TipoMovimiento;
use App\Models\Carrito;
use App\Models\Departamento;
use App\Models\Direccion;
use App\Models\Pago;
use App\Models\ParametroSistema;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Notifications\PedidoActualizado;
use App\Notifications\PedidoRecibido;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Ciclo de vida del pedido: creación desde el carrito con reserva de inventario,
 * cambios de estado solo por transiciones válidas, cancelación y vencimiento.
 */
class PedidoService
{
    /**
     * Estados que se notifican por correo al cliente.
     */
    private const ESTADOS_NOTIFICADOS = [
        EstadoPedido::ListoEntrega, EstadoPedido::Enviado, EstadoPedido::Entregado,
        EstadoPedido::Cancelado, EstadoPedido::Reembolsado,
    ];

    public function __construct(
        private CarritoService $carrito,
        private InventarioService $inventario,
        private TallerService $taller,
    ) {}

    /**
     * Convierte el carrito en un pedido y reserva las unidades para que nadie más las compre.
     *
     * @param  array{metodo_entrega: string, direccion_id?: int|null, nombre_facturacion: string, nit: string, telefono: string, metodo_pago: string, notas?: string|null}  $datos
     */
    public function crearDesdeCarrito(User $cliente, Carrito $carrito, array $datos): Pedido
    {
        $resumen = $this->carrito->resumen($carrito);

        if ($resumen['lineas'] === []) {
            throw new DomainException('Tu carrito está vacío.');
        }

        if ($resumen['hay_problemas']) {
            throw new DomainException('Hay artículos sin existencia suficiente o que ya no están a la venta. Revisa tu carrito.');
        }

        [$departamento, $direccion] = $this->destino($cliente, $datos);
        $metodoPago = MetodoPago::from($datos['metodo_pago']);

        return DB::transaction(function () use ($cliente, $carrito, $datos, $resumen, $departamento, $direccion, $metodoPago) {
            $costoEnvio = $departamento ? (float) $departamento->tarifa_envio : 0.0;
            $total = round($resumen['subtotal'] + $costoEnvio + $resumen['armado'], 2);
            $base = round($total / (1 + ParametroSistema::valor('iva_porcentaje', 12) / 100), 2);

            $pedido = new Pedido;
            $pedido->forceFill([
                'numero' => 'TMP-'.uniqid(),
                'usuario_id' => $cliente->id,
                'estado' => EstadoPedido::PendientePago,
                'metodo_entrega' => $datos['metodo_entrega'],
                'departamento_id' => $departamento?->id,
                'direccion_envio' => $direccion,
                'nombre_facturacion' => $datos['nombre_facturacion'],
                'nit' => strtoupper($datos['nit'] ?: 'CF'),
                'telefono' => $datos['telefono'],
                'metodo_pago' => $metodoPago,
                'subtotal' => $resumen['subtotal'],
                'costo_envio' => $costoEnvio,
                'costo_armado' => $resumen['armado'],
                'total' => $total,
                'base_imponible' => $base,
                'iva' => round($total - $base, 2),
                'notas' => $datos['notas'] ?? null,
                'vence_at' => now()->addMinutes($metodoPago->minutosParaPagar()),
            ])->save();

            $pedido->forceFill(['numero' => sprintf('FPC-%06d', $pedido->id)])->save();

            $this->crearLineas($pedido, $resumen['lineas']);
            $this->reservar($pedido);
            $this->registrarHistorial($pedido, null, EstadoPedido::PendientePago, 'Pedido creado desde la tienda.');
            $this->carrito->vaciar($carrito);

            DB::afterCommit(fn () => $cliente->notify(new PedidoRecibido($pedido)));

            return $pedido;
        });
    }

    /**
     * Cambia el estado si la transición está permitida y guarda quién lo hizo.
     */
    public function cambiarEstado(Pedido $pedido, EstadoPedido $destino, ?string $comentario = null, bool $notificar = true): Pedido
    {
        return DB::transaction(function () use ($pedido, $destino, $comentario, $notificar) {
            $fila = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);
            $origen = $fila->estado;

            if (! $this->transicionPermitida($origen, $destino)) {
                throw new DomainException("No se puede pasar un pedido de «{$origen->etiqueta()}» a «{$destino->etiqueta()}».");
            }

            $marcas = match ($destino) {
                EstadoPedido::Pagado => ['pagado_at' => now()],
                EstadoPedido::Entregado => ['entregado_at' => now()],
                EstadoPedido::Cancelado => ['cancelado_at' => now()],
                default => [],
            };

            $fila->forceFill(['estado' => $destino, ...$marcas])->save();
            $this->registrarHistorial($fila, $origen, $destino, $comentario);
            $pedido->setRawAttributes($fila->getAttributes(), true);

            if ($notificar && in_array($destino, self::ESTADOS_NOTIFICADOS, true)) {
                DB::afterCommit(fn () => $fila->usuario->notify(new PedidoActualizado($fila)));
            }

            return $pedido;
        });
    }

    /**
     * Cancela un pedido pendiente de pago y libera las unidades reservadas.
     */
    public function cancelar(Pedido $pedido, string $motivo): Pedido
    {
        return DB::transaction(function () use ($pedido, $motivo) {
            $fila = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);

            if (! $fila->estaPendienteDePago()) {
                throw new DomainException('Solo se cancelan pedidos pendientes de pago; uno pagado se reembolsa.');
            }

            $this->liberarReservas($fila);
            $fila->forceFill(['motivo_cancelacion' => $motivo])->save();

            // Las órdenes de PayPal sin cobrar ya no quedan «por verificar».
            $fila->pagos()->where('metodo', MetodoPago::Paypal)->where('estado', EstadoPago::Pendiente)
                ->update(['estado' => EstadoPago::Rechazado->value, 'motivo_rechazo' => 'Pedido cancelado sin completar el pago.', 'updated_at' => now()]);

            return $this->cambiarEstado($pedido, EstadoPedido::Cancelado, $motivo);
        });
    }

    /**
     * Se llama al aprobarse el pago: la mercadería sale del kardex (desde la reserva) y el pedido
     * pasa al taller si incluye armado o a preparación.
     */
    public function registrarPago(Pedido $pedido, Pago $pago): Pedido
    {
        return DB::transaction(function () use ($pedido, $pago) {
            $this->cambiarEstado($pedido, EstadoPedido::Pagado, "Pago {$pago->metodo->etiqueta()} aprobado.", notificar: false);

            foreach ($pedido->items()->with('producto')->get() as $item) {
                $movimiento = $this->inventario->salida($item->producto, $item->cantidad, TipoMovimiento::SalidaVenta, $pedido, "Pedido {$pedido->numero}", desdeReserva: true);
                $item->forceFill(['costo_unitario' => $movimiento->costo_unitario])->save();
            }

            $ordenes = $this->taller->crearOrdenes($pedido);
            $siguiente = $ordenes > 0 ? EstadoPedido::EnArmado : EstadoPedido::EnPreparacion;
            $this->cambiarEstado($pedido, $siguiente, $ordenes > 0 ? "Se generaron {$ordenes} orden(es) de armado." : null, notificar: false);

            DB::afterCommit(fn () => $pedido->usuario->notify(new PedidoActualizado($pedido)));

            return $pedido;
        });
    }

    public function registrarEnvio(Pedido $pedido, string $mensajeria, string $guia): Pedido
    {
        if (! $pedido->esEnvio()) {
            throw new DomainException('Este pedido se recoge en tienda.');
        }

        $pedido->forceFill(['mensajeria' => $mensajeria, 'numero_guia' => $guia])->save();

        return $this->cambiarEstado($pedido, EstadoPedido::Enviado, "Enviado con {$mensajeria}, guía {$guia}.");
    }

    /**
     * Cancela los pedidos cuyo plazo de pago venció (salvo los que tienen un comprobante por verificar).
     */
    public function vencerPendientes(): int
    {
        $vencidos = Pedido::query()
            ->where('estado', EstadoPedido::PendientePago)
            ->where('vence_at', '<', now())
            ->get()
            ->reject(fn (Pedido $pedido) => $pedido->tienePagoPorVerificar());

        foreach ($vencidos as $pedido) {
            $this->cancelar($pedido, 'Venció el plazo para pagar.');
        }

        return $vencidos->count();
    }

    public function transicionPermitida(EstadoPedido $origen, EstadoPedido $destino): bool
    {
        return DB::table('transiciones_estado_pedido')
            ->where('estado_origen', $origen->value)
            ->where('estado_destino', $destino->value)
            ->exists();
    }

    /**
     * @return list<EstadoPedido>
     */
    public function destinosPosibles(Pedido $pedido): array
    {
        return DB::table('transiciones_estado_pedido')
            ->where('estado_origen', $pedido->estado->value)
            ->pluck('estado_destino')
            ->map(fn (string $estado) => EstadoPedido::from($estado))
            ->all();
    }

    /**
     * Libera las unidades apartadas por un pedido pendiente.
     */
    public function liberarReservas(Pedido $pedido): void
    {
        foreach ($this->cantidadesPorProducto($pedido) as $productoId => $cantidad) {
            $this->inventario->liberarReserva(Producto::withTrashed()->findOrFail($productoId), $cantidad);
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array{0: ?Departamento, 1: ?array<string, mixed>}
     */
    private function destino(User $cliente, array $datos): array
    {
        if ($datos['metodo_entrega'] !== 'ENVIO') {
            return [null, null];
        }

        $direccion = Direccion::query()->with('departamento')
            ->where('usuario_id', $cliente->id)
            ->findOrFail($datos['direccion_id'] ?? 0);

        return [$direccion->departamento, [
            'nombre_receptor' => $direccion->nombre_receptor,
            'telefono' => $direccion->telefono,
            'departamento' => $direccion->departamento->nombre,
            'municipio' => $direccion->municipio,
            'direccion' => $direccion->direccion,
            'referencias' => $direccion->referencias,
        ]];
    }

    /**
     * @param  list<array<string, mixed>>  $lineas
     */
    private function crearLineas(Pedido $pedido, array $lineas): void
    {
        $grupo = 0;

        foreach ($lineas as $linea) {
            if ($linea['tipo'] === 'PRODUCTO') {
                $this->crearLinea($pedido, $linea['producto'], $linea['cantidad']);

                continue;
            }

            $grupo++;
            foreach ($linea['componentes'] as $componente) {
                $this->crearLinea($pedido, $componente['producto'], $componente['cantidad'], "CFG-{$grupo}", $linea['descripcion'], $linea['armado'] > 0);
            }
        }
    }

    private function crearLinea(Pedido $pedido, Producto $producto, int $cantidad, ?string $grupo = null, ?string $nombreConfiguracion = null, bool $conArmado = false): void
    {
        $pedido->items()->create([
            'producto_id' => $producto->id,
            'descripcion' => mb_substr($producto->nombre, 0, 180),
            'grupo_configuracion' => $grupo,
            'nombre_configuracion' => $nombreConfiguracion ? mb_substr($nombreConfiguracion, 0, 120) : null,
            'con_armado' => $conArmado,
            'cantidad' => $cantidad,
            'precio_unitario' => $producto->precio,
            'subtotal' => round((float) $producto->precio * $cantidad, 2),
        ]);
    }

    private function reservar(Pedido $pedido): void
    {
        // Se reserva en orden de id para evitar bloqueos cruzados entre pedidos simultáneos.
        foreach ($this->cantidadesPorProducto($pedido) as $productoId => $cantidad) {
            $this->inventario->reservar(Producto::findOrFail($productoId), $cantidad);
        }
    }

    /**
     * @return array<int, int>
     */
    private function cantidadesPorProducto(Pedido $pedido): array
    {
        return $pedido->items()->selectRaw('producto_id, SUM(cantidad) as total')
            ->groupBy('producto_id')->orderBy('producto_id')
            ->pluck('total', 'producto_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    private function registrarHistorial(Pedido $pedido, ?EstadoPedido $anterior, EstadoPedido $nuevo, ?string $comentario): void
    {
        $pedido->historial()->create([
            'estado_anterior' => $anterior,
            'estado_nuevo' => $nuevo,
            'usuario_id' => Auth::id(),
            'comentario' => $comentario,
        ]);
    }
}
