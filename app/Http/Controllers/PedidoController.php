<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\Pago;
use App\Models\ParametroSistema;
use App\Models\Pedido;
use App\Services\PagoService;
use App\Services\PayPalClient;
use App\Services\PedidoService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Mis pedidos" del cliente: seguimiento, pago y cancelación.
 */
class PedidoController extends Controller
{
    public function index(Request $request): View
    {
        return view('cuenta.pedidos.index', [
            'pedidos' => $request->user()->pedidos()->withCount('items')->latest('id')->paginate(10),
        ]);
    }

    public function show(Request $request, Pedido $pedido, PayPalClient $paypal): View
    {
        $this->asegurarDueno($request, $pedido);

        return view('cuenta.pedidos.show', [
            'pedido' => $pedido->load(['items.producto', 'historial', 'pagos', 'departamento', 'ordenesArmado']),
            'paypalClientId' => $paypal->configurado() ? $paypal->clientId() : null,
            'cuentaBancaria' => ParametroSistema::valor('cuenta_bancaria_depositos'),
        ]);
    }

    public function cancelar(Request $request, Pedido $pedido, PedidoService $pedidos): RedirectResponse
    {
        $this->asegurarDueno($request, $pedido);

        try {
            $pedidos->cancelar($pedido, 'Cancelado por el cliente.');
        } catch (DomainException $e) {
            return back()->with('estado', $e->getMessage());
        }

        return back()->with('estado', 'Pedido cancelado. Las unidades apartadas se liberaron.');
    }

    public function transferencia(Request $request, Pedido $pedido, PagoService $pagos): RedirectResponse
    {
        $this->asegurarDueno($request, $pedido);

        $datos = $request->validate([
            'banco' => ['required', 'string', 'max:60'],
            'numero_boleta' => ['required', 'string', 'max:60'],
            'monto' => ['required', 'numeric', 'min:1', 'max:999999'],
            'comprobante' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ], [], ['numero_boleta' => 'número de boleta o referencia', 'comprobante' => 'comprobante']);

        try {
            $pagos->registrarTransferencia($pedido, $datos['banco'], $datos['numero_boleta'], (float) $datos['monto'], $request->file('comprobante'));
        } catch (DomainException $e) {
            return back()->with('estado', $e->getMessage());
        }

        return back()->with('estado', 'Recibimos tu comprobante. Te avisaremos por correo cuando verifiquemos el pago.');
    }

    /**
     * Muestra el comprobante a su dueño o al personal de ventas.
     */
    public function comprobante(Request $request, Pago $pago): StreamedResponse
    {
        $usuario = $request->user();
        abort_unless($pago->pedido->usuario_id === $usuario->id || $usuario->tieneRolEfectivo(Rol::Admin, Rol::Vendedor, Rol::Contador), 403);
        abort_if($pago->comprobante_ruta === null || ! Storage::disk('local')->exists($pago->comprobante_ruta), 404);

        return Storage::disk('local')->response($pago->comprobante_ruta);
    }

    private function asegurarDueno(Request $request, Pedido $pedido): void
    {
        abort_unless($pedido->usuario_id === $request->user()->id, 404);
    }
}
