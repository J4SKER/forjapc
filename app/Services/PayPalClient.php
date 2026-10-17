<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente mínimo de la API REST de PayPal (Orders v2, reembolsos y verificación de
 * webhooks). En este proyecto se usa el modo sandbox: no se cobra dinero real.
 */
class PayPalClient
{
    public function configurado(): bool
    {
        return filled(config('services.paypal.client_id')) && filled(config('services.paypal.secret'));
    }

    public function clientId(): ?string
    {
        return config('services.paypal.client_id');
    }

    /**
     * Crea una orden de pago en dólares y devuelve su identificador.
     */
    public function crearOrden(float $montoUsd, string $referencia, string $descripcion): string
    {
        $respuesta = $this->http()->post('/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $referencia,
                'invoice_id' => $referencia,
                'description' => mb_substr($descripcion, 0, 127),
                'amount' => ['currency_code' => 'USD', 'value' => number_format($montoUsd, 2, '.', '')],
            ]],
            'payment_source' => ['paypal' => ['experience_context' => [
                'brand_name' => 'ForjaPC',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
            ]]],
        ])->throw();

        return (string) $respuesta->json('id');
    }

    /**
     * Captura una orden aprobada por el comprador.
     *
     * @return array{estado: string, captura_id: ?string, monto: float, moneda: ?string, comision: float, respuesta: array<string, mixed>}
     */
    public function capturarOrden(string $ordenId): array
    {
        try {
            $datos = $this->http()->post("/v2/checkout/orders/{$ordenId}/capture", (object) [])->throw()->json();
        } catch (RequestException $e) {
            // Si la orden ya se capturó (p. ej. desde el webhook), se consulta su estado.
            if ($e->response->json('details.0.issue') !== 'ORDER_ALREADY_CAPTURED') {
                throw $e;
            }
            $datos = $this->http()->get("/v2/checkout/orders/{$ordenId}")->throw()->json();
        }

        $captura = data_get($datos, 'purchase_units.0.payments.captures.0', []);

        return [
            'estado' => (string) data_get($captura, 'status', data_get($datos, 'status', '')),
            'captura_id' => data_get($captura, 'id'),
            'monto' => (float) data_get($captura, 'amount.value', 0),
            'moneda' => data_get($captura, 'amount.currency_code'),
            'comision' => (float) data_get($captura, 'seller_receivable_breakdown.paypal_fee.value', 0),
            'respuesta' => $datos,
        ];
    }

    /**
     * Estado actual de una orden (CREATED, APPROVED, COMPLETED, …).
     */
    public function estadoOrden(string $ordenId): string
    {
        return (string) $this->http()->get("/v2/checkout/orders/{$ordenId}")->throw()->json('status');
    }

    /**
     * Reembolsa total o parcialmente una captura y devuelve el identificador del reembolso.
     */
    public function reembolsar(string $capturaId, float $montoUsd, string $nota): string
    {
        $respuesta = $this->http()->post("/v2/payments/captures/{$capturaId}/refund", [
            'amount' => ['currency_code' => 'USD', 'value' => number_format($montoUsd, 2, '.', '')],
            'note_to_payer' => mb_substr($nota, 0, 255),
        ])->throw();

        return (string) $respuesta->json('id');
    }

    /**
     * Verifica con PayPal que una notificación de webhook es auténtica.
     *
     * @param  array<string, mixed>  $evento
     */
    public function verificarWebhook(array $cabeceras, array $evento): bool
    {
        $webhookId = config('services.paypal.webhook_id');

        if (blank($webhookId)) {
            return false;
        }

        $respuesta = $this->http()->post('/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $cabeceras['paypal-auth-algo'] ?? null,
            'cert_url' => $cabeceras['paypal-cert-url'] ?? null,
            'transmission_id' => $cabeceras['paypal-transmission-id'] ?? null,
            'transmission_sig' => $cabeceras['paypal-transmission-sig'] ?? null,
            'transmission_time' => $cabeceras['paypal-transmission-time'] ?? null,
            'webhook_id' => $webhookId,
            'webhook_event' => $evento,
        ]);

        return $respuesta->successful() && $respuesta->json('verification_status') === 'SUCCESS';
    }

    private function http(): PendingRequest
    {
        if (! $this->configurado()) {
            throw new RuntimeException('PayPal no está configurado.');
        }

        return Http::baseUrl($this->urlBase())->acceptJson()->asJson()->timeout(20)->withToken($this->token());
    }

    private function token(): string
    {
        return Cache::remember('paypal_token', now()->addMinutes(30), function () {
            return (string) Http::baseUrl($this->urlBase())
                ->asForm()
                ->withBasicAuth((string) config('services.paypal.client_id'), (string) config('services.paypal.secret'))
                ->post('/v1/oauth2/token', ['grant_type' => 'client_credentials'])
                ->throw()
                ->json('access_token');
        });
    }

    private function urlBase(): string
    {
        return config('services.paypal.modo') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }
}
