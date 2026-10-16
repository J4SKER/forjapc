<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\ParametroSistema;
use App\Services\CarritoService;
use App\Services\PayPalClient;
use App\Services\PedidoService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Checkout: entrega, facturación y forma de pago; al confirmar se crea el pedido con reserva.
 */
class CheckoutController extends Controller
{
    public function create(Request $request, CarritoService $carrito, PayPalClient $paypal): View|RedirectResponse
    {
        $resumen = $carrito->resumen();

        if ($resumen['lineas'] === [] || $resumen['hay_problemas']) {
            return redirect()->route('carrito');
        }

        $usuario = $request->user();

        return view('tienda.checkout', [
            'resumen' => $resumen,
            'usuario' => $usuario,
            'direcciones' => $usuario->direcciones()->with('departamento')->orderByDesc('es_principal')->get(),
            'paypalDisponible' => $paypal->configurado(),
            'cuentaBancaria' => ParametroSistema::valor('cuenta_bancaria_depositos'),
            'minutosPaypal' => (int) ParametroSistema::valor('minutos_vencimiento_pago_linea', 30),
            'horasTransferencia' => (int) ParametroSistema::valor('horas_vencimiento_transferencia', 48),
        ]);
    }

    public function store(CheckoutRequest $request, CarritoService $carrito, PedidoService $pedidos, PayPalClient $paypal): RedirectResponse
    {
        if ($request->input('metodo_pago') === 'PAYPAL' && ! $paypal->configurado()) {
            return back()->withInput()->withErrors(['metodo_pago' => 'El pago con PayPal no está disponible en este momento. Elige transferencia.']);
        }

        try {
            $pedido = $pedidos->crearDesdeCarrito($request->user(), $carrito->actual(crear: true), $request->safe()->except('acepto'));
        } catch (DomainException $e) {
            return redirect()->route('carrito')->with('estado', $e->getMessage());
        }

        return redirect()->route('pedidos.show', $pedido)->with('estado', "¡Listo! Registramos tu pedido {$pedido->numero}. Completa el pago para confirmarlo.");
    }
}
