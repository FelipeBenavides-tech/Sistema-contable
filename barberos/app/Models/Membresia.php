<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Membresia extends Model
{
    protected $fillable = [
        'barberia_id',
        'cliente_id',
        'plan_membresia_id',
        'venta_id',
        'nombre_plan',
        'precio',
        'visitas_total',
        'visitas_usadas',
        'fecha_inicio',
        'fecha_vencimiento',
        'anulada',
    ];

    protected $casts = [
        'precio'            => 'decimal:2',
        'visitas_total'     => 'integer',
        'visitas_usadas'    => 'integer',
        'fecha_inicio'      => 'date',
        'fecha_vencimiento' => 'date',
        'anulada'           => 'boolean',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanMembresia::class, 'plan_membresia_id');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /** Servicios que se pagaron con esta membresía (cada uno es una visita). */
    public function usos(): HasMany
    {
        return $this->hasMany(VentaItem::class)->where('cubierto_membresia', true);
    }

    /**
     * Vigente = no anulada, con visitas disponibles y sin pasar la fecha de
     * vencimiento (vence al terminar ese día). Lo que pase primero la cierra.
     */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('anulada', false)
            ->whereColumn('visitas_usadas', '<', 'visitas_total')
            ->whereDate('fecha_vencimiento', '>=', now()->toDateString());
    }

    public function getVisitasRestantesAttribute(): int
    {
        return max(0, $this->visitas_total - $this->visitas_usadas);
    }

    public function getEstadoAttribute(): string
    {
        return match (true) {
            $this->anulada                                         => 'anulada',
            $this->visitas_restantes <= 0                          => 'agotada',
            $this->fecha_vencimiento->lt(now()->startOfDay())      => 'vencida',
            default                                                => 'activa',
        };
    }

    public function getVigenteAttribute(): bool
    {
        return $this->estado === 'activa';
    }

    /**
     * Vende una membresía: crea la venta (ingreso del día, sin comisión)
     * y la membresía del cliente, todo en una sola transacción.
     */
    public static function vender(Cliente $cliente, PlanMembresia $plan, array $pago, int $barberiaId): self
    {
        return DB::transaction(function () use ($cliente, $plan, $pago, $barberiaId) {
            $inicio = now()->startOfDay();

            $membresia = self::create([
                'barberia_id'       => $barberiaId,
                'cliente_id'        => $cliente->id,
                'plan_membresia_id' => $plan->id,
                'nombre_plan'       => $plan->nombre,
                'precio'            => $plan->precio,
                'visitas_total'     => $plan->visitas,
                'visitas_usadas'    => 0,
                'fecha_inicio'      => $inicio->toDateString(),
                // Un plan de 30 días que empieza hoy cubre hoy y los 29 siguientes
                'fecha_vencimiento' => $inicio->copy()->addDays(max(1, $plan->duracion_dias) - 1)->toDateString(),
            ]);

            $venta = Venta::registrar($pago, [[
                'servicio_id'     => null,
                'inventario_id'   => null,
                'membresia_id'    => $membresia->id,
                'es_producto'     => false,
                'es_membresia'    => true,
                'nombre_servicio' => "Membresía {$plan->nombre}",
                'precio'          => (float) $plan->precio,
                'cantidad'        => 1,
                'subtotal'        => (float) $plan->precio,
            ]], null, $barberiaId);

            $membresia->update(['venta_id' => $venta->id]);

            return $membresia;
        });
    }
}
