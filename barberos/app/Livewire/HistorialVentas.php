<?php

namespace App\Livewire;

use App\Models\AuditoriaVenta;
use App\Models\Barbero;
use App\Models\Inventario;
use App\Models\Membresia;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class HistorialVentas extends Component
{
    public string $fecha = '';

    public bool  $mostrarAuditoria = false;
    public array $auditoriaData    = [];
    public ?int  $auditoriaVentaId = null;

    // Modal contraseña
    public bool   $mostrarModal     = false;
    #[Locked]
    public string $accion           = '';
    #[Locked]
    public ?int   $ventaId          = null;
    // Solo se activa después de verificar la contraseña
    #[Locked]
    public bool   $edicionAutorizada = false;
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
        if (!in_array($accion, ['editar', 'eliminar'], true)) {
            return;
        }

        Venta::where('barberia_id', $this->barberiaId())->findOrFail($ventaId);

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
            $this->edicionAutorizada = true;
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
        DB::transaction(function () {
            $venta = Venta::with('items')
                ->where('barberia_id', $this->barberiaId())
                ->lockForUpdate()
                ->findOrFail($this->ventaId);

            if ($venta->fue_eliminada) {
                return;
            }

            // Membresías: devolver las visitas usadas y anular la vendida
            foreach ($venta->items as $item) {
                if (!$item->membresia_id) {
                    continue;
                }

                $membresia = Membresia::where('barberia_id', $this->barberiaId())
                    ->lockForUpdate()
                    ->find($item->membresia_id);

                if (!$membresia) {
                    continue;
                }

                if ($item->es_membresia) {
                    $membresia->update(['anulada' => true]);
                } elseif ($item->cubierto_membresia) {
                    $membresia->update(['visitas_usadas' => max(0, $membresia->visitas_usadas - $item->cantidad)]);
                }
            }

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

            // Se marca como eliminada (no se borra) y deja de sumar en caja y reportes
            $venta->update([
                'fue_eliminada'    => true,
                'total_original'   => $venta->total,
                'total'            => 0,
                'comision_barbero' => 0,
                'ganancia_local'   => 0,
                'monto_efectivo'   => 0,
                'monto_nequi'      => 0,
            ]);
        });

        $this->motivo  = '';
        $this->ventaId = null;
    }

    private function ejecutarEditar(): void
    {
        $venta = Venta::with(['items.inventario', 'items.membresia'])
            ->where('barberia_id', $this->barberiaId())
            ->findOrFail($this->ventaId);

        $this->editBarberoId  = $venta->barbero_id ?? 0;
        $this->editMetodoPago = $venta->metodo_pago;
        $this->editEfectivo   = (float) $venta->monto_efectivo;
        $this->editNequi      = (float) $venta->monto_nequi;
        $this->editItems      = $venta->items->map(fn($item) => [
            'id'              => $item->id,
            'nombre_servicio' => $item->nombre_servicio,
            'precio'          => (float) $item->precio,
            'cantidad'        => $item->cantidad,
            'subtotal'        => (float) $item->subtotal,
            'es_producto'     => $item->es_producto,
            'es_membresia'    => $item->es_membresia,
            'cubierto'        => $item->cubierto_membresia,
            // Máximo posible = lo que ya tenía la venta + lo que queda en bodega
            // (o las visitas que le quedan a la membresía)
            'maximo'          => match (true) {
                $item->es_membresia       => $item->cantidad,
                $item->cubierto_membresia => $item->cantidad + ($item->membresia?->vigente ? $item->membresia->visitas_restantes : 0),
                (bool) $item->inventario_id => $item->cantidad + (int) ($item->inventario?->stock_actual ?? 0),
                default                   => null,
            },
        ])->values()->toArray();

        $this->resetErrorBag();
        $this->mostrarEdicion = true;
    }

    public function guardarEdicion(): void
    {
        if (!$this->edicionAutorizada || !$this->ventaId) {
            abort(403);
        }

        $this->validate([
            'editMetodoPago' => 'required|in:efectivo,nequi,transferencia,combinado',
            'editItems'      => 'required|array|min:1',
        ], [
            'editItems.required' => 'La venta debe tener al menos un ítem. Si no, elimínala.',
            'editItems.min'      => 'La venta debe tener al menos un ítem. Si no, elimínala.',
        ]);

        $barberiaId = $this->barberiaId();
        $barbero    = $this->editBarberoId
            ? Barbero::where('barberia_id', $barberiaId)->findOrFail($this->editBarberoId)
            : null;

        $cantidades = collect($this->editItems)->mapWithKeys(fn($i) => [(int) $i['id'] => max(0, (int) $i['cantidad'])]);

        try {
            DB::transaction(function () use ($barberiaId, $barbero, $cantidades) {
                $venta = Venta::with('items')
                    ->where('barberia_id', $barberiaId)
                    ->lockForUpdate()
                    ->findOrFail($this->ventaId);

                if ($venta->fue_eliminada) {
                    throw ValidationException::withMessages(['editItems' => 'Esta venta ya fue eliminada.']);
                }

                $totalAntes = $venta->total;
                $itemsFinales = [];

                foreach ($venta->items as $item) {
                    // La venta de una membresía no se edita: para anularla se elimina la venta
                    $nueva = $item->es_membresia ? $item->cantidad : $cantidades->get($item->id, 0);
                    $diferencia = $nueva - $item->cantidad;

                    // Ajustar las visitas de la membresía por la diferencia
                    if ($item->cubierto_membresia && $item->membresia_id && $diferencia !== 0) {
                        $membresia = Membresia::where('barberia_id', $barberiaId)
                            ->lockForUpdate()
                            ->find($item->membresia_id);

                        if ($membresia && $diferencia > 0
                            && (!$membresia->vigente || $membresia->visitas_restantes < $diferencia)) {
                            throw ValidationException::withMessages([
                                'editItems' => "La membresía no tiene visitas disponibles para más {$item->nombre_servicio}.",
                            ]);
                        }

                        $membresia?->update(['visitas_usadas' => max(0, $membresia->visitas_usadas + $diferencia)]);
                    }

                    // Ajustar el stock solo al guardar, y solo por la diferencia
                    if ($item->inventario_id && $diferencia !== 0) {
                        $producto = Inventario::where('barberia_id', $barberiaId)
                            ->lockForUpdate()
                            ->find($item->inventario_id);

                        if ($producto && $diferencia > 0) {
                            if ($producto->stock_actual < $diferencia) {
                                throw ValidationException::withMessages([
                                    'editItems' => "Stock insuficiente de {$item->nombre_servicio}: quedan {$producto->stock_actual}.",
                                ]);
                            }
                            $producto->decrement('stock_actual', $diferencia);
                        } elseif ($producto) {
                            $producto->increment('stock_actual', -$diferencia);
                        }
                    }

                    if ($nueva === 0) {
                        $item->delete();
                        continue;
                    }

                    $subtotal = $item->cubierto_membresia ? 0 : (float) $item->precio * $nueva;

                    $item->update([
                        'cantidad' => $nueva,
                        'subtotal' => $subtotal,
                    ]);

                    $itemsFinales[] = [
                        'precio'             => (float) $item->precio,
                        'cantidad'           => $nueva,
                        'subtotal'           => $subtotal,
                        'es_producto'        => $item->es_producto,
                        'es_membresia'       => $item->es_membresia,
                        'cubierto_membresia' => $item->cubierto_membresia,
                    ];
                }

                if (count($itemsFinales) === 0) {
                    throw ValidationException::withMessages([
                        'editItems' => 'La venta debe tener al menos un ítem. Si no, elimínala.',
                    ]);
                }

                $total    = collect($itemsFinales)->sum('subtotal');
                $comision = Venta::calcularComision($barbero, $itemsFinales);

                if ($this->editMetodoPago === 'combinado'
                    && abs(($this->editEfectivo + $this->editNequi) - $total) > 1) {
                    throw ValidationException::withMessages([
                        'editEfectivo' => 'En pago combinado, efectivo + Nequi debe ser igual al total.',
                    ]);
                }

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

                AuditoriaVenta::create([
                    'venta_id'            => $venta->id,
                    'barberia_id'         => $barberiaId,
                    'user_id'             => auth()->id(),
                    'accion'              => 'editada',
                    'motivo'              => $this->motivo,
                    'total_antes'         => $totalAntes,
                    'total_despues'       => $total,
                    'barbero_antes'       => $venta->barbero?->nombre ?? 'Venta directa',
                    'metodo_pago_antes'   => $venta->metodo_pago,
                    'metodo_pago_despues' => $this->editMetodoPago,
                ]);

                $venta->update([
                    'barbero_id'       => $barbero?->id,
                    'total'            => $total,
                    'total_original'   => $venta->total_original ?? $totalAntes,
                    'comision_barbero' => $comision,
                    'ganancia_local'   => $total - $comision,
                    'metodo_pago'      => $this->editMetodoPago,
                    'monto_efectivo'   => $efectivo,
                    'monto_nequi'      => $nequi,
                    'fue_editada'      => true,
                ]);
            });
        } catch (ValidationException $e) {
            foreach ($e->errors() as $campo => $mensajes) {
                $this->addError($campo, $mensajes[0]);
            }
            return;
        }

        $this->cancelarEdicion();
    }

    public function cancelarEdicion(): void
    {
        $this->edicionAutorizada = false;
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

        $auditorias = AuditoriaVenta::with('user')
            ->where('barberia_id', $this->barberiaId())
            ->where('venta_id', $ventaId)
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
                'restaurado'  => $venta->inventario_restaurado,
                'tiene_productos' => $venta->items->contains(fn($i) => $i->inventario_id),
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
                'fecha'        => $a->created_at->format('d/m/Y h:i a'),
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

        DB::transaction(function () use ($ventaId) {
            $venta = Venta::with('items')
                ->where('barberia_id', $this->barberiaId())
                ->lockForUpdate()
                ->findOrFail($ventaId);

            // Solo ventas eliminadas, y una sola vez
            if (!$venta->fue_eliminada || $venta->inventario_restaurado) {
                return;
            }

            foreach ($venta->items as $item) {
                if (!$item->inventario_id) {
                    continue;
                }

                Inventario::where('barberia_id', $this->barberiaId())
                    ->whereKey($item->inventario_id)
                    ->increment('stock_actual', $item->cantidad);
            }

            $venta->update(['inventario_restaurado' => true]);
        });

        $this->cerrarAuditoria();
    }

    public function aumentarCantidad(int $index): void
    {
        if (!isset($this->editItems[$index])) {
            return;
        }

        $maximo = $this->editItems[$index]['maximo'] ?? null;
        if ($maximo !== null && $this->editItems[$index]['cantidad'] >= $maximo) {
            $this->addError('editItems', !empty($this->editItems[$index]['cubierto'])
                ? "La membresía no tiene más visitas para: {$this->editItems[$index]['nombre_servicio']}"
                : "Sin stock disponible para: {$this->editItems[$index]['nombre_servicio']}");
            return;
        }

        $this->editItems[$index]['cantidad']++;
        $this->recalcularEditSubtotal($index);
    }

    public function disminuirCantidad(int $index): void
    {
        if (!isset($this->editItems[$index])) {
            return;
        }

        if (!empty($this->editItems[$index]['es_membresia'])) {
            $this->addError('editItems', 'La venta de una membresía no se puede quitar. Si fue un error, elimina la venta.');
            return;
        }

        if ($this->editItems[$index]['cantidad'] > 1) {
            $this->editItems[$index]['cantidad']--;
            $this->recalcularEditSubtotal($index);
        } else {
            // El stock se devuelve al guardar, no antes
            array_splice($this->editItems, $index, 1);
        }
    }

    private function recalcularEditSubtotal(int $index): void
    {
        $this->editItems[$index]['subtotal'] = !empty($this->editItems[$index]['cubierto'])
            ? 0
            : $this->editItems[$index]['precio'] * $this->editItems[$index]['cantidad'];
    }

    public function render()
    {
        $ventas = Venta::with(['barbero', 'items'])
            ->where('barberia_id', $this->barberiaId())
            ->when($this->fecha, fn($q) => $q->whereDate('fecha', $this->fecha))
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
