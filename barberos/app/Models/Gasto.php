<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    protected $fillable = [
        'barberia_id',
        'concepto',
        'categoria',
        'valor',
        'metodo_pago',
        'fecha',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'valor' => 'decimal:2',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public static function resumenMes(int $mes, int $anio, int $barberiaId): array
    {
        return self::where('barberia_id', $barberiaId)
            ->selectRaw('categoria, SUM(valor) as total')
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    public static function totalMes(int $mes, int $anio, int $barberiaId): float
    {
        return (float) self::where('barberia_id', $barberiaId)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->sum('valor');
    }
}
