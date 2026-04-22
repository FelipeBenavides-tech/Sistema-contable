<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\PosVenta;
use App\Livewire\GestionBarberos;
use App\Livewire\GestionServicios;
use App\Livewire\GestionInventario;
use App\Livewire\GestionGastos;
use App\Livewire\HistorialVentas;
use App\Livewire\Reportes;
use App\Livewire\Admin\PanelAdmin;
use App\Http\Controllers\ReporteController;

// ── Panel Admin (solo para is_admin = true) ───────────────────────────────────
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/', PanelAdmin::class)->name('admin.panel');
});

// ── Panel Barbería (usuarios con barberia_id) ─────────────────────────────────
Route::middleware(['auth', 'barberia'])->group(function () {
    Route::get('/', PosVenta::class)->name('pos');
    Route::get('/pos', PosVenta::class)->name('pos');
    Route::get('/ventas', HistorialVentas::class)->name('ventas');
    Route::get('/inventario', GestionInventario::class)->name('inventario');
    Route::get('/gastos', GestionGastos::class)->name('gastos');
    Route::get('/reportes', Reportes::class)->name('reportes');
    Route::get('/barberos', GestionBarberos::class)->name('barberos');
    Route::get('/servicios', GestionServicios::class)->name('servicios');

    // Exportaciones
    Route::get('/reportes/excel/{mes}/{anio}', [ReporteController::class, 'exportarExcel'])->name('reportes.excel');
    Route::get('/reportes/pdf/{mes}/{anio}', [ReporteController::class, 'exportarPdf'])->name('reportes.pdf');
});

require __DIR__ . '/auth.php';
