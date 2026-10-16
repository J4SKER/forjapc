<?php

namespace App\Providers;

use App\Models\OrdenArmado;
use App\Models\User;
use App\Services\CarritoService;
use Illuminate\Auth\Events\Login;
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

        // El carrito del visitante pasa al del cliente cuando inicia sesión o se registra.
        Event::listen(function (Login $evento) {
            if ($evento->user instanceof User) {
                app(CarritoService::class)->fusionar($evento->user, session()->pull(CarritoService::SESION));
            }
        });
    }
}
