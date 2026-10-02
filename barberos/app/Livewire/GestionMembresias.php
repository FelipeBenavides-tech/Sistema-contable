<?php

namespace App\Livewire;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PlanMembresia;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GestionMembresias extends Component
{
    public string $tab    = 'membresias';
    public string $filtro = 'activas';
    public string $buscar = '';
    public string $aviso  = '';
    public string $exito  = '';

    // Formulario de plan
    public bool   $mostrarPlan  = false;
    public ?int   $planId       = null;
    public string $planNombre   = '';
    public float  $planPrecio   = 0;
    public int    $planVisitas  = 5;
    public int    $planDias     = 30;

    // Venta de membresía
    public bool   $mostrarVenta   = false;
    public int    $clienteId      = 0;
    public string $buscarClienteVenta = '';
    public string $nuevoNombre    = '';
    public string $nuevoTelefono  = '';
    public int    $ventaPlanId    = 0;
    public string $metodoPago     = 'efectivo';
    public float  $montoEfectivo  = 0;
    public float  $montoNequi     = 0;

    // Detalle de visitas
    public ?int $detalleId = null;

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    // ── Planes ──────────────────────────────────────────────────────────────

    public function nuevoPlan(): void
    {
        $this->resetErrorBag();
        $this->reset(['planId', 'planNombre', 'planPrecio', 'planVisitas', 'planDias', 'aviso']);
        $this->mostrarPlan = true;
    }

    public function editarPlan(int $id): void
    {
        $this->resetErrorBag();
        $plan = PlanMembresia::where('barberia_id', $this->barberiaId())->findOrFail($id);
        $this->planId      = $plan->id;
        $this->planNombre  = $plan->nombre;
        $this->planPrecio  = (float) $plan->precio;
        $this->planVisitas = $plan->visitas;
        $this->planDias    = $plan->duracion_dias;
        $this->aviso       = '';
        $this->mostrarPlan = true;
    }

    public function guardarPlan(): void
    {
        $this->validate([
            'planNombre'  => 'required|min:2|max:80',
            'planPrecio'  => 'required|numeric|min:0|max:99999999',
            'planVisitas' => 'required|integer|min:1|max:365',
            'planDias'    => 'required|integer|min:1|max:730',
        ], [], [
            'planNombre'  => 'nombre',
            'planPrecio'  => 'precio',
            'planVisitas' => 'visitas',
            'planDias'    => 'duración',
        ]);

        $datos = [
            'nombre'        => $this->planNombre,
            'precio'        => $this->planPrecio,
            'visitas'       => $this->planVisitas,
            'duracion_dias' => $this->planDias,
        ];

        if ($this->planId) {
            // Las membresías ya vendidas conservan las condiciones con que se vendieron
            PlanMembresia::where('barberia_id', $this->barberiaId())->findOrFail($this->planId)->update($datos);
        } else {
            PlanMembresia::create($datos + ['barberia_id' => $this->barberiaId(), 'activo' => true]);
        }

        $this->mostrarPlan = false;
    }

    public function togglePlan(int $id): void
    {
        $plan = PlanMembresia::where('barberia_id', $this->barberiaId())->findOrFail($id);
        $plan->update(['activo' => !$plan->activo]);
    }

    public function eliminarPlan(int $id): void
    {
        $plan = PlanMembresia::where('barberia_id', $this->barberiaId())->findOrFail($id);

        if ($plan->membresias()->exists()) {
            $plan->update(['activo' => false]);
            $this->aviso = "\"{$plan->nombre}\" ya se vendió, así que se desactivó en lugar de borrarse.";
            return;
        }

        $plan->delete();
        $this->aviso = '';
    }

    public function cancelarPlan(): void
    {
        $this->resetErrorBag();
        $this->mostrarPlan = false;
    }

    // ── Venta de membresía ──────────────────────────────────────────────────

    public function nuevaVenta(?int $clienteId = null): void
    {
        $this->resetErrorBag();
        $this->reset(['clienteId', 'buscarClienteVenta', 'nuevoNombre', 'nuevoTelefono', 'ventaPlanId', 'metodoPago', 'montoEfectivo', 'montoNequi', 'exito']);

        if ($clienteId) {
            $this->clienteId = Cliente::where('barberia_id', $this->barberiaId())->findOrFail($clienteId)->id;
        }

        $this->ventaPlanId = (int) PlanMembresia::where('barberia_id', $this->barberiaId())
            ->where('activo', true)
            ->orderBy('nombre')
            ->value('id');
        $this->mostrarVenta = true;
    }

    public function elegirCliente(int $id): void
    {
        $this->clienteId = Cliente::where('barberia_id', $this->barberiaId())->findOrFail($id)->id;
        $this->buscarClienteVenta = '';
    }

