<?php

namespace App\Livewire;

use App\Models\Servicio;
use Livewire\Component;

class GestionServicios extends Component
{
    public string $nombre = '';
    public float $precio = 0;
    public string $categoria = 'servicio';
    public ?int $editandoId = null;
    public bool $mostrarForm = false;

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function nuevo(): void
    {
        $this->resetErrorBag();
        $this->aviso = '';
        $this->reset(['nombre', 'precio', 'categoria', 'editandoId']);
        $this->categoria = 'servicio';
        $this->mostrarForm = true;
    }

    public function editar(int $id): void
    {
        $this->resetErrorBag();
        $this->aviso = '';
        $servicio = Servicio::where('barberia_id', $this->barberiaId())->findOrFail($id);
        $this->editandoId  = $id;
        $this->nombre      = $servicio->nombre;
        $this->precio      = $servicio->precio;
        $this->categoria   = $servicio->categoria;
        $this->mostrarForm = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre'    => 'required|min:2',
            'precio'    => 'required|numeric|min:0|max:99999999',
            'categoria' => 'required|in:servicio,producto',
        ]);

        if ($this->editandoId) {
            Servicio::where('barberia_id', $this->barberiaId())
                ->findOrFail($this->editandoId)
                ->update([
                    'nombre'    => $this->nombre,
                    'precio'    => $this->precio,
                    'categoria' => $this->categoria,
                ]);
        } else {
            Servicio::create([
                'barberia_id' => $this->barberiaId(),
                'nombre'      => $this->nombre,
                'precio'      => $this->precio,
                'categoria'   => $this->categoria,
                'activo'      => true,
            ]);
        }

        $this->reset(['nombre', 'precio', 'categoria', 'editandoId']);
        $this->mostrarForm = false;
    }

    public string $aviso = '';

    public function eliminar(int $id): void
    {
        $servicio = Servicio::where('barberia_id', $this->barberiaId())->findOrFail($id);

        // Si ya se vendió, borrarlo dañaría el historial: se desactiva
        if ($servicio->ventaItems()->exists()) {
            $servicio->update(['activo' => false]);
            $this->aviso = "\"{$servicio->nombre}\" ya tiene ventas, así que se desactivó en lugar de borrarse.";
            return;
        }

        $servicio->delete();
        $this->aviso = '';
    }

    public function toggleActivo(int $id): void
    {
        $servicio = Servicio::where('barberia_id', $this->barberiaId())->findOrFail($id);
        $servicio->update(['activo' => !$servicio->activo]);
    }

    public function cancelar(): void
    {
        $this->resetErrorBag();
        $this->aviso = '';
        $this->reset(['nombre', 'precio', 'categoria', 'editandoId']);
        $this->mostrarForm = false;
    }

    public function render()
    {
        $servicios = Servicio::where('barberia_id', $this->barberiaId())
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get();

        return view('livewire.gestion-servicios', [
            'servicios' => $servicios,
        ])->layout('layouts.app', ['title' => 'Servicios']);
    }
}
