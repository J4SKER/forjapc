<?php

namespace App\Notifications;

use App\Enums\MetodoPago;
use App\Models\ParametroSistema;
use App\Models\Pedido;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PedidoRecibido extends Notification
{
    public function __construct(public Pedido $pedido) {}

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
        $mensaje = (new MailMessage)
            ->subject("Recibimos tu pedido {$pedido->numero}")
            ->greeting('¡Hola, '.strtok($notifiable->nombre, ' ').'!')
            ->line("Registramos tu pedido **{$pedido->numero}** por **Q".number_format($pedido->total, 2).'**.');

        if ($pedido->metodo_pago === MetodoPago::Transferencia) {
            $mensaje->line('Realiza tu transferencia o depósito a: **'.ParametroSistema::valor('cuenta_bancaria_depositos').'**')
                ->line('Luego sube la foto del comprobante desde el detalle del pedido. Tienes hasta el '.$pedido->vence_at->translatedFormat('j \d\e F \a \l\a\s H:i').'.');
        } else {
            $mensaje->line('Completa el pago con PayPal antes de las '.$pedido->vence_at->format('H:i').' para conservar las unidades apartadas.');
        }

        return $mensaje->action('Ver mi pedido', route('pedidos.show', $pedido))
            ->line('¡Gracias por comprar en ForjaPC!');
    }
}
