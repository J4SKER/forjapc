<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoPedido;
use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Pedido;
use App\Services\PagoService;
use App\Services\PedidoService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Bandeja de pedidos del personal: seguimiento, cambios de estado, envío y reembolsos.
 */
class PedidoController extends Controller
{
    /**
     * Estados que el personal puede asignar a mano (los demás los asignan los flujos de pago, taller y envío).
     */
    private const MANUALES = [EstadoPedido::EnPreparacion, EstadoPedido::ListoEntrega, EstadoPedido::Entregado];

    public function __construct(private PedidoService $pedidos) {}

    public function index(Request $request): View
    {
        $request->validate(['desde' => ['nullable', 'date'], 'hasta' => ['nullable', 'date']]);

        $consulta = Pedido::query()->with('usuario:id,nombre,email')
            ->when($request->filled('estado'), fn (Builder $q) => $q->where('estado', $request->string('estado')->value()))
            ->when($request->filled('desde'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->date('hasta')))
            ->when($request->filled('q'), function (Builder $q) use ($request) {
                $termino = '%'.$request->string('q')->trim()->value().'%';
                $q->where(fn (Builder $q) => $q->where('numero', 'like', $termino)
                    ->orWhere('nombre_facturacion', 'like', $termino)
                    ->orWhereHas('usuario', fn (Builder $u) => $u->where('email', 'like', $termino)->orWhere('nombre', 'like', $termino)));
            });

        return view('admin.pedidos.index', [
            'pedidos' => (clone $consulta)->latest('id')->paginate(20)->withQueryString(),
            'estados' => EstadoPedido::cases(),
            'conteos' => Pedido::query()->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado')->map(fn ($total) => (int) $total),
        ]);
    }

    public function show(Pedido $pedido): View
    {
        $pedido->load(['usuario', 'items.producto', 'historial.usuario', 'pagos.reembolsos', 'pagos.verificador', 'ordenesArmado.tecnico']);

        return view('admin.pedidos.show', [
            'pedido' => $pedido,
            'destinos' => $this->destinosManuales($pedido),
        ]);
    }

    /**
     * Estados que el personal puede asignar a mano a este pedido: no se salta el taller
     * (lo cierra la orden de armado) ni se entrega un envío sin registrar la guía.
     *
     * @return list<EstadoPedido>
     */
    private function destinosManuales(Pedido $pedido): array
    {
        return array_values(array_filter($this->pedidos->destinosPosibles($pedido), fn (EstadoPedido $e) => in_array($e, self::MANUALES, true)
            && ! ($e === EstadoPedido::Entregado && $pedido->esEnvio() && $pedido->estado === EstadoPedido::ListoEntrega)
            && ! ($e === EstadoPedido::ListoEntrega && $pedido->estado === EstadoPedido::EnArmado)));
    }

    public function cambiarEstado(Request $request, Pedido $pedido): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::enum(EstadoPedido::class)->only(self::MANUALES)],
            'comentario' => ['nullable', 'string', 'max:255'],
        ]);

        $destino = EstadoPedido::from($datos['estado']);

        if (! in_array($destino, $this->destinosManuales($pedido), true)) {
            return back()->with('estado', "No se puede pasar este pedido a «{$destino->etiqueta()}» desde aquí.");
        }

        return $this->ejecutar(fn () => $this->pedidos->cambiarEstado($pedido, $destino, $datos['comentario'] ?? null), 'Estado actualizado.');
    }

    public function envio(Request $request, Pedido $pedido): RedirectResponse
    {
        $datos = $request->validate([
            'mensajeria' => ['required', 'string', 'max:60'],
            'numero_guia' => ['required', 'string', 'max:60'],
        ], [], ['numero_guia' => 'número de guía']);

        return $this->ejecutar(fn () => $this->pedidos->registrarEnvio($pedido, $datos['mensajeria'], $datos['numero_guia']), 'Envío registrado y cliente notificado.');
    }

    public function cancelar(Request $request, Pedido $pedido): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']]);

        return $this->ejecutar(fn () => $this->pedidos->cancelar($pedido, $datos['motivo']), 'Pedido cancelado y reservas liberadas.');
    }

    public function reembolsar(Request $request, Pedido $pedido, Pago $pago, PagoService $pagos): RedirectResponse
    {
        abort_unless($pago->pedido_id === $pedido->id, 404);

        $datos = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01'],
            'motivo' => ['required', 'string', 'min:5', 'max:255'],
            'devolver_inventario' => ['boolean'],
        ]);

        return $this->ejecutar(fn () => $pagos->reembolsar($pago, (float) $datos['monto'], $datos['motivo'], $request->boolean('devolver_inventario')), 'Reembolso registrado.');
    }

    private function ejecutar(callable $accion, string $exito): RedirectResponse
    {
        try {
            $accion();
        } catch (DomainException $e) {
            return back()->withInput()->with('estado', $e->getMessage());
        }

        return back()->with('estado', $exito);
    }
}
