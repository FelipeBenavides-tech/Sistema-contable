<?php

namespace App\Livewire;

use App\Models\Barbero;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\Membresia;
use App\Models\Servicio;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
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

    // Cliente que se atiende (opcional; obligatorio para fiar) y su membresía
    public int    $clienteId     = 0;
    public int    $membresiaId   = 0;
    public string $buscarCliente = '';

    // Cita de la agenda que se está cobrando
    public int    $citaId        = 0;
    // Usar el saldo a favor del cliente en esta venta
    public bool   $usarSaldoFavor = false;

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function mount(): void
    {
        $this->resumenDia = Venta::resumenDia(null, $this->barberiaId());

        if ($citaId = (int) request()->query('cita')) {
            $this->cargarCita($citaId);
        }
    }

    /** Llena el carrito con los servicios, el barbero y el cliente de una cita. */
    public function cargarCita(int $citaId): void
    {
        $cita = Cita::with('servicios')
            ->where('barberia_id', $this->barberiaId())
            ->where('estado', 'confirmada')
            ->find($citaId);

        if (!$cita) {
            $this->addError('carrito', 'La cita ya no está pendiente por cobrar.');
            return;
        }

        $this->vaciarCarrito();
        $this->citaId    = $cita->id;
        $this->barberoId = $cita->barbero_id;
        $this->seleccionarCliente($cita->cliente_id);

        foreach ($cita->servicios as $item) {
            $existe = $item->servicio_id && Servicio::where('barberia_id', $this->barberiaId())
                ->where('activo', true)->whereKey($item->servicio_id)->exists();
            if ($existe) {
                $this->agregarServicio($item->servicio_id);
            }
        }
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

    private function clienteSeleccionado(): ?Cliente
    {
        return $this->clienteId
            ? Cliente::where('barberia_id', $this->barberiaId())->find($this->clienteId)
            : null;
    }

    /** Escoge el cliente que se atiende. Si tiene membresía vigente, se usa. */
    public function seleccionarCliente(int $clienteId): void
    {
        $cliente = Cliente::where('barberia_id', $this->barberiaId())->findOrFail($clienteId);

        $this->clienteId     = $cliente->id;
        $this->buscarCliente = '';
        $this->ventaExitosa  = false;
        $this->resetErrorBag('carrito');

        $membresia = Membresia::where('barberia_id', $this->barberiaId())
            ->where('cliente_id', $cliente->id)
            ->vigentes()
            ->orderBy('fecha_vencimiento')
            ->first();

        if ($membresia) {
            $this->membresiaId = $membresia->id;
            $this->cubrirPrimerServicio();
        } else {
            $this->quitarMembresia();
        }
    }

    public function quitarCliente(): void
    {
        $this->quitarMembresia();
        $this->clienteId = 0;
        $this->usarSaldoFavor = false;
        if ($this->metodoPago === 'fiado') {
            $this->metodoPago = 'efectivo';
        }
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
        $this->clienteId     = $membresia->cliente_id;
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
        $this->reset(['carrito', 'metodoPago', 'montoEfectivo', 'montoNequi', 'ventaExitosa', 'membresiaId', 'clienteId', 'buscarCliente', 'citaId', 'usarSaldoFavor']);
    }

    public function getTotalCarritoProperty(): float
    {
        return collect($this->carrito)->sum('subtotal');
    }

    // Anticipo pagado en línea de la cita que se cobra
    private function anticipoCita(): float
    {
        if (!$this->citaId) {
            return 0;
        }

        return (float) (Cita::where('barberia_id', $this->barberiaId())
            ->where('estado', 'confirmada')
            ->where('anticipo_estado', 'pagado')
            ->whereKey($this->citaId)
            ->value('anticipo') ?? 0);
    }

    /** [anticipo de la cita, saldo a favor usado, lo que falta por cobrar] para un total. */
    private function desglose(float $total): array
    {
        $anticipo = min($total, $this->anticipoCita());
        $saldo = 0.0;

        if ($this->usarSaldoFavor && $cliente = $this->clienteSeleccionado()) {
            $saldo = min((float) $cliente->saldo_favor, $total - $anticipo);
        }

        return [$anticipo, $saldo, max(0, $total - $anticipo - $saldo)];
    }

    public function getPorCobrarProperty(): float
    {
        return $this->desglose($this->totalCarrito)[2];
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
            'metodoPago' => 'required|in:efectivo,nequi,transferencia,combinado,fiado',
        ], [
            'carrito.required' => 'Agrega al menos un servicio o producto.',
            'carrito.min'      => 'Agrega al menos un servicio o producto.',
        ]);

        $items = $this->itemsDesdeBaseDeDatos();
        [$anticipo, $saldoFavor, $total] = $this->desglose((float) collect($items)->sum('subtotal'));

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

        if ($this->metodoPago === 'fiado' && !$this->clienteId) {
            $this->addError('carrito', 'Para fiar, primero escoge el cliente.');
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
            DB::transaction(function () use ($efectivo, $nequi, $items, $barbero, $anticipo, $saldoFavor) {
                $venta = Venta::registrar(
                    [
                        'metodo_pago'       => $this->metodoPago,
                        'monto_efectivo'    => $efectivo,
                        'monto_nequi'       => $nequi,
                        'monto_prepagado'   => $anticipo,
                        'saldo_favor_usado' => $saldoFavor,
                        'membresia_id'      => $this->membresiaId ?: null,
                        'cliente_id'        => $this->clienteId ?: null,
                    ],
                    $items,
                    $barbero,
                    $this->barberiaId()
                );

                // La cita de la agenda queda atendida y enlazada a su venta
                if ($this->citaId) {
                    Cita::where('barberia_id', $this->barberiaId())
                        ->where('estado', 'confirmada')
                        ->whereKey($this->citaId)
                        ->update([
                            'estado'          => 'atendida',
                            'venta_id'        => $venta->id,
                            'anticipo_estado' => DB::raw("CASE WHEN anticipo_estado = 'pagado' THEN 'aplicado' ELSE anticipo_estado END"),
                        ]);
                }
            });
        } catch (ValidationException $e) {
            $this->addError('carrito', collect($e->errors())->flatten()->first());
            return;
        }

        $this->resumenDia = Venta::resumenDia(null, $this->barberiaId());
        $this->reset(['carrito', 'metodoPago', 'montoEfectivo', 'montoNequi', 'membresiaId', 'clienteId', 'buscarCliente', 'citaId', 'usarSaldoFavor']);
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
        $clientesEncontrados = mb_strlen($termino) >= 2
            ? Cliente::where('barberia_id', $barberiaId)
                ->buscar($termino)
                ->with(['membresias' => fn($q) => $q->vigentes()->orderBy('fecha_vencimiento')])
                ->orderBy('nombre')
                ->limit(6)
                ->get()
            : collect();

        // Plan Pro: citas de hoy que faltan por cobrar
        $citasPorCobrar = auth()->user()->barberia?->es_pro
            ? Cita::with(['cliente', 'barbero'])
                ->where('barberia_id', $barberiaId)
                ->whereDate('fecha', now()->toDateString())
                ->where('estado', 'confirmada')
                ->orderBy('hora_inicio')
                ->get()
            : collect();

        return view('livewire.pos-venta', [
            'cliente'              => $this->clienteSeleccionado(),
            'membresia'            => $this->membresiaSeleccionada(),
            'clientesEncontrados'  => $clientesEncontrados,
            'citasPorCobrar'       => $citasPorCobrar,
            'desglose'             => $this->desglose($this->totalCarrito),
            'servicios'           => $servicios,
            'productosInventario' => $productosInventario,
            'barberos'            => $barberos,
            'ventasHoy'           => $ventasHoy,
        ])->layout('layouts.app', ['title' => 'Caja del día']);
    }
}
