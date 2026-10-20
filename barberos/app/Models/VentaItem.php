<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaItem extends Model
{
    protected $fillable = [
        'venta_id',
        'servicio_id',
        'inventario_id',
        'membresia_id',
        'es_producto',
        'es_membresia',
        'cubierto_membresia',
        'nombre_servicio',
        'precio',
        'cantidad',
        'subtotal',
    ];

    protected $casts = [
        'precio'   => 'decimal:2',
        'subtotal'    => 'decimal:2',
        'es_producto' => 'boolean',
        'es_membresia'       => 'boolean',
        'cubierto_membresia' => 'boolean',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class);
    }

    public function membresia(): BelongsTo
    {
        return $this->belongsTo(Membresia::class);
    }
}
