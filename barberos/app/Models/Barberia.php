<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Barberia extends Model
{
    protected $fillable = [
        'nombre',
        'nit',
        'propietario',
        'telefono',
        'direccion',
        'logo',
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
        return max(0, (int) ceil(now()->startOfDay()->diffInDays($this->fecha_vencimiento->copy()->startOfDay(), false)));
    }

    // ¿Está vencida la suscripción?
    public function getVencidaAttribute(): bool
    {
        if (!$this->fecha_vencimiento) return false;
        // Vence al terminar el día indicado
        return now()->isAfter($this->fecha_vencimiento->copy()->endOfDay());
    }

    // Logo listo para incrustar en la pantalla o en el PDF (sin depender de storage:link)
    public function logoDataUri(): ?string
    {
        if (!$this->logo || !Storage::disk('local')->exists($this->logo)) return null;

        $mime = Storage::disk('local')->mimeType($this->logo) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('local')->get($this->logo));
    }

    // Iniciales para cuando no hay logo (ej. "Barbería El Estilo" -> "EE")
    public function getInicialesAttribute(): string
    {
        $palabras = collect(preg_split('/\s+/', trim($this->nombre ?? '')))
            ->reject(fn ($p) => in_array(mb_strtolower($p), ['barbería', 'barberia', 'barber', 'barbershop', 'shop', 'de', 'y', '&']))
            ->values();

        if ($palabras->isEmpty()) $palabras = collect([$this->nombre ?: 'K']);

        return mb_strtoupper($palabras->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    }
}
