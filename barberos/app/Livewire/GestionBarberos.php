<?php

namespace App\Livewire;

use App\Models\Barbero;
use Livewire\Component;

class GestionBarberos extends Component
{
    public string $nombre = '';
    public string $telefono = '';
    public float $comision_porcentaje = 50;
    public bool $activo = true;
    public ?int $editandoId = null;
    public bool $mostrarForm = false;

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function nuevo(): void
    {
        $this->reset(['nombre', 'telefono', 'comision_porcentaje', 'activo', 'editandoId']);
        $this->mostrarForm = true;
    }

    public function editar(int $id): void
    {
        $barbero = Barbero::where('barberia_id', $this->barberiaId())->findOrFail($id);
        $this->editandoId          = $id;
        $this->nombre              = $barbero->nombre;
        $this->telefono            = $barbero->telefono ?? '';
        $this->comision_porcentaje = (float) $barbero->comision_porcentaje;
        $this->activo              = (bool) $barbero->activo;
        $this->mostrarForm         = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre'              => 'required|min:2',
            'comision_porcentaje' => 'required|numeric|min:0|max:100',
        ]);

        if ($this->editandoId) {
            Barbero::where('barberia_id', $this->barberiaId())
                ->findOrFail($this->editandoId)
                ->update([
                    'nombre'              => $this->nombre,
                    'telefono'            => $this->telefono,
                    'comision_porcentaje' => $this->comision_porcentaje,
                    'activo'              => $this->activo,
                ]);
        } else {
            Barbero::create([
                'barberia_id'         => $this->barberiaId(),
                'nombre'              => $this->nombre,
                'telefono'            => $this->telefono,
                'comision_porcentaje' => $this->comision_porcentaje,
                'activo'              => true,
            ]);
        }

        $this->reset(['nombre', 'telefono', 'comision_porcentaje', 'editandoId']);
        $this->mostrarForm = false;
    }

    public string $aviso = '';

    public function eliminar(int $id): void
    {
        $barbero = Barbero::where('barberia_id', $this->barberiaId())->findOrFail($id);

        // Si ya tiene ventas, borrarlo dañaría el historial y las comisiones: se desactiva
        if ($barbero->ventas()->exists()) {
            $barbero->update(['activo' => false]);
            $this->aviso = "{$barbero->nombre} ya tiene ventas, así que se desactivó en lugar de borrarse.";
            return;
        }

        $barbero->delete();
        $this->aviso = '';
    }

    public function toggleActivo(int $id): void
    {
        $barbero = Barbero::where('barberia_id', $this->barberiaId())->findOrFail($id);
        $barbero->update(['activo' => !$barbero->activo]);
    }

    public function cancelar(): void
    {
        $this->reset(['nombre', 'telefono', 'comision_porcentaje', 'editandoId']);
        $this->mostrarForm = false;
    }

    public function render()
    {
        $barberos = Barbero::where('barberia_id', $this->barberiaId())
            ->orderBy('nombre')
            ->get();

        return view('livewire.gestion-barberos', [
            'barberos' => $barberos,
        ])->layout('layouts.app', ['title' => 'Barberos']);
    }
}
