<?php

namespace App\Providers;

use App\Http\Middleware\VerificarAdmin;
use App\Http\Middleware\VerificarBarberia;
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
    }
}
