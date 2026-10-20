<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class MiBarberia extends Component
{
    use WithFileUploads;

    public string $nombre = '';
    public string $nit = '';
    public string $propietario = '';
    public string $telefono = '';
    public string $direccion = '';
    public $logo = null;
    public string $mensaje = '';

    private function barberia()
    {
        return auth()->user()->barberia;
    }

    public function mount(): void
    {
        $barberia = $this->barberia();

        $this->nombre      = $barberia->nombre;
        $this->nit         = $barberia->nit ?? '';
        $this->propietario = $barberia->propietario ?? '';
        $this->telefono    = $barberia->telefono ?? '';
        $this->direccion   = $barberia->direccion ?? '';
    }

    public function updatedLogo(): void
    {
        $this->mensaje = '';
        $this->validateOnly('logo', ['logo' => 'nullable|image|mimes:png,jpg,jpeg|max:1024']);
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre'      => 'required|min:2|max:80',
            'nit'         => 'nullable|max:30',
            'propietario' => 'nullable|max:80',
            'telefono'    => 'nullable|max:30',
            'direccion'   => 'nullable|max:120',
            'logo'        => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
        ]);

        $barberia = $this->barberia();
        $datos = [
            'nombre'      => trim($this->nombre),
            'nit'         => trim($this->nit) ?: null,
            'propietario' => trim($this->propietario) ?: null,
            'telefono'    => trim($this->telefono) ?: null,
            'direccion'   => trim($this->direccion) ?: null,
        ];

        if ($this->logo) {
            $anterior = $barberia->logo;
            $datos['logo'] = $this->logo->store('logos', 'local');
            if ($anterior) Storage::disk('local')->delete($anterior);
        }

        $barberia->update($datos);

        $this->logo = null;
        $this->mensaje = 'Datos guardados. Así saldrán en los reportes PDF.';
    }

    public function quitarLogo(): void
    {
        $barberia = $this->barberia();

        if ($barberia->logo) {
            Storage::disk('local')->delete($barberia->logo);
            $barberia->update(['logo' => null]);
        }

        $this->logo = null;
        $this->mensaje = 'Logo quitado.';
    }

    public function render()
    {
        return view('livewire.mi-barberia', [
            'barberia' => $this->barberia()->fresh(),
        ])->layout('layouts.app', ['title' => 'Mi barbería']);
    }
}
