<?php

namespace App\Livewire;

use App\Models\Barbero;
use App\Models\Inventario;
use App\Models\Membresia;
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

    // Cliente con membresía que se atiende en esta venta
    public int    $membresiaId   = 0;
    public string $buscarCliente = '';

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
                'cubierto'        => false,
            ];
            $this->cubrirPrimerServicio();
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
                'cubierto'        => false,
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
        $this->carrito[$key]['subtotal'] = !empty($this->carrito[$key]['cubierto'])
            ? 0
            : $this->carrito[$key]['precio'] * $this->carrito[$key]['cantidad'];
    }

    private function membresiaSeleccionada(): ?Membresia
    {
        if (!$this->membresiaId) {
            return null;
        }

        return Membresia::with('cliente')
            ->where('barberia_id', $this->barberiaId())
            ->find($this->membresiaId);
    }

    public function seleccionarMembresia(int $membresiaId): void
    {
        $membresia = Membresia::where('barberia_id', $this->barberiaId())
            ->vigentes()
            ->findOrFail($membresiaId);

        $this->membresiaId   = $membresia->id;
        $this->buscarCliente = '';
        $this->ventaExitosa  = false;
        $this->resetErrorBag('carrito');
        $this->cubrirPrimerServicio();
    }

    public function quitarMembresia(): void
    {
        $this->membresiaId = 0;

        foreach (array_keys($this->carrito) as $key) {
            $this->carrito[$key]['cubierto'] = false;
            $this->recalcularSubtotal($key);
        }
    }

    /** Marca o desmarca un servicio del carrito como pagado con la membresía. */
    public function alternarMembresia(string $key): void
    {
        if (!$this->membresiaId || !isset($this->carrito[$key]) || !empty($this->carrito[$key]['es_producto'])) {
            return;
        }

        $this->carrito[$key]['cubierto'] = empty($this->carrito[$key]['cubierto']);
        $this->recalcularSubtotal($key);
    }

    /**
     * Al atender a un cliente con membresía, el primer servicio del carrito
     * se paga con una visita (si ninguno lo está ya).
     */
    private function cubrirPrimerServicio(): void
    {
        if (!$this->membresiaId || collect($this->carrito)->contains('cubierto', true)) {
            return;
        }

        foreach ($this->carrito as $key => $item) {
            if (empty($item['es_producto'])) {
                $this->carrito[$key]['cubierto'] = true;
                $this->recalcularSubtotal($key);
                return;
            }
        }
    }

    public function vaciarCarrito(): void
    {
        $this->reset(['carrito', 'metodoPago', 'montoEfectivo', 'montoNequi', 'ventaExitosa', 'membresiaId', 'buscarCliente']);
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
                $esProducto = $servicio->categoria === 'producto';
                // Pagado con membresía: no se cobra, pero guarda el precio normal
                $cubierto = $this->membresiaId && !$esProducto && !empty($item['cubierto']);
                $items[] = [
                    'servicio_id'        => $servicio->id,
                    'inventario_id'      => null,
                    'es_producto'        => $esProducto,
                    'cubierto_membresia' => $cubierto,
                    'nombre_servicio'    => $servicio->nombre,
                    'precio'             => (float) $servicio->precio,
                    'cantidad'           => $cantidad,
                    'subtotal'           => $cubierto ? 0 : (float) $servicio->precio * $cantidad,
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
                    'membresia_id'   => $this->membresiaId ?: null,
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
        $this->reset(['carrito', 'metodoPago', 'montoEfectivo', 'montoNequi', 'membresiaId', 'buscarCliente']);
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
            ->whereDate('fecha', now()->toDateString())
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $termino = trim($this->buscarCliente);
        $clientesConMembresia = mb_strlen($termino) >= 2
            ? Membresia::with('cliente')
                ->where('barberia_id', $barberiaId)
                ->vigentes()
                ->whereHas('cliente', fn($q) => $q->where(fn($q) => $q
                    ->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('telefono', 'like', "%{$termino}%")))
                ->orderBy('fecha_vencimiento')
                ->limit(6)
                ->get()
            : collect();

        return view('livewire.pos-venta', [
            'membresia'            => $this->membresiaSeleccionada(),
            'clientesConMembresia' => $clientesConMembresia,
            'servicios'           => $servicios,
            'productosInventario' => $productosInventario,
            'barberos'            => $barberos,
            'ventasHoy'           => $ventasHoy,
        ])->layout('layouts.app', ['title' => 'Caja del día']);
    }
}
