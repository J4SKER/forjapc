<?php

namespace App\Notifications;

use App\Models\Pago;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PagoRechazado extends Notification
{
    public function __construct(public Pago $pago) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pedido = $this->pago->pedido;

        return (new MailMessage)
            ->subject("No pudimos verificar el pago del pedido {$pedido->numero}")
            ->greeting('¡Hola, '.strtok($notifiable->nombre, ' ').'!')
            ->line('Revisamos el comprobante que subiste y no pudimos confirmarlo.')
            ->line('Motivo: '.$this->pago->motivo_rechazo)
            ->line('Puedes subir un nuevo comprobante mientras el pedido siga vigente.')
            ->action('Ver mi pedido', route('pedidos.show', $pedido));
    }
}
