<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $fillable = [
        'barberia_id',
        'nombre',
        'telefono',
        'email',
        'fecha_nacimiento',
        'notas',
        'permite_fiado',
        'fiado_bloqueado',
        'acepta_mensajes',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'permite_fiado'    => 'boolean',
        'fiado_bloqueado'  => 'boolean',
        'acepta_mensajes'  => 'boolean',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function membresias(): HasMany
    {
        return $this->hasMany(Membresia::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function pagosCartera(): HasMany
    {
        return $this->hasMany(PagoCartera::class);
    }

    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        return $query->where(fn($q) => $q
            ->where('nombre', 'like', "%{$termino}%")
            ->orWhere('telefono', 'like', "%{$termino}%"));
    }

    // Clientes que deben algo
    public function scopeEnCartera(Builder $query): Builder
    {
        return $query->whereHas('ventas', fn($q) => $q->activas()->where('saldo_pendiente', '>', 0));
    }

    // Solo dígitos, para comparar teléfonos escritos de formas distintas
    public static function normalizarTelefono(?string $telefono): string
    {
        return preg_replace('/\D/', '', (string) $telefono);
    }

    /**
     * Busca el cliente por teléfono dentro de la barbería, o lo crea.
     * Así una misma persona no queda repetida al agendarla varias veces.
     */
    public static function buscarOCrear(int $barberiaId, string $nombre, ?string $telefono): self
    {
        $telefono = self::normalizarTelefono($telefono);

        if ($telefono !== '') {
            $existente = self::where('barberia_id', $barberiaId)->where('telefono', $telefono)->first();
            if ($existente) {
                return $existente;
            }
        }

        return self::create([
            'barberia_id' => $barberiaId,
            'nombre'      => trim($nombre),
            'telefono'    => $telefono ?: null,
        ]);
    }

    // ── Cartera (servicios fiados) ────────────────────────────────────────────

    private function ventasPendientes()
    {
        return $this->ventas()->activas()->where('saldo_pendiente', '>', 0);
    }

    // Lo que el cliente debe en total
    public function getDeudaAttribute(): float
    {
        return (float) $this->ventasPendientes()->sum('saldo_pendiente');
    }

    // Servicios fiados que aún no ha pagado (no cuenta productos)
    public function getServiciosFiadosAttribute(): int
    {
        return (int) VentaItem::whereIn('venta_id', $this->ventasPendientes()->select('id'))
            ->where('es_producto', false)
            ->where('es_membresia', false)
            ->where('cubierto_membresia', false)
            ->sum('cantidad');
    }

    public function getLimiteFiadoAttribute(): int
    {
        return (int) ($this->barberia?->limite_fiado ?? 4);
    }

    /**
     * Motivo por el que no se le puede fiar esta cantidad de servicios,
     * o null si sí se puede.
     */
    public function motivoSinFiado(int $serviciosNuevos): ?string
    {
        if (!$this->permite_fiado) {
            return "{$this->nombre} no tiene cartera habilitada. Actívala en Clientes para poder fiarle.";
        }

        $deuda = '$' . number_format($this->deuda, 0, ',', '.');

        if ($this->fiado_bloqueado) {
            return "{$this->nombre} ya completó {$this->limite_fiado} servicios fiados. Debe pagar la totalidad ({$deuda}) antes de fiarle otra vez.";
        }

        if ($this->servicios_fiados + $serviciosNuevos > $this->limite_fiado) {
            $quedan = max(0, $this->limite_fiado - $this->servicios_fiados);
            return "A {$this->nombre} solo se le pueden fiar {$quedan} servicios más (lleva {$this->servicios_fiados} de {$this->limite_fiado}).";
        }

        return null;
    }

    /**
     * Al llegar al límite de servicios fiados queda bloqueado, y solo se
     * desbloquea cuando paga todo lo que debe.
     */
    public function actualizarBloqueoFiado(): void
    {
        $this->refresh();

        if ($this->deuda <= 0) {
            $bloqueado = false;
        } elseif ($this->servicios_fiados >= $this->limite_fiado) {
            $bloqueado = true;
        } else {
            $bloqueado = $this->fiado_bloqueado;
        }

        if ($bloqueado !== $this->fiado_bloqueado) {
            $this->update(['fiado_bloqueado' => $bloqueado]);
        }
    }

    public function getEstadoCarteraAttribute(): string
    {
        if ($this->deuda <= 0) return 'al_dia';
        return $this->fiado_bloqueado ? 'debe_pagar' : 'con_deuda';
    }
}
