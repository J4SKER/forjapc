<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tareas programadas de ForjaPC (el cron del servidor ejecuta schedule:run cada minuto).
Schedule::command('forjapc:vencer-pedidos')->everyMinute()->withoutOverlapping();
Schedule::command('forjapc:tipo-cambio')->dailyAt('07:00')->timezone('America/Guatemala');
