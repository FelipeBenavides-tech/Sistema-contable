<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barberia extends Model
{
    protected $fillable = [
        'nombre',
        'propietario',
        'telefono',
        'direccion',
        'activo',
        'fecha_vencimiento',
        'plan',
    ];

    protected $casts = [
        'activo'            => 'boolean',
        'fecha_vencimiento' => 'date',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function barberos(): HasMany
    {
        return $this->hasMany(Barbero::class);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function inventario(): HasMany
    {
        return $this->hasMany(Inventario::class);
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    // Días restantes del plan
    public function getDiasRestantesAttribute(): int
    {
        if (!$this->fecha_vencimiento) return 0;
        return max(0, now()->diffInDays($this->fecha_vencimiento, false));
    }

    // ¿Está vencida la suscripción?
    public function getVencidaAttribute(): bool
    {
        if (!$this->fecha_vencimiento) return false;
        return now()->isAfter($this->fecha_vencimiento);
    }
}
