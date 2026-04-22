<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servicio extends Model
{
    protected $fillable = [
        'barberia_id',
        'nombre',
        'precio',
        'categoria',
        'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function ventaItems(): HasMany
    {
        return $this->hasMany(VentaItem::class);
    }
}
