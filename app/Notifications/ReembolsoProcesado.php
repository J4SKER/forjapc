<?php

namespace App\Notifications;

use App\Enums\MetodoPago;
use App\Models\Reembolso;
use App\Notifications\Concerns\ResumenPedido;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de reembolso total o parcial: cuánto, por qué, por dónde vuelve el dinero y qué pasa con el pedido.
 */
class ReembolsoProcesado extends Notification
{
    use ResumenPedido;

    public function __construct(public Reembolso $reembolso, public bool $esTotal) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pago = $this->reembolso->pago;
        $pedido = $pago->pedido;
        $monto = 'Q'.number_format((float) $this->reembolso->monto, 2);

        $mensaje = (new MailMessage)
            ->subject(($this->esTotal ? 'Reembolsamos tu pedido ' : 'Reembolso parcial de tu pedido ').$pedido->numero)
            ->greeting('¡Hola, '.strtok($notifiable->nombre, ' ').'!')
            ->line($this->esTotal
                ? "Te devolvimos **{$monto}**, el total que pagaste por el pedido **{$pedido->numero}**."
                : "Te devolvimos **{$monto}** del pedido **{$pedido->numero}**.")
            ->line("**Motivo:** {$this->reembolso->motivo}");

        if ($pago->metodo === MetodoPago::Paypal) {
            $usd = number_format(round((float) $this->reembolso->monto / (float) $pago->tipo_cambio, 2), 2);
            $mensaje->line("**¿Dónde recibo el dinero?** Vuelve por PayPal al mismo medio con el que pagaste (US\${$usd}). En tu cuenta PayPal se refleja de inmediato; si pagaste con tarjeta, tu banco puede tardar algunos días hábiles en mostrarlo.");
        } else {
            $mensaje->line('**¿Dónde recibo el dinero?** Como pagaste por transferencia, nuestro equipo de ventas te contactará para confirmar la cuenta bancaria donde te lo depositaremos.');
        }

        if (! $this->esTotal) {
            $mensaje->line('Tu pedido sigue activo; el reembolso solo cubre la parte indicada.');
        } else {
            $mensaje->line('Con esto tu pedido queda cerrado como **reembolsado**.'.($this->reembolso->devuelve_inventario ? ' Ya registramos la devolución de los productos.' : ''));
        }

        return $this->agregarResumen($mensaje, $pedido)
            ->action('Ver mi pedido', route('pedidos.show', $pedido))
            ->line($this->contacto('¿Dudas sobre este reembolso?'));
    }
}
