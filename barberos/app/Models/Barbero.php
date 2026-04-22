<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Barbero extends Model
{
    protected $fillable = [
        'barberia_id',
        'nombre',
        'telefono',
        'comision_porcentaje',
        'activo',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function comisionesMes(int $mes, int $anio): array
    {
        $ventas = $this->ventas()
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->get();

        return [
            'barbero'        => $this->nombre,
            'total_generado' => $ventas->sum('total'),
            'comision_total' => $ventas->sum('comision_barbero'),
            'porcentaje'     => $this->comision_porcentaje,
            'servicios'      => $ventas->count(),
        ];
    }
}
