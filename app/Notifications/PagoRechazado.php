<?php

namespace App\Notifications;

use App\Models\Pago;
use App\Models\Pedido;
use App\Notifications\Concerns\ResumenPedido;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de comprobante de transferencia rechazado: por qué y cómo corregirlo a tiempo.
 */
class PagoRechazado extends Notification
{
    use ResumenPedido;

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

        $mensaje = (new MailMessage)
            ->subject("No pudimos verificar el pago del pedido {$pedido->numero}")
            ->greeting('¡Hola, '.strtok($notifiable->nombre, ' ').'!')
            ->line('Revisamos el comprobante que subiste (banco '.$this->pago->banco.', boleta '.$this->pago->numero_boleta.') y no pudimos confirmar el depósito.')
            ->line("**Motivo:** {$this->pago->motivo_rechazo}")
            ->line($this->queHacer($pedido));

        return $this->agregarResumen($mensaje, $pedido)
            ->action('Subir otro comprobante', route('pedidos.show', $pedido))
            ->line($this->contacto('¿Ya hiciste el depósito y crees que es un error?'));
    }

    /**
     * Si el plazo ya venció no se puede subir otro comprobante: se indica cómo seguir.
     */
    private function queHacer(Pedido $pedido): string
    {
        if ($pedido->vence_at?->isPast()) {
            return '**¿Qué hago?** El plazo para pagar este pedido ya venció, así que no podrás subir otro comprobante y el pedido se cancelará, liberando los productos. Si todavía los quieres, haz un pedido nuevo; si ya depositaste, escríbenos para revisarlo.';
        }

        $limite = $pedido->vence_at ? ' antes del '.$pedido->vence_at->translatedFormat('j \d\e F \a \l\a\s H:i').'; después de esa hora el pedido se cancela y se liberan los productos.' : '.';

        return '**¿Qué hago?** Revisa que el monto sea el total del pedido, que la boleta sea legible y que el depósito sea a la cuenta indicada. Luego sube un comprobante nuevo desde tu pedido'.$limite;
    }
}
