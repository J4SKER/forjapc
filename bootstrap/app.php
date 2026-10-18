<?php

use App\Http\Middleware\AsegurarRol;
use App\Http\Middleware\CabecerasSeguridad;
use App\Http\Middleware\ConfirmarPassword;
use App\Http\Middleware\ExigirCambioPassword;
use App\Http\Middleware\ModoMantenimiento;
use App\Http\Middleware\SanearEntrada;
use App\Http\Middleware\VerificarCuentaActiva;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rol' => AsegurarRol::class,
            'confirmar.password' => ConfirmarPassword::class,
        ]);

        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        // Solo se aceptan peticiones dirigidas al dominio de APP_URL (y sus subdominios): así
        // nadie puede hacer que los enlaces de los correos apunten a otro dominio (Host falso).
        $middleware->trustHosts();

        $middleware->append(CabecerasSeguridad::class);

        $middleware->web(prepend: [
            SanearEntrada::class,
        ]);

        $middleware->web(append: [
            AuthenticateSession::class,
            VerificarCuentaActiva::class,
            ExigirCambioPassword::class,
            ModoMantenimiento::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
