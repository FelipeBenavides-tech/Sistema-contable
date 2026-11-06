<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Libera las horas de las reservas en línea que no se pagaron a tiempo.
// En el servidor debe correr el programador de Laravel (cron: php artisan schedule:run cada minuto).
Artisan::command('citas:liberar', function () {
    $this->info('Reservas liberadas: ' . \App\Models\Cita::liberarVencidas());
})->purpose('Libera las reservas en línea sin pagar');

\Illuminate\Support\Facades\Schedule::command('citas:liberar')->everyFiveMinutes();
