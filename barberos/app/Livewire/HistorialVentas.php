<?php

namespace App\Livewire;

use App\Models\AuditoriaVenta;
use App\Models\Barbero;
use App\Models\Venta;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class HistorialVentas extends Component
{
    public string $fecha = '';

    public bool  $mostrarAuditoria = false;
    public array $auditoriaData    = [];
    public ?int  $auditoriaVentaId = null;

    // Modal contraseña
    public bool   $mostrarModal     = false;
    public string $accion           = '';
    public ?int   $ventaId          = null;
    public string $passwordConfirm  = '';
    public string $errorPassword    = '';

    // Motivo obligatorio
    public string $motivo           = '';

    // Edición
    public bool   $mostrarEdicion   = false;
    public int    $editBarberoId    = 0;
    public string $editMetodoPago   = 'efectivo';
    public float  $editEfectivo     = 0;
    public float  $editNequi        = 0;
    public array  $editItems        = [];

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function mount(): void
    {
        $this->fecha = now()->toDateString();
    }

    public function confirmarAccion(int $ventaId, string $accion): void
    {
        $this->ventaId        = $ventaId;
        $this->accion         = $accion;
        $this->passwordConfirm = '';
        $this->errorPassword  = '';
        $this->motivo         = '';
        $this->mostrarModal   = true;
    }

    public function verificarPassword(): void
    {
        // Validar motivo
        $this->validate([
            'motivo' => [
                'required',
                'min:10',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/',
            ],
        ], [
            'motivo.required' => 'El motivo es obligatorio.',
            'motivo.min'      => 'El motivo debe tener al menos 10 caracteres.',
            'motivo.regex'    => 'El motivo solo puede contener letras y espacios, sin números ni símbolos.',
        ]);

        if (!Hash::check($this->passwordConfirm, auth()->user()->password)) {
            $this->errorPassword = 'Contraseña incorrecta.';
            return;
        }

        $this->mostrarModal  = false;
        $this->errorPassword = '';

        if ($this->accion === 'eliminar') {
            $this->ejecutarEliminar();
        } elseif ($this->accion === 'editar') {
            $this->ejecutarEditar();
        }
    }

    public function cancelarModal(): void
    {
        $this->mostrarModal   = false;
        $this->passwordConfirm = '';
        $this->errorPassword  = '';
        $this->ventaId        = null;
        $this->accion         = '';
        $this->motivo         = '';
    }

    private function ejecutarEliminar(): void
    {
        $venta = Venta::where('barberia_id', $this->barberiaId())->findOrFail($this->ventaId);

        // Guardar en auditoría ANTES de eliminar
        AuditoriaVenta::create([
            'venta_id'          => $venta->id,
            'barberia_id'       => $this->barberiaId(),
            'user_id'           => auth()->id(),
            'accion'            => 'eliminada',
            'motivo'            => $this->motivo,
            'total_antes'       => $venta->total,
            'total_despues'     => 0,
            'barbero_antes'     => $venta->barbero?->nombre ?? 'Venta directa',
            'metodo_pago_antes' => $venta->metodo_pago,
        ]);

        // Marcar como eliminada y poner total en 0 en vez de borrar
        $venta->update([
            'fue_eliminada'  => true,
            'total_original' => $venta->total,
            'total'          => 0,
            'comision_barbero' => 0,
            'ganancia_local'   => 0,
        ]);

        $this->motivo  = '';
        $this->ventaId = null;
    }

    private function ejecutarEditar(): void
    {
        $venta = Venta::with('items')
            ->where('barberia_id', $this->barberiaId())
            ->findOrFail($this->ventaId);

        $this->editBarberoId  = $venta->barbero_id ?? 0;
        $this->editMetodoPago = $venta->metodo_pago;
        $this->editEfectivo   = $venta->monto_efectivo;
        $this->editNequi      = $venta->monto_nequi;
        $this->editItems      = $venta->items->map(function ($item) {
            return [
                'id'              => $item->id,
                'nombre_servicio' => $item->nombre_servicio,
                'precio'          => $item->precio,
                'cantidad'        => $item->cantidad,
                'subtotal'        => $item->subtotal,
            ];
        })->toArray();

        $this->mostrarEdicion = true;
    }

    public function guardarEdicion(): void
    {
        $this->validate([
            'editMetodoPago' => 'required|in:efectivo,nequi,transferencia,combinado',
        ]);

        $venta      = Venta::where('barberia_id', $this->barberiaId())->findOrFail($this->ventaId);
        $totalAntes = $venta->total;

        $barbero  = $this->editBarberoId ? Barbero::findOrFail($this->editBarberoId) : null;
        $total    = collect($this->editItems)->sum('subtotal');
        $comision = $barbero ? round($total * ($barbero->comision_porcentaje / 100), 2) : 0;

        $efectivo = match ($this->editMetodoPago) {
            'efectivo'  => $total,
            'combinado' => $this->editEfectivo,
            default     => 0,
        };

        $nequi = match ($this->editMetodoPago) {
            'nequi', 'transferencia' => $total,
            'combinado'              => $this->editNequi,
            default                  => 0,
        };

        // Guardar auditoría
        AuditoriaVenta::create([
            'venta_id'           => $venta->id,
            'barberia_id'        => $this->barberiaId(),
            'user_id'            => auth()->id(),
            'accion'             => 'editada',
            'motivo'             => $this->motivo,
            'total_antes'        => $totalAntes,
            'total_despues'      => $total,
            'barbero_antes'      => $venta->barbero?->nombre ?? 'Venta directa',
            'metodo_pago_antes'  => $venta->metodo_pago,
            'metodo_pago_despues' => $this->editMetodoPago,
        ]);

        $venta->update([
            'barbero_id'       => $this->editBarberoId ?: null,
            'total'            => $total,
            'total_original'   => $venta->total_original ?? $totalAntes,
            'comision_barbero' => $comision,
            'ganancia_local'   => $total - $comision,
            'metodo_pago'      => $this->editMetodoPago,
            'monto_efectivo'   => $efectivo,
            'monto_nequi'      => $nequi,
            'fue_editada'      => true,
        ]);

        $this->mostrarEdicion = false;
        $this->ventaId        = null;
        $this->motivo         = '';
    }

    public function cancelarEdicion(): void
    {
        $this->mostrarEdicion = false;
        $this->ventaId        = null;
        $this->editItems      = [];
        $this->motivo         = '';
    }

    public function verAuditoria(int $ventaId): void
    {
        $venta = Venta::with(['items', 'barbero'])
            ->where('barberia_id', $this->barberiaId())
            ->findOrFail($ventaId);

        $auditorias = AuditoriaVenta::where('venta_id', $ventaId)
            ->orderByDesc('created_at')
            ->get();

        $this->auditoriaVentaId = $ventaId;
        $this->auditoriaData    = [
            'venta'      => [
                'id'          => $venta->id,
                'total'       => $venta->total,
                'total_orig'  => $venta->total_original,
                'eliminada'   => $venta->fue_eliminada,
                'editada'     => $venta->fue_editada,
                'metodo'      => $venta->metodo_pago,
                'barbero'     => $venta->barbero?->nombre ?? 'Venta directa',
                'items'       => $venta->items->map(fn($i) => [
                    'nombre'   => $i->nombre_servicio,
                    'precio'   => $i->precio,
                    'cantidad' => $i->cantidad,
                    'subtotal' => $i->subtotal,
                ])->toArray(),
            ],
            'auditorias' => $auditorias->map(fn($a) => [
                'accion'       => $a->accion,
                'motivo'       => $a->motivo,
                'total_antes'  => $a->total_antes,
                'total_despues' => $a->total_despues,
                'barbero_antes' => $a->barbero_antes,
                'metodo_antes' => $a->metodo_pago_antes,
                'metodo_despues' => $a->metodo_pago_despues,
                'fecha'        => $a->created_at->setTimezone('America/Bogota')->format('d/m/Y h:i a'),
                'usuario'      => $a->user?->name ?? 'Sistema',
            ])->toArray(),
        ];

        $this->mostrarAuditoria = true;
    }

    public function cerrarAuditoria(): void
    {
        $this->mostrarAuditoria = false;
        $this->auditoriaData    = [];
        $this->auditoriaVentaId = null;
    }

    public function restaurarInventario(int $ventaId, bool $restaurar): void
    {
        if (!$restaurar) {
            $this->cerrarAuditoria();
            return;
        }

        $venta = Venta::with('items')
            ->where('barberia_id', $this->barberiaId())
            ->findOrFail($ventaId);

        foreach ($venta->items as $item) {
            // Buscar en inventario por nombre
            $producto = \App\Models\Inventario::where('barberia_id', $this->barberiaId())
                ->where('nombre', $item->nombre_servicio)
                ->where('categoria', 'venta')
                ->first();

            if ($producto) {
                $producto->increment('stock_actual', $item->cantidad);
            }
        }

        $this->cerrarAuditoria();
    }

    public function aumentarCantidad(int $index): void
    {
        if (isset($this->editItems[$index])) {
            // Verificar stock si es producto de inventario
            $nombre   = $this->editItems[$index]['nombre_servicio'];
            $producto = \App\Models\Inventario::where('barberia_id', $this->barberiaId())
                ->where('nombre', $nombre)
                ->where('categoria', 'venta')
                ->first();

            if ($producto && $producto->stock_actual <= 0) {
                $this->addError('editItems', "Sin stock disponible para: {$nombre}");
                return;
            }

            $this->editItems[$index]['cantidad']++;
            $this->editItems[$index]['subtotal'] =
                $this->editItems[$index]['precio'] * $this->editItems[$index]['cantidad'];

            // Descontar del inventario si aplica
            if ($producto) {
                $producto->decrement('stock_actual', 1);
            }
        }
    }

    public function disminuirCantidad(int $index): void
    {
        if (isset($this->editItems[$index])) {
            $nombre   = $this->editItems[$index]['nombre_servicio'];
            $producto = \App\Models\Inventario::where('barberia_id', $this->barberiaId())
                ->where('nombre', $nombre)
                ->where('categoria', 'venta')
                ->first();

            if ($this->editItems[$index]['cantidad'] > 1) {
                $this->editItems[$index]['cantidad']--;
                $this->editItems[$index]['subtotal'] =
                    $this->editItems[$index]['precio'] * $this->editItems[$index]['cantidad'];

                // Devolver al inventario si aplica
                if ($producto) {
                    $producto->increment('stock_actual', 1);
                }
            } else {
                // Si llega a 0 eliminar el item y devolver 1 unidad al inventario
                array_splice($this->editItems, $index, 1);

                if ($producto) {
                    $producto->increment('stock_actual', 1);
                }
            }
        }
    }

    public function render()
    {
        $ventas = Venta::with(['barbero', 'items'])
            ->where('barberia_id', $this->barberiaId())
            ->when($this->fecha, fn($q) => $q->where('fecha', $this->fecha))
            ->orderByDesc('created_at')
            ->get();

        $barberos = Barbero::where('barberia_id', $this->barberiaId())
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('livewire.historial-ventas', [
            'ventas'   => $ventas,
            'barberos' => $barberos,
        ])->layout('layouts.app', ['title' => 'Historial de ventas']);
    }
}
