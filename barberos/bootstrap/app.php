<?php

use App\Http\Middleware\VerificarAdmin;
use App\Http\Middleware\VerificarBarberia;
use App\Http\Middleware\VerificarDueno;
use App\Http\Middleware\VerificarPro;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Wompi no envía token CSRF: sus avisos se validan con la firma del evento
        $middleware->validateCsrfTokens(except: ['webhooks/wompi']);

        $middleware->alias([
            'barberia' => VerificarBarberia::class,
            'admin'    => VerificarAdmin::class,
            'dueno'    => VerificarDueno::class,
            'pro'      => VerificarPro::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
