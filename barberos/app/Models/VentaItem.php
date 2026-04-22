<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaItem extends Model
{
    protected $fillable = [
        'venta_id',
        'servicio_id',
        'nombre_servicio',
        'precio',
        'cantidad',
        'subtotal',
    ];

    protected $casts = [
        'precio'   => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}
