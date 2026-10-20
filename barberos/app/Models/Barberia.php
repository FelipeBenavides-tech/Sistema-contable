<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        'saldo',
        'contrato_hasta',
    ];

    protected $casts = [
        'activo'            => 'boolean',
        'fecha_vencimiento' => 'date',
        'contrato_hasta'    => 'date',
        'saldo'             => 'decimal:2',
    ];

    public const PLANES = ['mensual', 'semestral', 'anual', 'porcentaje'];

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

    public function pagosSuscripcion(): HasMany
    {
        return $this->hasMany(PagoSuscripcion::class);
    }

    // ── Plan ──────────────────────────────────────────────────────────────────

    public function getPorUsoAttribute(): bool
    {
        return $this->plan === 'porcentaje';
    }

    public function getNombrePlanAttribute(): string
    {
        return $this->por_uso
            ? config('suscripcion.porcentaje.nombre')
            : (config("suscripcion.planes.{$this->plan}.nombre") ?? ucfirst($this->plan));
    }

    // Lo que cuesta un pago del plan actual (en el plan por uso, la recarga mínima)
    public function getPrecioPlanAttribute(): float
    {
        return (float) ($this->por_uso
            ? $this->recarga_minima
            : config("suscripcion.planes.{$this->plan}.precio", 0));
    }

    // Plan por uso: la primera recarga tiene un mínimo más alto que las siguientes
    public function getRecargaMinimaAttribute(): float
    {
        $yaRecargo = $this->exists && $this->pagosSuscripcion()->where('plan', 'porcentaje')->exists();

        return (float) config($yaRecargo ? 'suscripcion.porcentaje.recarga_minima' : 'suscripcion.porcentaje.primera_recarga_minima');
    }

    public function getInstalacionPagadaAttribute(): bool
    {
        return $this->exists && $this->pagosSuscripcion()->where('plan', 'instalacion')->exists();
    }

    // Pago único por la instalación y entrega del sistema
    public function registrarInstalacion(float $valor, string $metodoPago = 'transferencia', ?string $nota = null): PagoSuscripcion
    {
        return DB::transaction(function () use ($valor, $metodoPago, $nota) {
            $gasto = Gasto::create([
                'barberia_id' => $this->id,
                'concepto'    => 'Instalación y entrega de Kaixa',
                'categoria'   => 'suscripcion',
                'valor'       => $valor,
                'metodo_pago' => in_array($metodoPago, ['efectivo', 'transferencia', 'tarjeta']) ? $metodoPago : 'transferencia',
                'fecha'       => now()->startOfDay(),
                'observacion' => $nota,
            ]);

            return $this->pagosSuscripcion()->create([
                'plan'        => 'instalacion',
                'valor'       => $valor,
                'fecha'       => now()->startOfDay(),
                'metodo_pago' => $metodoPago,
                'nota'        => $nota,
                'gasto_id'    => $gasto->id,
            ]);
        });
    }

    // Último día en que se puede usar el sistema sin pagar (vencimiento + días de gracia)
    public function getFechaLimiteAttribute(): ?Carbon
    {
        return $this->fecha_vencimiento?->copy()->addDays((int) config('suscripcion.dias_gracia'));
    }

    /**
     * Estado del pago:
     *  al_dia     → todo en orden
     *  por_vencer → vence en los próximos días (o saldo bajo en el plan por uso)
     *  en_gracia  → ya venció, pero aún está dentro de los días de gracia
     *  bloqueada  → pasó la gracia (o se acabó el saldo): debe pagar para seguir
     */
    public function getEstadoPagoAttribute(): string
    {
        if ($this->por_uso) {
            if ((float) $this->saldo <= 0) return 'bloqueada';
            return (float) $this->saldo < (float) config('suscripcion.porcentaje.aviso_saldo_bajo') ? 'por_vencer' : 'al_dia';
        }

        if (!$this->fecha_vencimiento) return 'al_dia';

        $hoy = now()->startOfDay();
        if ($hoy->lte($this->fecha_vencimiento)) {
            return $this->dias_restantes <= (int) config('suscripcion.dias_gracia') ? 'por_vencer' : 'al_dia';
        }

        return $hoy->lte($this->fecha_limite) ? 'en_gracia' : 'bloqueada';
    }

    // Días que le quedan de gracia antes de que se bloquee
    public function getDiasGraciaRestantesAttribute(): int
    {
        if (!$this->fecha_limite) return 0;
        return max(0, (int) now()->startOfDay()->diffInDays($this->fecha_limite->copy()->startOfDay(), false));
    }

    // Planes que exigen contrato mínimo
    public const PLANES_CON_CONTRATO = ['mensual', 'porcentaje'];

    // ¿Sigue dentro del contrato mínimo (plan mensual o por uso)?
    public function getEnContratoMinimoAttribute(): bool
    {
        return in_array($this->plan, self::PLANES_CON_CONTRATO) && $this->contrato_hasta && now()->startOfDay()->lte($this->contrato_hasta);
    }

    // Arranca el contrato mínimo cuando la barbería empieza en el plan mensual o por uso
    public function iniciarContrato(?Carbon $desde = null): void
    {
        $this->contrato_hasta = ($desde ?? now())->copy()->addMonths((int) config('suscripcion.contrato_minimo_meses'));
    }

    /**
     * Registra un pago (planes por tiempo) o una recarga (plan por uso).
     * Extiende el vencimiento o suma el saldo, y lo anota como gasto en la
     * contabilidad de la barbería.
     */
    public function registrarPago(float $valor, int $cantidad = 1, string $metodoPago = 'transferencia', ?string $nota = null): PagoSuscripcion
    {
        return DB::transaction(function () use ($valor, $cantidad, $metodoPago, $nota) {
            $barberia = self::lockForUpdate()->findOrFail($this->id);
            $hoy = now()->startOfDay();
            $datos = [
                'plan'        => $barberia->plan,
                'valor'       => $valor,
                'fecha'       => $hoy,
                'metodo_pago' => $metodoPago,
                'nota'        => $nota,
            ];

            if ($barberia->por_uso) {
                $barberia->saldo = (float) $barberia->saldo + $valor;
                $concepto = 'Recarga Kaixa (plan por uso)';
            } else {
                $meses = (int) config("suscripcion.planes.{$barberia->plan}.meses", 1) * max(1, $cantidad);

                // Si paga antes de que se bloquee, el periodo sigue desde su vencimiento
                // (los días de gracia no se regalan); si ya estaba bloqueada, desde hoy
                $base = $barberia->fecha_vencimiento && $hoy->lte($barberia->fecha_limite)
                    ? $barberia->fecha_vencimiento->copy()
                    : $hoy->copy();

                $datos['meses']       = $meses;
                $datos['cubre_desde'] = $base->copy()->addDay();
                $datos['cubre_hasta'] = $base->copy()->addMonths($meses);
                $barberia->fecha_vencimiento = $datos['cubre_hasta'];


                $concepto = "Suscripción Kaixa plan {$barberia->nombre_plan} ({$meses} " . ($meses === 1 ? 'mes' : 'meses') . ')';
            }

            $barberia->save();

            $gasto = Gasto::create([
                'barberia_id' => $barberia->id,
                'concepto'    => $concepto,
                'categoria'   => 'suscripcion',
                'valor'       => $valor,
                'metodo_pago' => in_array($metodoPago, ['efectivo', 'transferencia', 'tarjeta']) ? $metodoPago : 'transferencia',
                'fecha'       => $hoy,
                'observacion' => $nota,
            ]);

            $this->refresh();

            return $barberia->pagosSuscripcion()->create($datos + ['gasto_id' => $gasto->id]);
        });
    }

    /**
     * Devuelve al cliente el saldo a favor del plan por uso (por ejemplo, al
     * terminar el contrato o cambiar de plan). Queda como pago negativo y
     * resta el gasto en la contabilidad de la barbería.
     */
    public function devolverSaldo(string $metodoPago = 'transferencia', ?string $nota = null): ?PagoSuscripcion
    {
        return DB::transaction(function () use ($metodoPago, $nota) {
            $barberia = self::lockForUpdate()->findOrFail($this->id);
            $valor = (float) $barberia->saldo;
            if ($valor <= 0) {
                return null;
            }

            $barberia->update(['saldo' => 0]);
            $hoy = now()->startOfDay();

            $gasto = Gasto::create([
                'barberia_id' => $barberia->id,
                'concepto'    => 'Devolución de saldo Kaixa',
                'categoria'   => 'suscripcion',
                'valor'       => -$valor,
                'metodo_pago' => in_array($metodoPago, ['efectivo', 'transferencia', 'tarjeta']) ? $metodoPago : 'transferencia',
                'fecha'       => $hoy,
                'observacion' => $nota,
            ]);

            $this->refresh();

            return $barberia->pagosSuscripcion()->create([
                'plan'        => 'devolucion',
                'valor'       => -$valor,
                'fecha'       => $hoy,
                'metodo_pago' => $metodoPago,
                'nota'        => $nota,
                'gasto_id'    => $gasto->id,
            ]);
        });
    }

    /**
     * Plan por uso: descuenta del saldo el porcentaje de una venta.
     * Debe llamarse dentro de la transacción de la venta.
     */
    public static function cobrarUso(int $barberiaId, float $totalVenta): float
    {
        $barberia = self::lockForUpdate()->find($barberiaId);
        if (!$barberia || !$barberia->por_uso || $totalVenta <= 0) {
            return 0;
        }

        $cargo = round($totalVenta * (float) config('suscripcion.porcentaje.valor') / 100, 2);

        if ($cargo > (float) $barberia->saldo) {
            $saldo = '$' . number_format((float) $barberia->saldo, 0, ',', '.');
            throw ValidationException::withMessages([
                'carrito' => "Saldo de Kaixa insuficiente ({$saldo}). Recarga para seguir vendiendo.",
            ]);
        }

        $barberia->decrement('saldo', $cargo);

        return $cargo;
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