    public function cambiarCliente(): void
    {
        $this->clienteId = 0;
    }

    public function venderMembresia(): void
    {
        $barberiaId = $this->barberiaId();

        $this->validate([
            'ventaPlanId'   => 'required|integer|min:1',
            'metodoPago'    => 'required|in:efectivo,nequi,transferencia,combinado',
            'nuevoNombre'   => $this->clienteId ? 'nullable' : 'required|min:2|max:80',
            'nuevoTelefono' => 'nullable|max:30',
        ], [
            'ventaPlanId.min'      => 'Elige un plan.',
            'nuevoNombre.required' => 'Escribe el nombre del cliente o búscalo.',
        ], [
            'nuevoNombre'   => 'nombre',
            'nuevoTelefono' => 'teléfono',
        ]);

        $plan = PlanMembresia::where('barberia_id', $barberiaId)->where('activo', true)->findOrFail($this->ventaPlanId);
        $total = (float) $plan->precio;

        if ($this->metodoPago === 'combinado'
            && abs(($this->montoEfectivo + $this->montoNequi) - $total) > 1) {
            $this->addError('montoEfectivo', 'En pago combinado, efectivo + Nequi debe ser igual al precio.');
            return;
        }

        $cliente = $this->clienteId
            ? Cliente::where('barberia_id', $barberiaId)->findOrFail($this->clienteId)
            : Cliente::create([
                'barberia_id' => $barberiaId,
                'nombre'      => trim($this->nuevoNombre),
                'telefono'    => trim($this->nuevoTelefono) ?: null,
            ]);

        $membresia = Membresia::vender($cliente, $plan, [
            'metodo_pago'    => $this->metodoPago,
            'monto_efectivo' => match ($this->metodoPago) {
                'efectivo'  => $total,
                'combinado' => $this->montoEfectivo,
                default     => 0,
            },
            'monto_nequi'    => match ($this->metodoPago) {
                'nequi', 'transferencia' => $total,
                'combinado'              => $this->montoNequi,
                default                  => 0,
            },
        ], $barberiaId);

        $this->mostrarVenta = false;
        $this->tab    = 'membresias';
        $this->filtro = 'activas';
        $this->exito  = "Membresía vendida a {$cliente->nombre}: {$membresia->visitas_total} visitas hasta el {$membresia->fecha_vencimiento->format('d/m/Y')}.";
    }

    public function cancelarVenta(): void
    {
        $this->resetErrorBag();
        $this->mostrarVenta = false;
    }

    // ── Detalle ─────────────────────────────────────────────────────────────

    public function verDetalle(int $id): void
    {
        $this->detalleId = Membresia::where('barberia_id', $this->barberiaId())->findOrFail($id)->id;
    }

    public function cerrarDetalle(): void
    {
        $this->detalleId = null;
    }

    public function render()
    {
        $barberiaId = $this->barberiaId();
        $termino = trim($this->buscar);

        $membresias = Membresia::with('cliente')
            ->where('barberia_id', $barberiaId)
            ->when($this->filtro === 'activas', fn($q) => $q->vigentes())
            ->when($termino !== '', fn($q) => $q->whereHas('cliente', fn($q) => $q->where(fn($q) => $q
                ->where('nombre', 'like', "%{$termino}%")
                ->orWhere('telefono', 'like', "%{$termino}%"))))
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $planes = PlanMembresia::where('barberia_id', $barberiaId)
            ->withCount('membresias')
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        $terminoCliente = trim($this->buscarClienteVenta);
        $clientesEncontrados = $this->mostrarVenta && !$this->clienteId && mb_strlen($terminoCliente) >= 2
            ? Cliente::where('barberia_id', $barberiaId)
                ->where(fn($q) => $q->where('nombre', 'like', "%{$terminoCliente}%")
                    ->orWhere('telefono', 'like', "%{$terminoCliente}%"))
                ->orderBy('nombre')
                ->limit(6)
                ->get()
            : collect();

        $detalle = $this->detalleId
            ? Membresia::with(['cliente', 'usos.venta.barbero'])
                ->where('barberia_id', $barberiaId)
                ->find($this->detalleId)
            : null;

        $activas = Membresia::where('barberia_id', $barberiaId)->vigentes();

        return view('livewire.gestion-membresias', [
            'membresias'          => $membresias,
            'planes'              => $planes,
            'clienteElegido'      => $this->clienteId ? Cliente::where('barberia_id', $barberiaId)->find($this->clienteId) : null,
            'clientesEncontrados' => $clientesEncontrados,
            'detalle'             => $detalle,
            'totalActivas'        => (clone $activas)->count(),
            'visitasPendientes'   => (int) (clone $activas)->sum(DB::raw('visitas_total - visitas_usadas')),
        ])->layout('layouts.app', ['title' => 'Membresías']);
    }
}
