<?php

namespace App\Livewire;

use App\Models\Venta;
use App\Support\PlanesKaixa;
use Livewire\Component;

class MiPlan extends Component
{
    public function render()
    {
        $barberia = auth()->user()->barberia;

        $cobradoMes = $barberia->por_uso
            ? (float) Venta::where('barberia_id', $barberia->id)
                ->whereMonth('fecha', now()->month)
                ->whereYear('fecha', now()->year)
                ->sum('cargo_plataforma')
            : 0;

        return view('livewire.mi-plan', [
            'barberia'   => $barberia,
            'planes'     => PlanesKaixa::catalogo(),
            'pagos'      => $barberia->pagosSuscripcion()->latest('fecha')->latest('id')->limit(24)->get(),
            'cobradoMes' => $cobradoMes,
            'whatsapp'   => preg_replace('/\D/', '', (string) config('suscripcion.whatsapp_soporte')),
        ])->layout('layouts.app', ['title' => 'Mi plan Kaixa']);
    }
}
