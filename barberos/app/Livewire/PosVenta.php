<?php

namespace App\Livewire;

use App\Models\Barbero;
use App\Models\Inventario;
use App\Models\Servicio;
use App\Models\Venta;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PosVenta extends Component
{
    public array  $carrito       = [];
    public int    $barberoId     = 0;
    public string $metodoPago    = 'efectivo';
    public float  $montoEfectivo = 0;
    public float  $montoNequi    = 0;
    public bool   $ventaExitosa  = false;
    public string $categoria     = 'todos';
    public array  $resumenDia    = [];

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function mount(): void
    {
        $this->resumenDia = Venta::resumenDia(null, $this->barberiaId());
    }

    public function agregarServicio(int $servicioId): void
    {
        $servicio = Servicio::where('barberia_id', $this->barberiaId())
            ->where('activo', true)
            ->findOrFail($servicioId);

        $key = 'srv_' . $servicio->id;
        $this->ventaExitosa = false;

        if (isset($this->carrito[$key])) {
            $this->carrito[$key]['cantidad']++;
        } else {
            $this->carrito[$key] = [
                'servicio_id'     => $servicio->id,
                'inventario_id'   => null,
                'nombre_servicio' => $servicio->nombre,
                'precio'          => (float) $servicio->precio,
                'cantidad'        => 1,
                'es_producto'     => $servicio->categoria === 'producto',
            ];
        }

        $this->recalcularSubtotal($key);
    }

    public function agregarProductoInventario(int $inventarioId): void
    {
        $producto = Inventario::where('barberia_id', $this->barberiaId())
            ->findOrFail($inventarioId);

        $key = 'inv_' . $producto->id;
        $this->ventaExitosa = false;
        $enCarrito = $this->carrito[$key]['cantidad'] ?? 0;

        if ($enCarrito >= $producto->stock_actual) {
            $this->addError('carrito', $producto->stock_actual <= 0
                ? "Sin stock: {$producto->nombre}"
                : "Stock insuficiente: solo hay {$producto->stock_actual} de {$producto->nombre}.");
            return;
        }

        if (isset($this->carrito[$key])) {
            $this->carrito[$key]['cantidad']++;
        } else {
            $this->carrito[$key] = [
                'servicio_id'     => null,
                'inventario_id'   => $producto->id,
                'nombre_servicio' => $producto->nombre,
                'precio'          => (float) $producto->precio_venta,
                'cantidad'        => 1,
                'es_producto'     => true,
            ];
        }

        $this->recalcularSubtotal($key);
    }

    public function restarItem(string $key): void
    {
        if (!isset($this->carrito[$key])) {
            return;
        }

        if ($this->carrito[$key]['cantidad'] <= 1) {
            $this->quitarItem($key);
            return;
        }

        $this->carrito[$key]['cantidad']--;
        $this->recalcularSubtotal($key);
    }

    public function quitarItem(string $key): void
    {
        unset($this->carrito[$key]);
    }

    private function recalcularSubtotal(string $key): void
    {
        $this->carrito[$key]['subtotal'] = $this->carrito[$key]['precio'] * $this->carrito[$key]['cantidad'];
    }

    public function vaciarCarrito(): void
    {
        $this->reset(['carrito', 'metodoPago', 'montoEfectivo', 'montoNequi', 'ventaExitosa']);
    }

    public function getTotalCarritoProperty(): float
    {
        return collect($this->carrito)->sum('subtotal');
    }

    /**
     * Arma los ítems de la venta leyendo precios y nombres de la base de datos
     * (no del navegador), siempre dentro de la barbería del usuario.
     */
    private function itemsDesdeBaseDeDatos(): array
    {
        $barberiaId = $this->barberiaId();
        $items = [];

        foreach ($this->carrito as $item) {
            $cantidad = max(1, (int) $item['cantidad']);

            if (!empty($item['inventario_id'])) {
                $producto = Inventario::where('barberia_id', $barberiaId)->findOrFail($item['inventario_id']);
                $items[] = [
                    'servicio_id'     => null,
                    'inventario_id'   => $producto->id,
                    'es_producto'     => true,
                    'nombre_servicio' => $producto->nombre,
                    'precio'          => (float) $producto->precio_venta,
                    'cantidad'        => $cantidad,
                    'subtotal'        => (float) $producto->precio_venta * $cantidad,
                ];
            } else {
                $servicio = Servicio::where('barberia_id', $barberiaId)->findOrFail($item['servicio_id']);
                $items[] = [
                    'servicio_id'     => $servicio->id,
                    'inventario_id'   => null,
                    'es_producto'     => $servicio->categoria === 'producto',
                    'nombre_servicio' => $servicio->nombre,
                    'precio'          => (float) $servicio->precio,
                    'cantidad'        => $cantidad,
                    'subtotal'        => (float) $servicio->precio * $cantidad,
                ];
            }
        }

        return $items;
    }

    public function cobrar(): void
    {
        $this->validate([
            'carrito'    => 'required|array|min:1',
            'metodoPago' => 'required|in:efectivo,nequi,transferencia,combinado',
        ], [
            'carrito.required' => 'Agrega al menos un servicio o producto.',
            'carrito.min'      => 'Agrega al menos un servicio o producto.',
        ]);

        $items = $this->itemsDesdeBaseDeDatos();
        $total = collect($items)->sum('subtotal');

        // El barbero es obligatorio solo si hay servicios
        $tieneServicios = collect($items)->contains(fn($item) => empty($item['es_producto']));
        if ($tieneServicios && $this->barberoId === 0) {
            $this->addError('barberoId', 'Selecciona el barbero que hizo el servicio.');
            return;
        }

        $barbero = $this->barberoId
            ? Barbero::where('barberia_id', $this->barberiaId())->findOrFail($this->barberoId)
            : null;

        if ($this->metodoPago === 'combinado'
            && abs(($this->montoEfectivo + $this->montoNequi) - $total) > 1) {
            $this->addError('montoEfectivo', 'En pago combinado, efectivo + Nequi debe ser igual al total.');
            return;
        }

        $efectivo = match ($this->metodoPago) {
            'efectivo'  => $total,
            'combinado' => $this->montoEfectivo,
            default     => 0,
        };

        $nequi = match ($this->metodoPago) {
            'nequi', 'transferencia' => $total,
            'combinado'              => $this->montoNequi,
            default                  => 0,
        };

        try {
            Venta::registrar(
                [
                    'metodo_pago'    => $this->metodoPago,
                    'monto_efectivo' => $efectivo,
                    'monto_nequi'    => $nequi,
                ],
                $items,
                $barbero,
                $this->barberiaId()
            );
        } catch (ValidationException $e) {
            $this->addError('carrito', collect($e->errors())->flatten()->first());
            return;
        }

        $this->resumenDia = Venta::resumenDia(null, $this->barberiaId());
        $this->reset(['carrito', 'metodoPago', 'montoEfectivo', 'montoNequi']);
        $this->ventaExitosa = true;
    }

    public function render()
    {
        $barberiaId = $this->barberiaId();

        $servicios = Servicio::query()
            ->where('barberia_id', $barberiaId)
            ->where('activo', true)
            ->when($this->categoria === 'servicio', fn($q) => $q->where('categoria', 'servicio'))
            ->when($this->categoria === 'producto', fn($q) => $q->where('categoria', 'producto'))
            ->orderBy('nombre')
            ->get();

        $productosInventario = $this->categoria === 'servicio'
            ? collect()
            : Inventario::query()
                ->where('barberia_id', $barberiaId)
                ->where('categoria', 'venta')
                ->where('precio_venta', '>', 0)
                ->orderBy('nombre')
                ->get();

        $barberos = Barbero::where('barberia_id', $barberiaId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $ventasHoy = Venta::with(['barbero', 'items'])
            ->activas()
            ->where('barberia_id', $barberiaId)
            ->where('fecha', now()->toDateString())
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('livewire.pos-venta', [
            'servicios'           => $servicios,
            'productosInventario' => $productosInventario,
            'barberos'            => $barberos,
            'ventasHoy'           => $ventasHoy,
        ])->layout('layouts.app', ['title' => 'Caja del día']);
    }
}
