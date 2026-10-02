<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanMembresia extends Model
{
    protected $table = 'planes_membresia';

    protected $fillable = [
        'barberia_id',
        'nombre',
        'precio',
        'visitas',
        'duracion_dias',
        'activo',
    ];

    protected $casts = [
        'precio'        => 'decimal:2',
        'visitas'       => 'integer',
        'duracion_dias' => 'integer',
        'activo'        => 'boolean',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function membresias(): HasMany
    {
        return $this->hasMany(Membresia::class);
    }
}
