<?php

namespace App\Notifications;

use App\Enums\MetodoPago;
use App\Models\ParametroSistema;
use App\Models\Pedido;
use App\Notifications\Concerns\ResumenPedido;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmación del pedido: qué se apartó, cómo pagar y hasta cuándo.
 */
class PedidoRecibido extends Notification
{
    use ResumenPedido;

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
        $limite = $pedido->vence_at->translatedFormat('j \d\e F \a \l\a\s H:i');

        $mensaje = (new MailMessage)
            ->subject("Recibimos tu pedido {$pedido->numero}")
            ->greeting('¡Hola, '.strtok($notifiable->nombre, ' ').'!')
            ->line("Registramos tu pedido **{$pedido->numero}** y **apartamos los productos a tu nombre** hasta el {$limite}. Si no recibimos el pago para entonces, el pedido se cancela solo y los productos vuelven a la venta.");

        if ($pedido->metodo_pago === MetodoPago::Transferencia) {
            $mensaje->line('**¿Cómo pago?** Transfiere o deposita **Q'.number_format((float) $pedido->total, 2).'** a: **'.ParametroSistema::valor('cuenta_bancaria_depositos')."**. Escribe el número de pedido {$pedido->numero} como referencia.")
                ->line('**¿Qué sigue?** Sube la foto del comprobante desde tu pedido. Nuestro equipo lo verifica en horario de oficina y te avisamos por correo cuando el pago quede confirmado.');
        } else {
            $mensaje->line('**¿Cómo pago?** Abre tu pedido y paga con tu cuenta PayPal o con tarjeta de débito o crédito. Los datos de la tarjeta los recibe PayPal; ForjaPC nunca los ve.')
                ->line('**¿Qué sigue?** En cuanto PayPal confirme el cobro te enviaremos un correo y empezaremos a preparar tu pedido.');
        }

        return $this->agregarResumen($mensaje, $pedido)
            ->action('Ver mi pedido y pagar', route('pedidos.show', $pedido))
            ->line($this->contacto());
    }
}
