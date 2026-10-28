<?php

namespace App\Providers;

use App\Http\Middleware\VerificarAdmin;
use App\Http\Middleware\VerificarBarberia;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        // Las acciones de Livewire (botones, formularios) pasan por los mismos
        // controles de acceso que la página donde se cargó el componente.
        Livewire::addPersistentMiddleware([
            VerificarAdmin::class,
            VerificarBarberia::class,
        ]);

        // Detrás del proxy del hosting, para saber la IP real y si llegó por HTTPS
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // Si la dirección pública es https, todos los enlaces se generan con https
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceHttps();
        }
    }
}
