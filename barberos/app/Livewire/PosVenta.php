<?php

namespace App\Livewire;

use App\Models\Barbero;
use App\Models\Inventario;
use App\Models\Servicio;
use App\Models\Venta;
use Livewire\Component;

class PosVenta extends Component
{
    public array  $carrito               = [];
    public $barberoId                    = 0;
    public string $metodoPago            = 'efectivo';
    public float  $montoEfectivo         = 0;
    public float  $montoNequi            = 0;
    public bool   $ventaExitosa          = false;
    public string $categoria             = 'todos';
    public array  $resumenDia            = [];
    public array  $serviciosSeleccionados = [];

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function mount(): void
    {
        $this->resumenDia = Venta::resumenDia(null, $this->barberiaId());
    }

    public function setBarbero($value): void
    {
        $this->barberoId = (int) $value;
    }

    public function agregarServicio(int $servicioId): void
    {
        $servicio = Servicio::findOrFail($servicioId);

        if (isset($this->carrito[$servicioId])) {
            $this->carrito[$servicioId]['cantidad']++;
            $this->carrito[$servicioId]['subtotal'] =
                $this->carrito[$servicioId]['precio'] * $this->carrito[$servicioId]['cantidad'];
        } else {
            $this->carrito[$servicioId] = [
                'servicio_id'     => $servicio->id,
                'inventario_id'   => null,
                'nombre_servicio' => $servicio->nombre,
                'precio'          => (float) $servicio->precio,
                'cantidad'        => 1,
                'subtotal'        => (float) $servicio->precio,
                'es_inventario'   => false,
            ];
        }

        $this->serviciosSeleccionados[$servicioId] = true;
    }

    public function agregarProductoInventario(int $inventarioId): void
    {
        $producto = Inventario::where('barberia_id', $this->barberiaId())
            ->findOrFail($inventarioId);

        if ($producto->stock_actual <= 0) {
            $this->addError('carrito', "Sin stock: {$producto->nombre}");
            return;
        }

        $key = 'inv_' . $inventarioId;

        if (isset($this->carrito[$key])) {
            if ($this->carrito[$key]['cantidad'] >= $producto->stock_actual) {
                $this->addError('carrito', "Stock insuficiente: solo hay {$producto->stock_actual} unidades.");
                return;
            }
            $this->carrito[$key]['cantidad']++;
            $this->carrito[$key]['subtotal'] =
                $this->carrito[$key]['precio'] * $this->carrito[$key]['cantidad'];
        } else {
            $this->carrito[$key] = [
                'servicio_id'     => null,
                'inventario_id'   => $producto->id,
                'nombre_servicio' => $producto->nombre,
                'precio'          => (float) $producto->precio_venta,
                'cantidad'        => 1,
                'subtotal'        => (float) $producto->precio_venta,
                'es_inventario'   => true,
            ];
        }

        $this->serviciosSeleccionados[$key] = true;
    }

    public function quitarItem(string $key): void
    {
        $key = (string) $key;
        if (array_key_exists($key, $this->carrito)) {
            $newCarrito = [];
            foreach ($this->carrito as $k => $v) {
                if ((string)$k !== $key) {
                    $newCarrito[$k] = $v;
                }
            }
            $this->carrito = $newCarrito;

            $newSeleccionados = [];
            foreach ($this->serviciosSeleccionados as $k => $v) {
                if ((string)$k !== $key) {
                    $newSeleccionados[$k] = $v;
                }
            }
            $this->serviciosSeleccionados = $newSeleccionados;
        }
    }

    public function vaciarCarrito(): void
    {
        $this->carrito                = [];
        $this->metodoPago             = 'efectivo';
        $this->montoEfectivo          = 0;
        $this->montoNequi             = 0;
        $this->ventaExitosa           = false;
        $this->serviciosSeleccionados = [];
    }

    public function getTotalCarritoProperty(): float
    {
        return collect($this->carrito)->sum('subtotal');
    }

    public function cobrar(): void
    {
        $this->validate([
            'carrito'    => 'required|min:1',
            'metodoPago' => 'required|in:efectivo,nequi,transferencia,combinado',
        ], [
            'carrito.min' => 'Agrega al menos un servicio o producto.',
        ]);

        // Validar barbero solo si hay servicios (no solo productos de inventario)
        $tieneServicios = collect($this->carrito)->contains(fn($item) => empty($item['es_inventario']));
        if ($tieneServicios && ($this->barberoId == 0)) {
            $this->addError('barberoId', 'Selecciona el barbero para los servicios.');
            return;
        }


        $soloProductos = collect($this->carrito)->every(fn($item) => !empty($item['es_inventario']));
        $barbero = (!$soloProductos && $this->barberoId)
            ? Barbero::findOrFail($this->barberoId)
            : null;

        $efectivo = match ($this->metodoPago) {
            'efectivo'  => $this->totalCarrito,
            'combinado' => $this->montoEfectivo,
            default     => 0,
        };

        $nequi = match ($this->metodoPago) {
            'nequi', 'transferencia' => $this->totalCarrito,
            'combinado'              => $this->montoNequi,
            default                  => 0,
        };

        // Preparar items para la venta
        $itemsVenta = collect($this->carrito)->map(function ($item) {
            return [
                'servicio_id'     => $item['servicio_id'] ?? null,
                'nombre_servicio' => $item['nombre_servicio'],
                'precio'          => $item['precio'],
                'cantidad'        => $item['cantidad'],
                'subtotal'        => $item['subtotal'],
            ];
        })->values()->toArray();

        Venta::registrar(
            [
                'metodo_pago'    => $this->metodoPago,
                'monto_efectivo' => $efectivo,
                'monto_nequi'    => $nequi,
            ],
            $itemsVenta,
            $barbero,
            $this->barberiaId()
        );

        // Descontar stock de productos del inventario
        foreach ($this->carrito as $item) {
            if (!empty($item['es_inventario']) && !empty($item['inventario_id'])) {
                $inv = Inventario::find($item['inventario_id']);
                if ($inv) {
                    $inv->decrement('stock_actual', $item['cantidad']);
                }
            }
        }

        $this->resumenDia             = Venta::resumenDia(null, $this->barberiaId());
        $this->ventaExitosa           = true;
        $this->carrito                = [];
        $this->serviciosSeleccionados = [];
        $this->metodoPago             = 'efectivo';
        $this->montoEfectivo          = 0;
        $this->montoNequi             = 0;
    }

    public function render()
    {
        $barberiaId = $this->barberiaId();

        $servicios = Servicio::query()
            ->where('barberia_id', $barberiaId)
            ->where('activo', true)
            ->when(
                $this->categoria === 'servicio' || $this->categoria === 'todos',
                fn($q) => $q
            )
            ->when(
                $this->categoria === 'producto',
                fn($q) => $q->whereRaw('0=1')
            )
            ->orderBy('nombre')
            ->get();

        $productosInventario = Inventario::query()
            ->where('barberia_id', $barberiaId)
            ->where('categoria', 'venta')
            ->where('precio_venta', '>', 0)
            ->when(
                $this->categoria === 'servicio',
                fn($q) => $q->whereRaw('0=1')
            )
            ->orderBy('nombre')
            ->get();

        $barberos = Barbero::where('barberia_id', $barberiaId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $ventasHoy = Venta::with(['barbero', 'items'])
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
