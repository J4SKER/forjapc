<?php

namespace App\Notifications;

use App\Models\ParametroSistema;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de seguridad al dueño de una cuenta cuando alguien del personal cambia su rol,
 * su correo o su estado. Si no reconoce el cambio, sabe a quién avisar.
 */
class CuentaModificada extends Notification
{
    /**
     * @param  list<string>  $cambios  Descripciones legibles de lo que cambió.
     */
    public function __construct(public array $cambios, public string $realizadoPor) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mensaje = (new MailMessage)
            ->subject('Aviso de seguridad: cambios en tu cuenta de ForjaPC')
            ->greeting('¡Hola!')
            ->line("{$this->realizadoPor} hizo estos cambios en tu cuenta de ForjaPC el ".now()->translatedFormat('j \d\e F \a \l\a\s H:i').':');

        foreach ($this->cambios as $cambio) {
            $mensaje->line("• {$cambio}");
        }

        return $mensaje
            ->line('**Si no reconoces estos cambios**, escríbenos de inmediato a '.ParametroSistema::valor('correo_contacto', 'ventas@forjapc.com').' para revisar tu cuenta.')
            ->line('Este es un aviso automático; no necesitas hacer nada si estabas enterado.');
    }
}
