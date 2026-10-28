<?php

namespace App\Livewire;

use App\Models\Cliente;
use App\Models\PagoCartera;
use App\Models\Venta;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Clientes a los que se les fía: cuánto deben, cuántos servicios llevan
 * y registro de sus pagos.
 */
class GestionCartera extends Component
{
    public string $buscar = '';

    // Registrar pago
    public bool   $mostrarPago   = false;
    public ?int   $pagoClienteId = null;
    public float  $pagoValor     = 0;
    public string $pagoMetodo    = 'efectivo';
    public float  $pagoEfectivo  = 0;
    public float  $pagoNequi     = 0;
    public string $pagoNota      = '';

    public ?int   $detalleId = null;
    public string $exito     = '';

    // Configuración del límite
    public int  $limite = 4;
    public bool $editandoLimite = false;

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function mount(): void
    {
        $this->limite = (int) auth()->user()->barberia->limite_fiado;
    }

    public function guardarLimite(): void
    {
        $this->validate(['limite' => 'required|integer|min:1|max:50'], [], ['limite' => 'límite']);

        auth()->user()->barberia->update(['limite_fiado' => $this->limite]);

        // Recalcula quién queda bloqueado con el nuevo límite
        Cliente::where('barberia_id', $this->barberiaId())->enCartera()->get()
            ->each->actualizarBloqueoFiado();

        $this->editandoLimite = false;
    }

    public function abrirPago(int $clienteId): void
    {
        $this->resetErrorBag();
        $cliente = Cliente::where('barberia_id', $this->barberiaId())->findOrFail($clienteId);

        $this->pagoClienteId = $cliente->id;
        $this->pagoValor     = $cliente->deuda;
        $this->pagoMetodo    = 'efectivo';
        $this->pagoEfectivo  = 0;
        $this->pagoNequi     = 0;
        $this->pagoNota      = '';
        $this->exito         = '';
        $this->detalleId     = null;
        $this->mostrarPago   = true;
    }

    public function registrarPago(): void
    {
        $this->validate([
            'pagoValor'  => 'required|numeric|min:1',
            'pagoMetodo' => 'required|in:efectivo,nequi,transferencia,combinado',
            'pagoNota'   => 'nullable|max:200',
        ], [], ['pagoValor' => 'valor']);

        $cliente = Cliente::where('barberia_id', $this->barberiaId())->findOrFail($this->pagoClienteId);

        try {
            PagoCartera::registrar($cliente, (float) $this->pagoValor, $this->pagoMetodo, trim($this->pagoNota) ?: null, (float) $this->pagoEfectivo, (float) $this->pagoNequi);
        } catch (ValidationException $e) {
            $this->addError('pagoValor', collect($e->errors())->flatten()->first());
            return;
        }

        $cliente->refresh();
        $this->exito = $cliente->deuda > 0
            ? "Abono registrado. {$cliente->nombre} aún debe $" . number_format($cliente->deuda, 0, ',', '.') . '.'
            : "{$cliente->nombre} quedó a paz y salvo. Ya se le puede volver a fiar.";
        $this->cerrarPago();
    }

    public function cerrarPago(): void
    {
        $this->resetErrorBag();
        $this->mostrarPago   = false;
        $this->pagoClienteId = null;
    }

    public function verDetalle(int $clienteId): void
    {
        $this->detalleId = Cliente::where('barberia_id', $this->barberiaId())->findOrFail($clienteId)->id;
    }

    public function cerrarDetalle(): void
    {
        $this->detalleId = null;
    }

    public function render()
    {
        $barberiaId = $this->barberiaId();
        $termino = trim($this->buscar);

        $clientes = Cliente::with('barberia')
            ->where('barberia_id', $barberiaId)
            ->where(fn($q) => $q->where('permite_fiado', true)->orWhereHas('ventas', fn($v) => $v->activas()->where('saldo_pendiente', '>', 0)))
            ->when($termino !== '', fn($q) => $q->buscar($termino))
            ->withSum(['ventas as deuda_total' => fn($q) => $q->activas()], 'saldo_pendiente')
            ->withMax(['ventas as ultimo_fiado' => fn($q) => $q->activas()->where('metodo_pago', 'fiado')], 'fecha')
            ->get()
            // Primero los que deben pagar ya, luego los que más deben
            ->sortBy([
                fn($a, $b) => $b->fiado_bloqueado <=> $a->fiado_bloqueado,
                fn($a, $b) => (float) $b->deuda_total <=> (float) $a->deuda_total,
            ])
            ->values();

        $detalle = null;
        if ($this->detalleId) {
            $cliente = Cliente::where('barberia_id', $barberiaId)->find($this->detalleId);
            if ($cliente) {
                $detalle = [
                    'cliente' => $cliente,
                    'fiados'  => Venta::with(['items', 'barbero'])->activas()
                        ->where('cliente_id', $cliente->id)->where('metodo_pago', 'fiado')
                        ->latest('fecha')->latest('id')->limit(20)->get(),
                    'pagos'   => $cliente->pagosCartera()->latest('fecha')->latest('id')->limit(20)->get(),
                ];
            }
        }

        return view('livewire.gestion-cartera', [
            'clientes'   => $clientes,
            'totalDeuda' => $clientes->sum(fn($c) => (float) $c->deuda_total),
            'deben'      => $clientes->filter(fn($c) => (float) $c->deuda_total > 0)->count(),
            'bloqueados' => $clientes->where('fiado_bloqueado', true)->count(),
            'pagoDe'     => $this->pagoClienteId ? $clientes->firstWhere('id', $this->pagoClienteId) : null,
            'detalle'    => $detalle,
        ])->layout('layouts.app', ['title' => 'Cartera de clientes']);
    }
}
