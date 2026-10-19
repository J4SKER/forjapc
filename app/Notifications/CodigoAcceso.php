<?php

namespace App\Notifications;

use App\Models\ParametroSistema;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Código de verificación en dos pasos para entrar al panel de ForjaPC.
 */
class CodigoAcceso extends Notification
{
    public function __construct(public string $codigo, public int $minutos) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tu código de acceso a ForjaPC: {$this->codigo}")
            ->greeting('¡Hola, '.strtok($notifiable->nombre, ' ').'!')
            ->line('Alguien inició sesión con tu cuenta del panel de ForjaPC. Para terminar de entrar, escribe este código:')
            ->line("# {$this->codigo}")
            ->line("Vence en {$this->minutos} minutos y solo sirve una vez.")
            ->line('**Si no fuiste tú**, alguien conoce tu contraseña: cámbiala de inmediato y avisa a '.ParametroSistema::valor('correo_contacto', 'ventas@forjapc.com').'. Nunca compartas este código; nadie de ForjaPC te lo va a pedir.');
    }
}
