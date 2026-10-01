<?php

namespace App\Livewire\Admin;

use App\Models\Barberia;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PanelAdmin extends Component
{
    // Formulario nueva barbería
    public string $nombre = '';
    public string $propietario = '';
    public string $telefono = '';
    public string $direccion = '';
    public string $plan = 'mensual';
    public string $email = '';
    public string $password = '';
    public bool $mostrarForm = false;
    public ?int $editandoId = null;
    public string $nuevoEmail    = '';
    public string $nuevaPassword = '';
    public bool   $mostrarCredenciales = false;
    public ?int   $credencialesBarberiaId = null;
    public string $credencialesEmail = '';

    // Se ejecuta en cada petición: ningún usuario normal puede usar este panel
    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    private function vencimientoPara(string $plan)
    {
        return match ($plan) {
            'semestral' => now()->addMonths(6),
            'anual'     => now()->addYear(),
            default     => now()->addMonth(),
        };
    }

    public function nuevo(): void
    {
        $this->reset([
            'nombre',
            'propietario',
            'telefono',
            'direccion',
            'plan',
            'email',
            'password',
            'editandoId'
        ]);
        $this->plan        = 'mensual';
        $this->mostrarForm = true;
    }

    public function editar(int $id): void
    {
        $barberia          = Barberia::findOrFail($id);
        $this->editandoId  = $id;
        $this->nombre      = $barberia->nombre;
        $this->propietario = $barberia->propietario ?? '';
        $this->telefono    = $barberia->telefono ?? '';
        $this->direccion   = $barberia->direccion ?? '';
        $this->plan        = $barberia->plan;
        $this->mostrarForm = true;
    }

    public function guardar(): void
    {
        $rules = [
            'nombre' => 'required|min:2',
            'plan'   => 'required|in:mensual,semestral,anual',
        ];

        if (!$this->editandoId) {
            $rules['email']    = 'required|email|unique:users,email';
            $rules['password'] = 'required|min:8';
        }

        $this->validate($rules);

        if ($this->editandoId) {
            $barberia = Barberia::findOrFail($this->editandoId);
            $datos = [
                'nombre'      => $this->nombre,
                'propietario' => $this->propietario,
                'telefono'    => $this->telefono,
                'direccion'   => $this->direccion,
                'plan'        => $this->plan,
            ];

            // Editar datos no debe regalar tiempo: la fecha solo cambia si cambia el plan
            if ($barberia->plan !== $this->plan) {
                $datos['fecha_vencimiento'] = $this->vencimientoPara($this->plan);
            }

            $barberia->update($datos);
        } else {
            DB::transaction(function () {
                // Crear barbería
                $barberia = Barberia::create([
                    'nombre'            => $this->nombre,
                    'propietario'       => $this->propietario,
                    'telefono'          => $this->telefono,
                    'direccion'         => $this->direccion,
                    'activo'            => true,
                    'plan'              => $this->plan,
                    'fecha_vencimiento' => $this->vencimientoPara($this->plan),
                ]);

                // Crear usuario para esa barbería (la contraseña se cifra sola por el cast 'hashed')
                User::create([
                    'name'        => $this->propietario ?: $this->nombre,
                    'email'       => $this->email,
                    'password'    => $this->password,
                    'is_admin'    => false,
                    'barberia_id' => $barberia->id,
                ]);
            });
        }

        $this->reset([
            'nombre',
            'propietario',
            'telefono',
            'direccion',
            'plan',
            'email',
            'password',
            'editandoId'
        ]);
        $this->mostrarForm = false;
    }

    public function toggleActivo(int $id): void
    {
        $barberia = Barberia::findOrFail($id);
        $barberia->update(['activo' => !$barberia->activo]);
    }

    public function renovar(int $id): void
    {
        $barberia = Barberia::findOrFail($id);

        // Si aún no ha vencido, el nuevo periodo se suma al que le queda
        $desde = $barberia->fecha_vencimiento && $barberia->fecha_vencimiento->isFuture()
            ? $barberia->fecha_vencimiento->copy()
            : now();

        $barberia->update(['fecha_vencimiento' => match ($barberia->plan) {
            'semestral' => $desde->addMonths(6),
            'anual'     => $desde->addYear(),
            default     => $desde->addMonth(),
        }]);
    }

    public function eliminar(int $id): void
    {
        Barberia::findOrFail($id)->delete();
    }

    public function cancelar(): void
    {
        $this->reset([
            'nombre',
            'propietario',
            'telefono',
            'direccion',
            'plan',
            'email',
            'password',
            'editandoId'
        ]);
        $this->mostrarForm = false;
    }

    public function verCredenciales(int $id): void
    {
        $barberia = Barberia::findOrFail($id);
        $user     = $barberia->users()->first();

        $this->credencialesBarberiaId = $id;
        $this->credencialesEmail      = $user?->email ?? 'Sin usuario';
        $this->nuevoEmail             = $user?->email ?? '';
        $this->nuevaPassword          = '';
        $this->mostrarCredenciales    = true;
    }

    public function actualizarCredenciales(): void
    {
        $barberia = Barberia::findOrFail($this->credencialesBarberiaId);
        $user     = $barberia->users()->first();

        $this->validate([
            'nuevoEmail'    => 'required|email|unique:users,email,' . ($user?->id ?? 'NULL'),
            'nuevaPassword' => 'nullable|min:8',
        ]);

        if (!$user) {
            $this->addError('nuevoEmail', 'No hay usuario asignado a esta barbería.');
            return;
        }

        $data = ['email' => $this->nuevoEmail];
        if (!empty($this->nuevaPassword)) {
            $data['password'] = $this->nuevaPassword;
        }

        $user->update($data);

        $this->mostrarCredenciales    = false;
        $this->credencialesBarberiaId = null;
        $this->nuevoEmail             = '';
        $this->nuevaPassword          = '';
    }

    public function cerrarCredenciales(): void
    {
        $this->mostrarCredenciales    = false;
        $this->credencialesBarberiaId = null;
        $this->nuevoEmail             = '';
        $this->nuevaPassword          = '';
    }

    public function render()
    {
        $barberias = Barberia::withCount('ventas')
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total'    => $barberias->count(),
            'activas'  => $barberias->where('activo', true)->count(),
            'vencidas' => $barberias->filter(fn($b) => $b->vencida)->count(),
        ];

        return view('livewire.admin.panel-admin', [
            'barberias' => $barberias,
            'stats'     => $stats,
        ])->layout('layouts.admin', ['title' => 'Panel de Administración']);
    }
}
