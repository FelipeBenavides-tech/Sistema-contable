<?php

namespace App\Livewire;

use App\Models\Gasto;
use Livewire\Component;

class GestionGastos extends Component
{
    public string $concepto = '';
    public string $categoria = 'operativo';
    public float $valor = 0;
    public string $metodo_pago = 'efectivo';
    public string $fecha = '';
    public string $observacion = '';
    public bool $mostrarForm = false;

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function mount(): void
    {
        $this->fecha = now()->toDateString();
    }

    public function nuevo(): void
    {
        $this->resetErrorBag();
        $this->reset(['concepto', 'categoria', 'valor', 'metodo_pago', 'observacion']);
        $this->fecha       = now()->toDateString();
        $this->mostrarForm = true;
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $this->validate([
            'concepto'    => 'required|min:2|max:255',
            'categoria'   => 'required|in:arriendo,servicios_publicos,insumos,nomina,publicidad,mantenimiento,operativo,otros',
            'valor'       => 'required|numeric|min:1|max:99999999',
            'metodo_pago' => 'required|in:efectivo,transferencia,tarjeta',
            'fecha'       => 'required|date',
            'observacion' => 'nullable|max:255',
        ]);

        Gasto::create([
            'barberia_id' => $this->barberiaId(),
            'concepto'    => $this->concepto,
            'categoria'   => $this->categoria,
            'valor'       => $this->valor,
            'metodo_pago' => $this->metodo_pago,
            'fecha'       => $this->fecha,
            'observacion' => $this->observacion,
        ]);

        $this->reset(['concepto', 'categoria', 'valor', 'metodo_pago', 'observacion']);
        $this->mostrarForm = false;
    }

    public function eliminar(int $id): void
    {
        Gasto::where('barberia_id', $this->barberiaId())->findOrFail($id)->delete();
    }

    public function cancelar(): void
    {
        $this->resetErrorBag();
        $this->reset(['concepto', 'categoria', 'valor', 'metodo_pago', 'observacion']);
        $this->mostrarForm = false;
    }

    public function render()
    {
        $gastos   = Gasto::where('barberia_id', $this->barberiaId())
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->get();
        $totalMes = Gasto::totalMes(now()->month, now()->year, $this->barberiaId());

        return view('livewire.gestion-gastos', [
            'gastos'   => $gastos,
            'totalMes' => $totalMes,
        ])->layout('layouts.app', ['title' => 'Gastos']);
    }
}
