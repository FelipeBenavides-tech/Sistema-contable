<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\PosVenta;
use App\Livewire\GestionAgenda;
use App\Livewire\GestionBarberos;
use App\Livewire\GestionCartera;
use App\Livewire\GestionClientes;
use App\Livewire\GestionServicios;
use App\Livewire\GestionInventario;
use App\Livewire\GestionGastos;
use App\Livewire\GestionMembresias;
use App\Livewire\HistorialVentas;
use App\Livewire\MiAgenda;
use App\Livewire\MiPlan;
use App\Livewire\Reportes;
use App\Livewire\MiBarberia;
use App\Livewire\Admin\PanelAdmin;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\WompiWebhookController;
use App\Livewire\ReservaEnLinea;
use App\Livewire\VerReserva;
use App\Livewire\PanelCliente;

// ── Reservas en línea (públicas, plan Pro) ────────────────────────────────────
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/reservar/{slug}', ReservaEnLinea::class)->name('reservar');
    Route::get('/reserva/{token}', VerReserva::class)->name('reserva.ver');
    Route::get('/mis-citas/{token}', PanelCliente::class)->name('cliente.panel');
});
Route::post('/webhooks/wompi', WompiWebhookController::class)->middleware('throttle:120,1')->name('webhooks.wompi');

// ── Panel Admin (solo para is_admin = true) ───────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', PanelAdmin::class)->name('admin.panel');
});

// ── Barbero: solo su agenda (plan Pro) ────────────────────────────────────────
Route::middleware(['auth', 'barberia', 'pro'])->group(function () {
    Route::get('/mi-agenda', MiAgenda::class)->name('mi-agenda');
});

// ── Panel Barbería (dueño de la barbería) ─────────────────────────────────────
Route::middleware(['auth', 'barberia', 'dueno'])->group(function () {
    Route::redirect('/', '/pos');

    // Caja y contabilidad: no aplican a los negocios de "solo agendamiento"
    Route::middleware('caja')->group(function () {
        Route::get('/pos', PosVenta::class)->name('pos');
        Route::get('/ventas', HistorialVentas::class)->name('ventas');
        Route::get('/cartera', GestionCartera::class)->name('cartera');
        Route::get('/membresias', GestionMembresias::class)->name('membresias');
        Route::get('/inventario', GestionInventario::class)->name('inventario');
        Route::get('/gastos', GestionGastos::class)->name('gastos');
        Route::get('/reportes', Reportes::class)->name('reportes');

        // Exportaciones
        Route::get('/reportes/excel/{mes}/{anio}', [ReporteController::class, 'exportarExcel'])
            ->where(['mes' => '[1-9]|1[0-2]', 'anio' => '20[0-9]{2}'])
            ->name('reportes.excel');
        Route::get('/reportes/pdf/{mes}/{anio}', [ReporteController::class, 'exportarPdf'])
            ->where(['mes' => '[1-9]|1[0-2]', 'anio' => '20[0-9]{2}'])
            ->name('reportes.pdf');
    });

    Route::get('/clientes', GestionClientes::class)->name('clientes');
    Route::get('/servicios', GestionServicios::class)->name('servicios');
    Route::get('/mi-plan', MiPlan::class)->name('mi-plan');

    // Barbería: "Barberos" y "Mi barbería". Salón y solo agenda: "Profesionales" y "Mi negocio".
    // Son las mismas pantallas; cambian las palabras y los colores según el tipo de negocio.
    Route::get('/barberos', GestionBarberos::class)->name('barberos');
    Route::get('/profesionales', GestionBarberos::class)->name('profesionales');
    Route::get('/mi-barberia', MiBarberia::class)->name('mi-barberia');
    Route::get('/mi-negocio', MiBarberia::class)->name('mi-negocio');

    // Agenda de citas (plan Pro)
    Route::get('/agenda', GestionAgenda::class)->middleware('pro')->name('agenda');
});

require __DIR__ . '/auth.php';
