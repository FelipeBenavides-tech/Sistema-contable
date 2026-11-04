<?php

use Illuminate\Support\Facades\Schedule;

// Respaldo diario de la base de datos (requiere el cron de Laravel en el hosting)
Schedule::command('kaixa:respaldo')->dailyAt('03:00');
