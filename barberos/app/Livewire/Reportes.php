<?php

namespace App\Livewire;

use App\Models\Barbero;
use App\Models\Gasto;
use App\Models\VentaItem;
use App\Models\Venta;
use Livewire\Component;

class Reportes extends Component
{
    public int $mes  = 0;
    public int $anio = 0;

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function mount(): void
    {
        $this->mes  = now()->month;
        $this->anio = now()->year;
    }

    public function updated(): void
    {
        $this->mes  = min(12, max(1, $this->mes));
        $this->anio = min(now()->year + 1, max(2020, $this->anio));
    }

    public function render()
    {
        $barberiaId = $this->barberiaId();

        $resumenMes = Venta::resumenMes($this->mes, $this->anio, $barberiaId);

        $topServicios = VentaItem::selectRaw('nombre_servicio, SUM(cantidad) as total_cant, SUM(subtotal) as total_valor')
            ->whereHas(
                'venta',
                fn($q) => $q
                    ->where('barberia_id', $barberiaId)
                    ->where('fue_eliminada', false)
                    ->whereMonth('fecha', $this->mes)
                    ->whereYear('fecha', $this->anio)
            )
            ->groupBy('nombre_servicio')
            ->orderByDesc('total_valor')
            ->limit(8)
            ->get();

        // Incluye barberos inactivos: pudieron trabajar ese mes
        $comisionesBarberos = Barbero::where('barberia_id', $barberiaId)
            ->get()
            ->map(fn($b) => $b->comisionesMes($this->mes, $this->anio))
            ->filter(fn($b) => $b['servicios'] > 0)
            ->sortByDesc('total_generado')
            ->values();

        $gastosPorCategoria = Gasto::selectRaw('categoria, SUM(valor) as total')
            ->where('barberia_id', $barberiaId)
            ->whereMonth('fecha', $this->mes)
            ->whereYear('fecha', $this->anio)
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->get();

        $anios = range(now()->year, 2024);

        return view('livewire.reportes', compact(
            'anios',
            'resumenMes',
            'topServicios',
            'comisionesBarberos',
            'gastosPorCategoria',
        ))->layout('layouts.app', ['title' => 'Reportes']);
    }
}
