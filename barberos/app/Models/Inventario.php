<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventario extends Model
{
    protected $table = 'inventario';

    protected $fillable = [
        'barberia_id',
        'nombre',
        'categoria',
        'stock_actual',
        'stock_minimo',
        'precio_costo',
        'precio_venta',
        'unidad',
    ];

    protected $casts = [
        'precio_costo' => 'decimal:2',
        'precio_venta' => 'decimal:2',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function getStockBajoAttribute(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    public function entrada(int $cantidad, float $costoUnitario, string $motivo = 'compra'): void
    {
        $this->increment('stock_actual', $cantidad);
    }

    public function salida(int $cantidad, string $motivo = 'uso'): void
    {
        $this->decrement('stock_actual', $cantidad);
    }
}
