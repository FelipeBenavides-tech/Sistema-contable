<?php

namespace App\Livewire;

use App\Models\Barbero;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GestionBarberos extends Component
{
    public string $nombre = '';
    public string $telefono = '';
    public float $comision_porcentaje = 50;
    public bool $activo = true;
    public ?int $editandoId = null;
    public bool $mostrarForm = false;

    // Usuario del barbero para ver su agenda (plan Pro)
    public ?int   $accesoBarberoId = null;
    public string $accesoEmail     = '';
    public string $accesoPassword  = '';

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function nuevo(): void
    {
        $this->resetErrorBag();
        $this->aviso = '';
        $this->reset(['nombre', 'telefono', 'comision_porcentaje', 'activo', 'editandoId']);
        $this->mostrarForm = true;
    }

    public function editar(int $id): void
    {
        $this->resetErrorBag();
        $this->aviso = '';
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

        $barbero->user?->delete();
        $barbero->delete();
        $this->aviso = '';
    }

    public function toggleActivo(int $id): void
    {
        $barbero = Barbero::where('barberia_id', $this->barberiaId())->findOrFail($id);
        $barbero->update(['activo' => !$barbero->activo]);
    }

    // ── Acceso del barbero a su agenda (plan Pro) ────────────────────────────

    private function esPro(): bool
    {
        return (bool) auth()->user()->barberia?->es_pro;
    }

    public function abrirAcceso(int $id): void
    {
        if (!$this->esPro()) {
            return;
        }

        $this->resetErrorBag();
        $barbero = Barbero::with('user')->where('barberia_id', $this->barberiaId())->findOrFail($id);

        $this->accesoBarberoId = $barbero->id;
        $this->accesoEmail     = $barbero->user?->email ?? '';
        $this->accesoPassword  = '';
    }

    public function guardarAcceso(): void
    {
        if (!$this->esPro()) {
            return;
        }

        $barbero = Barbero::with('user')->where('barberia_id', $this->barberiaId())->findOrFail($this->accesoBarberoId);
        $user = $barbero->user;

        $this->validate([
            'accesoEmail'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user?->id)],
            'accesoPassword' => [$user ? 'nullable' : 'required', 'min:8'],
        ], [
            'accesoEmail.unique' => 'Ese correo ya lo usa otra cuenta.',
        ], [
            'accesoEmail'    => 'correo',
            'accesoPassword' => 'contraseña',
        ]);

        $datos = ['email' => $this->accesoEmail, 'name' => $barbero->nombre];
        if ($this->accesoPassword !== '') {
            $datos['password'] = $this->accesoPassword;
        }

        if ($user) {
            $user->update($datos);
        } else {
            User::create($datos + [
                'barberia_id' => $this->barberiaId(),
                'barbero_id'  => $barbero->id,
                'rol'         => 'barbero',
                'is_admin'    => false,
            ]);
        }

        $this->aviso = '';
        $this->accesoBarberoId = null;
    }

    public function quitarAcceso(int $id): void
    {
        $barbero = Barbero::with('user')->where('barberia_id', $this->barberiaId())->findOrFail($id);
        $barbero->user?->delete();
        $this->accesoBarberoId = null;
    }

    public function cerrarAcceso(): void
    {
        $this->resetErrorBag();
        $this->accesoBarberoId = null;
    }

    public function cancelar(): void
    {
        $this->resetErrorBag();
        $this->aviso = '';
        $this->reset(['nombre', 'telefono', 'comision_porcentaje', 'editandoId']);
        $this->mostrarForm = false;
    }

    public function render()
    {
        $barberos = Barbero::with('user')
            ->where('barberia_id', $this->barberiaId())
            ->orderBy('nombre')
            ->get();

        return view('livewire.gestion-barberos', [
            'barberos'     => $barberos,
            'esPro'        => $this->esPro(),
            'barberoAcceso' => $this->accesoBarberoId ? $barberos->firstWhere('id', $this->accesoBarberoId) : null,
        ])->layout('layouts.app', ['title' => 'Barberos']);
    }
}
