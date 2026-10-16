<?php

namespace App\Notifications;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al cliente cuando su pedido cambia a un estado importante.
 */
class PedidoActualizado extends Notification
{
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
        $texto = match ($pedido->estado) {
            EstadoPedido::Pagado => 'Confirmamos tu pago. Ya estamos preparando tu pedido.',
            EstadoPedido::EnArmado => 'Confirmamos tu pago y nuestro taller ya está armando tu computadora.',
            EstadoPedido::EnPreparacion => 'Confirmamos tu pago y estamos preparando tu pedido.',
            EstadoPedido::ListoEntrega => $pedido->esEnvio()
                ? 'Tu pedido está listo y pronto lo entregaremos a la empresa de mensajería.'
                : 'Tu pedido está listo para que lo recojas en nuestra tienda.',
            EstadoPedido::Enviado => "Tu pedido va en camino con {$pedido->mensajeria}. Número de guía: {$pedido->numero_guia}.",
            EstadoPedido::Entregado => 'Tu pedido fue entregado. ¡Esperamos que lo disfrutes! Puedes dejar tu opinión de los productos en la tienda.',
            EstadoPedido::Cancelado => 'Tu pedido fue cancelado. '.($pedido->motivo_cancelacion ?? ''),
            EstadoPedido::Reembolsado => 'Procesamos el reembolso de tu pedido.',
            default => 'Tu pedido cambió de estado.',
        };

        $mensaje = (new MailMessage)
            ->subject("Pedido {$pedido->numero}: {$pedido->estado->etiqueta()}")
            ->greeting('¡Hola, '.strtok($notifiable->nombre, ' ').'!')
            ->line($texto);

        if ($this->detalle) {
            $mensaje->line($this->detalle);
        }

        return $mensaje->action('Ver mi pedido', route('pedidos.show', $pedido));
    }
}
