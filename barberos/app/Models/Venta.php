<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Venta extends Model
{
    protected $fillable = [
        'barberia_id',
        'barbero_id',
        'total',
        'comision_barbero',
        'ganancia_local',
        'metodo_pago',
        'monto_efectivo',
        'monto_nequi',
        'fecha',
        'fue_editada',
        'fue_eliminada',
        'total_original',
        'inventario_restaurado',
    ];

    protected $casts = [
        'fecha'            => 'date',
        'total'            => 'decimal:2',
        'comision_barbero' => 'decimal:2',
        'ganancia_local'   => 'decimal:2',
        'monto_efectivo'   => 'decimal:2',
        'monto_nequi'      => 'decimal:2',
        'total_original'   => 'decimal:2',
        'fue_editada'           => 'boolean',
        'fue_eliminada'         => 'boolean',
        'inventario_restaurado' => 'boolean',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function barbero(): BelongsTo
    {
        return $this->belongsTo(Barbero::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VentaItem::class);
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(AuditoriaVenta::class);
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('fue_eliminada', false);
    }

    /**
     * La comisión del barbero se calcula solo sobre los servicios,
     * nunca sobre los productos ni sobre la venta de membresías.
     * Un servicio pagado con membresía cuenta a su precio normal.
     */
    public static function calcularComision(?Barbero $barbero, iterable $items): float
    {
        if (!$barbero) {
            return 0;
        }

        $baseServicios = collect($items)
            ->reject(fn($item) => !empty($item['es_producto']) || !empty($item['es_membresia']))
            ->sum(fn($item) => !empty($item['cubierto_membresia'])
                ? (float) $item['precio'] * (int) $item['cantidad']
                : (float) $item['subtotal']);

        return round($baseServicios * ($barbero->comision_porcentaje / 100), 2);
    }

    /**
     * Registra la venta, sus ítems, descuenta el stock y las visitas de
     * membresía en una sola transacción: si algo falla no queda nada a medias.
     *
     * @throws ValidationException si un producto no tiene stock suficiente
     *                             o la membresía no tiene visitas disponibles
     */
    public static function registrar(array $data, array $items, ?Barbero $barbero, int $barberiaId): self
    {
        return DB::transaction(function () use ($data, $items, $barbero, $barberiaId) {
            $visitas = collect($items)->where('cubierto_membresia', true)->sum('cantidad');
            $membresiaId = null;

            if ($visitas > 0) {
                $membresia = Membresia::where('barberia_id', $barberiaId)
                    ->lockForUpdate()
                    ->find($data['membresia_id'] ?? 0);

                if (!$membresia || !$membresia->vigente) {
                    throw ValidationException::withMessages([
                        'carrito' => 'La membresía del cliente ya no está vigente.',
                    ]);
                }

                if ($membresia->visitas_restantes < $visitas) {
                    throw ValidationException::withMessages([
                        'carrito' => "A la membresía solo le quedan {$membresia->visitas_restantes} visitas.",
                    ]);
                }

                $membresia->increment('visitas_usadas', $visitas);
                $membresiaId = $membresia->id;
            }

            foreach ($items as $item) {
                if (empty($item['inventario_id'])) {
                    continue;
                }

                $producto = Inventario::where('barberia_id', $barberiaId)
                    ->lockForUpdate()
                    ->find($item['inventario_id']);

                if (!$producto || $producto->stock_actual < $item['cantidad']) {
                    $disponible = $producto?->stock_actual ?? 0;
                    throw ValidationException::withMessages([
                        'carrito' => "Stock insuficiente de {$item['nombre_servicio']}: quedan {$disponible}.",
                    ]);
                }

                $producto->decrement('stock_actual', $item['cantidad']);
            }

            $total    = collect($items)->sum('subtotal');
            $comision = self::calcularComision($barbero, $items);

            $venta = self::create([
                'barberia_id'      => $barberiaId,
                'barbero_id'       => $barbero?->id,
                'total'            => $total,
                'comision_barbero' => $comision,
                'ganancia_local'   => $total - $comision,
                'metodo_pago'      => $data['metodo_pago'],
                'monto_efectivo'   => $data['monto_efectivo'] ?? 0,
                'monto_nequi'      => $data['monto_nequi'] ?? 0,
                'fecha'            => now()->toDateString(),
            ]);

            foreach ($items as $item) {
                $venta->items()->create([
                    'servicio_id'     => $item['servicio_id'] ?? null,
                    'inventario_id'   => $item['inventario_id'] ?? null,
                    'membresia_id'    => !empty($item['cubierto_membresia']) ? $membresiaId : ($item['membresia_id'] ?? null),
                    'es_producto'     => !empty($item['es_producto']),
                    'es_membresia'       => !empty($item['es_membresia']),
                    'cubierto_membresia' => !empty($item['cubierto_membresia']),
                    'nombre_servicio' => $item['nombre_servicio'],
                    'precio'          => $item['precio'],
                    'cantidad'        => $item['cantidad'],
                    'subtotal'        => $item['subtotal'],
                ]);
            }

            return $venta;
        });
    }

    public static function resumenDia(?string $fecha = null, int $barberiaId = 0): array
    {
        $fecha  = $fecha ?? now()->toDateString();
        $ventas = self::with('barbero')
            ->activas()
            ->where('barberia_id', $barberiaId)
            ->whereDate('fecha', $fecha)
            ->get();

        return [
            'total'           => $ventas->sum('total'),
            'total_efectivo'  => $ventas->sum('monto_efectivo'),
            'total_nequi'     => $ventas->sum('monto_nequi'),
            'cantidad_ventas' => $ventas->count(),
            'por_barbero'     => $ventas->groupBy('barbero_id')->map(function ($v) {
                return [
                    'nombre' => $v->first()->barbero?->nombre ?? 'Sin barbero',
                    'total'     => $v->sum('total'),
                    'comision'  => $v->sum('comision_barbero'),
                    'local'     => $v->sum('ganancia_local'),
                    'servicios' => $v->count(),
                ];
            })->values(),
        ];
    }

    public static function resumenMes(int $mes, int $anio, int $barberiaId): array
    {
        $ventas   = self::activas()
            ->where('barberia_id', $barberiaId)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->get();
        $gastos   = Gasto::totalMes($mes, $anio, $barberiaId);
        $ingresos = $ventas->sum('total');

        return [
            'ingresos'         => $ingresos,
            'gastos'           => $gastos,
            'ganancia_neta'    => $ingresos - $gastos,
            'total_comisiones' => $ventas->sum('comision_barbero'),
            'total_local'      => $ventas->sum('ganancia_local'),
            'cantidad_ventas'  => $ventas->count(),
        ];
    }
}
