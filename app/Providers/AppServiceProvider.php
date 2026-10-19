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
use App\Support\ReglasPassword;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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

        // Límites de peticiones con contador propio por acción y usuario (el «throttle:N,M»
        // de Laravel comparte un mismo contador entre todas las rutas de un usuario).
        $porAccion = fn (int $maximo, int $minutos = 1) => fn (Request $request) => Limit::perMinutes($minutos, $maximo)
            ->by(($request->route()?->getName() ?? $request->path()).'|'.($request->user()?->getAuthIdentifier() ?? $request->ip()));
        RateLimiter::for('verificar-codigo', $porAccion(10));
        RateLimiter::for('reenviar-codigo', $porAccion(3, 5));
        RateLimiter::for('confirmar-password', $porAccion(5));
        RateLimiter::for('acciones-cliente', $porAccion(10));
        RateLimiter::for('pagos-paypal', $porAccion(20));

        Password::defaults(fn () => ReglasPassword::cliente());

        // Una cuenta recién creada recibe la bienvenida; una existente que cambió su correo, la confirmación del cambio.
        VerifyEmail::toMailUsing(fn (object $usuario, string $url) => $usuario->created_at?->gt(now()->subMinutes(30))
            ? (new MailMessage)
                ->subject('Verifica tu correo en ForjaPC')
                ->greeting('¡Hola, '.strtok($usuario->nombre, ' ').'!')
                ->line('Gracias por crear tu cuenta. Confirma tu correo para empezar a comprar.')
                ->action('Verificar mi correo', $url)
                ->line('Si no creaste una cuenta en ForjaPC, puedes ignorar este mensaje.')
            : (new MailMessage)
                ->subject('Confirma tu nuevo correo en ForjaPC')
                ->greeting('¡Hola, '.strtok($usuario->nombre, ' ').'!')
                ->line('Cambiaste el correo de tu cuenta de ForjaPC a esta dirección. Confírmalo para seguir comprando y recibir los avisos de tus pedidos.')
                ->action('Confirmar mi nuevo correo', $url)
                ->line('Si no hiciste este cambio, no hagas clic y escríbenos de inmediato.'));

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
