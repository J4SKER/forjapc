<?php

namespace App\Notifications;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Notifications\Concerns\ResumenPedido;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al cliente cuando su pedido cambia a un estado importante: qué pasó, por qué y qué sigue.
 */
class PedidoActualizado extends Notification
{
    use ResumenPedido;

    /**
     * @param  string|null  $detalle  Nota del equipo para el cliente (el comentario al cambiar el estado).
     */
    public function __construct(public Pedido $pedido, public ?string $detalle = null) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pedido = $this->pedido;
        [$asunto, $queOcurrio, $queSigue] = $this->textos($pedido);

        $mensaje = (new MailMessage)
            ->subject("Pedido {$pedido->numero}: {$asunto}")
            ->greeting('¡Hola, '.strtok($notifiable->nombre, ' ').'!')
            ->line($queOcurrio);

        if (filled($this->detalle)) {
            $mensaje->line("**Nota de nuestro equipo:** {$this->detalle}");
        }

        $mensaje->line("**¿Qué sigue?** {$queSigue}");

        return $this->agregarResumen($mensaje, $pedido)
            ->action('Ver mi pedido', route('pedidos.show', $pedido))
            ->line($this->contacto());
    }

    /**
     * Asunto, explicación de lo ocurrido y siguiente paso según el estado.
     *
     * @return array{string, string, string}
     */
    private function textos(Pedido $pedido): array
    {
        $metodo = $pedido->pagoAprobado?->metodo?->etiqueta() ?? $pedido->metodo_pago->etiqueta();

        return match ($pedido->estado) {
            EstadoPedido::Pagado, EstadoPedido::EnPreparacion => [
                'pago confirmado',
                "Confirmamos tu pago por **{$metodo}**. Tus productos ya están apartados a tu nombre y los estamos preparando.",
                $pedido->esEnvio()
                    ? 'Cuando el paquete esté listo te avisaremos y luego te enviaremos el número de guía de la mensajería.'
                    : 'Te avisaremos por correo en cuanto esté listo para que pases a recogerlo.',
            ],
            EstadoPedido::EnArmado => [
                'pago confirmado, en el taller',
                "Confirmamos tu pago por **{$metodo}**. Tu computadora pasó a nuestro taller: un técnico la arma, ordena el cableado y le hace pruebas de encendido, temperatura y estabilidad.",
                'Cuando las pruebas terminen te avisaremos que está lista'.($pedido->esEnvio() ? ' y la enviaremos.' : ' para recoger.'),
            ],
            EstadoPedido::ListoEntrega => $pedido->esEnvio()
                ? ['listo para envío', 'Tu pedido ya está empacado y revisado.', 'Lo entregaremos a la mensajería y te enviaremos el número de guía para que lo rastrees.']
                : ['listo para recoger', 'Tu pedido ya está empacado, revisado y esperándote en nuestra tienda.', 'Pasa a recogerlo con tu DPI y el número de pedido. Si lo recoge otra persona, que lleve una copia de tu DPI.'],
            EstadoPedido::Enviado => [
                'va en camino',
                "Entregamos tu pedido a **{$pedido->mensajeria}**. Tu número de guía es **{$pedido->numero_guia}**.",
                "Puedes rastrearlo en el sitio de {$pedido->mensajeria} con ese número. Revisa el paquete al recibirlo y avísanos si llega dañado.",
            ],
            EstadoPedido::Entregado => [
                'entregado',
                'Tu pedido fue entregado. ¡Gracias por comprar en ForjaPC!',
                'Conserva este correo como respaldo de tu garantía. Si quieres, deja tu opinión de los productos en la tienda: ayuda a otros clientes.',
            ],
            EstadoPedido::Cancelado => [
                'cancelado',
                'Tu pedido fue cancelado. **Motivo:** '.($pedido->motivo_cancelacion ?: 'no indicado').'.',
                'No se realizó ningún cobro y liberamos los productos que tenías apartados. Si todavía los quieres, puedes hacer un pedido nuevo cuando gustes.',
            ],
            default => ['actualizado', "Tu pedido ahora está «{$pedido->estado->etiqueta()}».", 'Te avisaremos por correo en cada paso.'],
        };
    }
}
