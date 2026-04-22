<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected $casts = [
        'fecha'            => 'date',
        'total'            => 'decimal:2',
        'comision_barbero' => 'decimal:2',
        'ganancia_local'   => 'decimal:2',
        'monto_efectivo'   => 'decimal:2',
        'monto_nequi'      => 'decimal:2',
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

    public static function registrar(array $data, array $items, ?Barbero $barbero, int $barberiaId): self
    {
        $total = collect($items)->sum('subtotal');

        // Solo calcular comisión si hay barbero Y hay servicios (no solo productos)
        $tieneServicios = collect($items)->contains(fn($item) => empty($item['es_inventario']));
        $comision = ($barbero && $tieneServicios)
            ? round($total * ($barbero->comision_porcentaje / 100), 2)
            : 0;

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
                'nombre_servicio' => $item['nombre_servicio'],
                'precio'          => $item['precio'],
                'cantidad'        => $item['cantidad'],
                'subtotal'        => $item['subtotal'],
            ]);
        }

        return $venta;
    }

    public static function resumenDia(?string $fecha = null, int $barberiaId = 0): array
    {
        $fecha  = $fecha ?? now()->toDateString();
        $ventas = self::with('barbero')
            ->where('barberia_id', $barberiaId)
            ->where('fecha', $fecha)
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
        $ventas   = self::where('barberia_id', $barberiaId)
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
