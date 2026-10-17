<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\PagoService;
use App\Services\PayPalClient;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Endpoints que usan los botones de PayPal (JS SDK) y el webhook de la pasarela.
 * Los datos de la tarjeta se ingresan en la ventana de PayPal, nunca en ForjaPC.
 */
class PaypalController extends Controller
{
    public function crearOrden(Request $request, Pedido $pedido, PagoService $pagos): JsonResponse
    {
        abort_unless($pedido->usuario_id === $request->user()->id, 404);

        try {
            return response()->json(['id' => $pagos->iniciarPaypal($pedido)->referencia_externa]);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('Error al crear la orden de PayPal: '.$e->getMessage());

            return response()->json(['error' => 'No pudimos conectar con PayPal. Intenta de nuevo en un momento.'], 502);
        }
    }

    public function capturar(Request $request, Pedido $pedido, PagoService $pagos): JsonResponse
    {
        abort_unless($pedido->usuario_id === $request->user()->id, 404);
        $ordenId = (string) $request->validate(['orderID' => ['required', 'string', 'max:80']])['orderID'];

        try {
            $pagos->capturarPaypal($pedido, $ordenId);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('Error al capturar el pago de PayPal: '.$e->getMessage());

            return response()->json(['error' => 'No pudimos confirmar el pago con PayPal. Si se realizó un cobro, se aplicará automáticamente.'], 502);
        }

        session()->flash('estado', '¡Pago confirmado! Ya estamos preparando tu pedido.');

        return response()->json(['ok' => true, 'redireccion' => route('pedidos.show', $pedido)]);
    }

    /**
     * Notificaciones de PayPal: se verifica la firma y se procesan una sola vez.
     */
    public function webhook(Request $request, PayPalClient $paypal, PagoService $pagos): Response
    {
        $evento = $request->json()->all();
        $cabeceras = collect($request->headers->all())->map(fn ($valores) => $valores[0] ?? null)->all();

        if (! $paypal->configurado() || ! $paypal->verificarWebhook($cabeceras, $request->getContent())) {
            return response('Firma no válida', 400);
        }

        $pagos->procesarWebhook($evento);

        return response('OK', 200);
    }
}
