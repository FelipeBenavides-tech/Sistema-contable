<?php

namespace App\Livewire\Admin;

use App\Models\Barberia;
use App\Models\PagoSuscripcion;
use App\Models\User;
use App\Support\PlanesKaixa;
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
    // Primer pago (o recarga inicial) al crear la barbería
    public float  $valorPago = 0;

    // Registrar pago o recarga
    public bool   $mostrarPago    = false;
    public ?int   $pagoBarberiaId = null;
    public int    $pagoCantidad   = 1;
    public float  $pagoValor      = 0;
    public string $pagoMetodo     = 'transferencia';
    public string $pagoNota       = '';

    // Se ejecuta en cada petición: ningún usuario normal puede usar este panel
    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    private function precioPlan(string $plan): float
    {
        return (float) ($plan === 'porcentaje'
            ? config('suscripcion.porcentaje.recarga_minima')
            : config("suscripcion.planes.{$plan}.precio", 0));
    }

    public function updatedPlan(): void
    {
        if (!$this->editandoId) {
            $this->valorPago = $this->precioPlan($this->plan);
        }
    }

    public function nuevo(): void
    {
        $this->resetErrorBag();
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
        $this->valorPago   = $this->precioPlan('mensual');
        $this->mostrarForm = true;
    }

    public function editar(int $id): void
    {
        $this->resetErrorBag();
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
            'plan'   => 'required|in:' . implode(',', Barberia::PLANES),
        ];

        if (!$this->editandoId) {
            $rules['email']     = 'required|email|unique:users,email';
            $rules['password']  = 'required|min:8';
            $rules['valorPago'] = 'required|numeric|min:0';
        }

        $this->validate($rules);

        if ($this->editandoId) {
            $barberia = Barberia::findOrFail($this->editandoId);
            $barberia->fill([
                'nombre'      => $this->nombre,
                'propietario' => $this->propietario,
                'telefono'    => $this->telefono,
                'direccion'   => $this->direccion,
            ]);

            // Cambiar de plan no regala tiempo: el nuevo plan se cobra en el siguiente pago
            if ($barberia->plan !== $this->plan) {
                // Durante el contrato mínimo solo puede pasar a un plan prepagado más largo
                if ($barberia->en_contrato_minimo && !in_array($this->plan, ['semestral', 'anual'])) {
                    $this->addError('plan', "El plan {$barberia->nombre_plan} tiene contrato mínimo hasta el " . $barberia->contrato_hasta->format('d/m/Y') . '. Antes de esa fecha solo puede pasar a semestral o anual.');
                    return;
                }

                if ($barberia->plan === 'porcentaje') {
                    // Al salir del plan por uso, el primer pago vence hoy (con sus días de gracia)
                    $barberia->fecha_vencimiento = now()->startOfDay();
                }

                if (in_array($this->plan, Barberia::PLANES_CON_CONTRATO)) {
                    $barberia->iniciarContrato();
                } else {
                    $barberia->contrato_hasta = null;
                }

                $barberia->plan = $this->plan;
            }

            $barberia->save();
        } else {
            DB::transaction(function () {
                // Crear barbería
                $barberia = new Barberia([
                    'nombre'            => $this->nombre,
                    'propietario'       => $this->propietario,
                    'telefono'          => $this->telefono,
                    'direccion'         => $this->direccion,
                    'activo'            => true,
                    'plan'              => $this->plan,
                    'fecha_vencimiento' => $this->plan === 'porcentaje' ? null : now()->startOfDay(),
                ]);
                if (in_array($this->plan, Barberia::PLANES_CON_CONTRATO)) {
                    $barberia->iniciarContrato();
                }
                $barberia->save();

                // Crear usuario para esa barbería (la contraseña se cifra sola por el cast 'hashed')
                User::create([
                    'name'        => $this->propietario ?: $this->nombre,
                    'email'       => $this->email,
                    'password'    => $this->password,
                    'is_admin'    => false,
                    'barberia_id' => $barberia->id,
                ]);

                // Primer pago o recarga: deja el plan activo desde hoy
                if ($this->valorPago > 0) {
                    $barberia->registrarPago($this->valorPago);
                }
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

    // ── Pagos y recargas ─────────────────────────────────────────────────────

    public function abrirPago(int $id): void
    {
        $this->resetErrorBag();
        $barberia = Barberia::findOrFail($id);

        $this->pagoBarberiaId = $id;
        $this->pagoCantidad   = 1;
        $this->pagoValor      = $barberia->precio_plan;
        $this->pagoMetodo     = 'transferencia';
        $this->pagoNota       = '';
        $this->mostrarPago    = true;
    }

    // En el plan mensual se pueden pagar varios meses a la vez
    public function updatedPagoCantidad(): void
    {
        $barberia = Barberia::find($this->pagoBarberiaId);
        if ($barberia && !$barberia->por_uso) {
            $this->pagoValor = $barberia->precio_plan * max(1, $this->pagoCantidad);
        }
    }

    public function registrarPago(): void
    {
        $barberia = Barberia::findOrFail($this->pagoBarberiaId);

        $this->validate([
            'pagoCantidad' => 'required|integer|min:1|max:24',
            'pagoValor'    => 'required|numeric|min:' . ($barberia->por_uso ? config('suscripcion.porcentaje.recarga_minima') : 1),
            'pagoMetodo'   => 'required|in:efectivo,transferencia,tarjeta',
            'pagoNota'     => 'nullable|max:200',
        ], [
            'pagoValor.min' => $barberia->por_uso
                ? 'La recarga mínima es de $' . number_format((float) config('suscripcion.porcentaje.recarga_minima'), 0, ',', '.') . '.'
                : 'Escribe el valor pagado.',
        ], [
            'pagoCantidad' => 'meses',
            'pagoValor'    => 'valor',
        ]);

        $barberia->registrarPago(
            $this->pagoValor,
            $barberia->plan === 'mensual' ? $this->pagoCantidad : 1,
            $this->pagoMetodo,
            trim($this->pagoNota) ?: null,
        );

        $this->cerrarPago();
    }

    // Saldo a favor del plan por uso que se le devuelve al cliente
    public function devolverSaldo(int $id): void
    {
        $barberia = Barberia::findOrFail($id);

        if ($barberia->en_contrato_minimo) {
            $this->addError('devolucion', 'Solo se devuelve el saldo cuando termina el contrato mínimo (' . $barberia->contrato_hasta->format('d/m/Y') . ').');
            return;
        }

        $barberia->devolverSaldo();
    }

    public function cerrarPago(): void
    {
        $this->resetErrorBag();
        $this->mostrarPago    = false;
        $this->pagoBarberiaId = null;
    }

    public function eliminar(int $id): void
    {
        Barberia::findOrFail($id)->delete();
    }

    public function cancelar(): void
    {
        $this->resetErrorBag();
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
            'total'      => $barberias->count(),
            'activas'    => $barberias->where('activo', true)->count(),
            'en_gracia'  => $barberias->filter(fn($b) => $b->estado_pago === 'en_gracia')->count(),
            'bloqueadas' => $barberias->filter(fn($b) => $b->estado_pago === 'bloqueada')->count(),
            'recaudo'    => (float) PagoSuscripcion::whereMonth('fecha', now()->month)->whereYear('fecha', now()->year)->sum('valor'),
        ];

        return view('livewire.admin.panel-admin', [
            'barberias' => $barberias,
            'stats'     => $stats,
            'opciones'  => PlanesKaixa::opciones(),
            'pagoDe'    => $this->pagoBarberiaId ? $barberias->firstWhere('id', $this->pagoBarberiaId) : null,
        ])->layout('layouts.admin', ['title' => 'Panel de Administración']);
    }
}
