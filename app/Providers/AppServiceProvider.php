<?php

namespace App\Providers;

use App\Events\AjusteInventarioRegistrado;
use App\Events\CompraRecibida;
use App\Events\PagoAprobado;
use App\Events\ReembolsoRegistrado;
use App\Models\OrdenArmado;
use App\Models\User;
use App\Services\CarritoService;
use App\Services\ContabilidadService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::model('orden', OrdenArmado::class);

        VerifyEmail::toMailUsing(fn (object $usuario, string $url) => (new MailMessage)
            ->subject('Verifica tu correo en ForjaPC')
            ->greeting('¡Hola, '.strtok($usuario->nombre, ' ').'!')
            ->line('Gracias por crear tu cuenta. Confirma tu correo para empezar a comprar.')
            ->action('Verificar mi correo', $url)
            ->line('Si no creaste una cuenta en ForjaPC, puedes ignorar este mensaje.'));

        ResetPassword::toMailUsing(fn (object $usuario, string $token) => (new MailMessage)
            ->subject('Restablece tu contraseña de ForjaPC')
            ->greeting('¡Hola, '.strtok($usuario->nombre, ' ').'!')
            ->line('Recibimos una solicitud para cambiar la contraseña de tu cuenta.')
            ->action('Crear nueva contraseña', url(route('password.reset', ['token' => $token, 'email' => $usuario->getEmailForPasswordReset()], false)))
            ->line('El enlace vence en '.config('auth.passwords.users.expire').' minutos y solo se puede usar una vez.')
            ->line('Si no lo pediste, ignora este correo: tu contraseña no cambiará.'));

        // Contabilidad automática: cada evento registra su partida dentro de la misma transacción.
        Event::listen(fn (CompraRecibida $e) => app(ContabilidadService::class)->porCompra($e->compra));
        Event::listen(fn (PagoAprobado $e) => app(ContabilidadService::class)->porVenta($e->pago));
        Event::listen(fn (ReembolsoRegistrado $e) => app(ContabilidadService::class)->porReembolso($e->reembolso));
        Event::listen(fn (AjusteInventarioRegistrado $e) => app(ContabilidadService::class)->porAjuste($e->movimiento));

        // El carrito del visitante pasa al del cliente cuando inicia sesión o se registra.
        Event::listen(function (Login $evento) {
            if ($evento->user instanceof User) {
                app(CarritoService::class)->fusionar($evento->user, session()->pull(CarritoService::SESION));
            }
        });
    }
}
