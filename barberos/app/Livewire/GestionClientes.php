<?php

namespace App\Livewire;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GestionClientes extends Component
{
    public string $buscar = '';
    // todos | cartera | cumpleanos
    public string $filtro = 'todos';

    // Formulario
    public bool    $mostrarForm      = false;
    public ?int    $editandoId       = null;
    public string  $nombre           = '';
    public string  $telefono         = '';
    public string  $email            = '';
    public ?string $fecha_nacimiento = null;
    public string  $notas            = '';
    public bool    $permite_fiado    = false;
    public bool    $acepta_mensajes  = false;

    public ?int   $detalleId = null;
    public string $aviso     = '';

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function nuevo(): void
    {
        $this->resetErrorBag();
        $this->reset(['editandoId', 'nombre', 'telefono', 'email', 'fecha_nacimiento', 'notas', 'permite_fiado', 'acepta_mensajes']);
        $this->mostrarForm = true;
    }

    public function editar(int $id): void
    {
        $this->resetErrorBag();
        $cliente = Cliente::where('barberia_id', $this->barberiaId())->findOrFail($id);

        $this->editandoId       = $cliente->id;
        $this->nombre           = $cliente->nombre;
        $this->telefono         = $cliente->telefono ?? '';
        $this->email            = $cliente->email ?? '';
        $this->fecha_nacimiento = $cliente->fecha_nacimiento?->toDateString();
        $this->notas            = $cliente->notas ?? '';
        $this->permite_fiado    = $cliente->permite_fiado;
        $this->acepta_mensajes  = $cliente->acepta_mensajes;
        $this->detalleId        = null;
        $this->mostrarForm      = true;
    }

    public function guardar(): void
    {
        $this->telefono = Cliente::normalizarTelefono($this->telefono);

        $this->validate([
            'nombre'           => 'required|min:2|max:120',
            'telefono'         => [
                'nullable', 'digits_between:7,15',
                Rule::unique('clientes', 'telefono')
                    ->where('barberia_id', $this->barberiaId())
                    ->ignore($this->editandoId),
            ],
            'email'            => 'nullable|email|max:150',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'notas'            => 'nullable|max:500',
        ], [
            'telefono.unique'         => 'Ya hay un cliente con ese teléfono.',
            'telefono.digits_between' => 'Escribe un teléfono válido (solo números).',
        ]);

        $datos = [
            'nombre'           => trim($this->nombre),
            'telefono'         => $this->telefono ?: null,
            'email'            => trim($this->email) ?: null,
            'fecha_nacimiento' => $this->fecha_nacimiento ?: null,
            'notas'            => trim($this->notas) ?: null,
            'permite_fiado'    => $this->permite_fiado,
            'acepta_mensajes'  => $this->acepta_mensajes,
        ];

        if ($this->editandoId) {
            Cliente::where('barberia_id', $this->barberiaId())->findOrFail($this->editandoId)->update($datos);
        } else {
            Cliente::create($datos + ['barberia_id' => $this->barberiaId()]);
        }

        $this->mostrarForm = false;
        $this->editandoId  = null;
    }

    public function cancelar(): void
    {
        $this->resetErrorBag();
        $this->mostrarForm = false;
        $this->editandoId  = null;
    }

    public function verDetalle(int $id): void
    {
        $this->detalleId = Cliente::where('barberia_id', $this->barberiaId())->findOrFail($id)->id;
    }

    public function cerrarDetalle(): void
    {
        $this->detalleId = null;
    }

    public function eliminar(int $id): void
    {
        $cliente = Cliente::where('barberia_id', $this->barberiaId())->findOrFail($id);

        // Con historial no se borra: se perdería la trazabilidad de ventas y membresías
        if ($cliente->ventas()->exists() || $cliente->membresias()->exists() || $cliente->citas()->exists()) {
            $this->aviso = "{$cliente->nombre} tiene ventas, membresías o citas, así que no se puede eliminar.";
            return;
        }

        $cliente->delete();
        $this->aviso = '';
        $this->detalleId = null;
    }

    public function render()
    {
        $barberiaId = $this->barberiaId();
        $termino = trim($this->buscar);

        $clientes = Cliente::where('barberia_id', $barberiaId)
            ->when($termino !== '', fn($q) => $q->buscar($termino))
            ->when($this->filtro === 'cartera', fn($q) => $q->where(fn($q) => $q->where('permite_fiado', true)->orWhereHas('ventas', fn($v) => $v->activas()->where('saldo_pendiente', '>', 0))))
            ->when($this->filtro === 'cumpleanos', fn($q) => $q->whereMonth('fecha_nacimiento', now()->month))
            ->withCount(['ventas' => fn($q) => $q->activas()])
            ->withSum(['ventas as deuda_total' => fn($q) => $q->activas()], 'saldo_pendiente')
            ->orderBy('nombre')
            ->limit(200)
            ->get();

        $detalle = null;
        if ($this->detalleId) {
            $cliente = Cliente::where('barberia_id', $barberiaId)->find($this->detalleId);
            if ($cliente) {
                $ventas = Venta::where('barberia_id', $barberiaId)->where('cliente_id', $cliente->id)->activas();
                $detalle = [
                    'cliente'    => $cliente,
                    'visitas'    => (clone $ventas)->count(),
                    'gastado'    => (float) (clone $ventas)->sum('total'),
                    'ultima'     => (clone $ventas)->max('fecha'),
                    'membresia'  => $cliente->membresias()->vigentes()->orderBy('fecha_vencimiento')->first(),
                    'proximas'   => Cita::with('barbero')->where('cliente_id', $cliente->id)
                        ->whereDate('fecha', '>=', now()->toDateString())->where('estado', 'confirmada')
                        ->orderBy('fecha')->orderBy('hora_inicio')->limit(5)->get(),
                    'inasistencias' => Cita::where('cliente_id', $cliente->id)->where('estado', 'no_asistio')->count(),
                    'recientes'  => (clone $ventas)->with('items')->latest('fecha')->latest('id')->limit(8)->get(),
                ];
            }
        }

        return view('livewire.gestion-clientes', [
            'clientes' => $clientes,
            'detalle'  => $detalle,
            'esPro'    => (bool) auth()->user()->barberia?->es_pro,
        ])->layout('layouts.app', ['title' => 'Clientes']);
    }
}
