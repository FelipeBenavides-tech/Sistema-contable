<?php

namespace App\Livewire;

use App\Models\Barberia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    // Reservas en línea (plan Pro)
    public string $slug = '';
    public bool   $reservas_activas = false;
    public int    $anticipo_porcentaje = 50;
    public int    $horas_cancelacion = 4;
    public string $wompi_llave_publica = '';
    // Las secretas no se muestran: si se dejan vacías se conservan las guardadas
    public string $wompi_llave_privada = '';
    public string $wompi_secreto_eventos = '';
    public string $wompi_secreto_integridad = '';
    public string $mensajeReservas = '';

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

        $this->slug                = $barberia->slug ?? Barberia::slugDisponible($barberia->nombre, $barberia->id);
        $this->reservas_activas    = (bool) $barberia->reservas_activas;
        $this->anticipo_porcentaje = (int) $barberia->anticipo_porcentaje;
        $this->horas_cancelacion   = (int) $barberia->horas_cancelacion;
        $this->wompi_llave_publica = $barberia->wompi_llave_publica ?? '';
    }

    public function guardarReservas(): void
    {
        $barberia = $this->barberia();
        abort_unless($barberia->es_pro, 403);

        $this->slug = Str::slug($this->slug);
        $this->validate([
            'slug'                => ['required', 'min:3', 'max:80', Rule::unique('barberias', 'slug')->ignore($barberia->id)],
            'anticipo_porcentaje' => 'required|integer|min:0|max:100',
            'horas_cancelacion'   => 'required|integer|min:0|max:168',
            'wompi_llave_publica' => ['nullable', 'regex:/^pub_(test|prod)_\w+$/'],
            'wompi_llave_privada' => ['nullable', 'regex:/^prv_(test|prod)_\w+$/'],
            'wompi_secreto_eventos'    => ['nullable', 'regex:/^(test|prod)_events_\w+$/'],
            'wompi_secreto_integridad' => ['nullable', 'regex:/^(test|prod)_integrity_\w+$/'],
        ], [
            'slug.unique'                    => 'Esa dirección ya la usa otra barbería.',
            'wompi_llave_publica.regex'      => 'La llave pública de Wompi empieza por pub_test_ o pub_prod_.',
            'wompi_llave_privada.regex'      => 'La llave privada de Wompi empieza por prv_test_ o prv_prod_.',
            'wompi_secreto_eventos.regex'    => 'El secreto de eventos empieza por test_events_ o prod_events_.',
            'wompi_secreto_integridad.regex' => 'El secreto de integridad empieza por test_integrity_ o prod_integrity_.',
        ], ['slug' => 'dirección']);

        $datos = [
            'slug'                => $this->slug,
            'reservas_activas'    => $this->reservas_activas,
            'anticipo_porcentaje' => $this->anticipo_porcentaje,
            'horas_cancelacion'   => $this->horas_cancelacion,
            'wompi_llave_publica' => trim($this->wompi_llave_publica) ?: null,
        ];
        foreach (['wompi_llave_privada', 'wompi_secreto_eventos', 'wompi_secreto_integridad'] as $campo) {
            if (trim($this->$campo) !== '') {
                $datos[$campo] = trim($this->$campo);
            }
        }

        $barberia->update($datos);
        $this->reset(['wompi_llave_privada', 'wompi_secreto_eventos', 'wompi_secreto_integridad']);

        $barberia->refresh();
        $this->mensajeReservas = $this->reservas_activas && $this->anticipo_porcentaje > 0 && !$barberia->wompi_configurado
            ? 'Guardado. Falta completar las llaves de Wompi: mientras tanto las reservas se reciben sin anticipo.'
            : 'Configuración de reservas guardada.';
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
