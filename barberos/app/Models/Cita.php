<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Cita extends Model
{
    protected $fillable = [
        'barberia_id',
        'cliente_id',
        'barbero_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
        'origen',
        'total',
        'anticipo',
        'saldo_favor_usado',
        'anticipo_estado',
        'expira_en',
        'token',
        'notas',
        'venta_id',
        'creada_por',
    ];

    protected $casts = [
        'fecha'             => 'date',
        'total'             => 'decimal:2',
        'anticipo'          => 'decimal:2',
        'saldo_favor_usado' => 'decimal:2',
        'expira_en'         => 'datetime',
    ];

    public const ESTADOS = [
        'pendiente_pago' => ['Pago pendiente', 'badge-amber'],
        'confirmada' => ['Confirmada', 'badge-blue'],
        'atendida'   => ['Atendida', 'badge-green'],
        'cancelada'  => ['Cancelada', 'badge-gray'],
        'no_asistio' => ['No asistió', 'badge-red'],
    ];

    // Estados que ocupan la hora del barbero (más las reservas que se están pagando)
    public const OCUPAN = ['confirmada', 'atendida'];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function barbero(): BelongsTo
    {
        return $this->belongsTo(Barbero::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(CitaServicio::class);
    }

    public function scopeOcupan(Builder $query): Builder
    {
        return $query->where(fn($q) => $q
            ->whereIn('estado', self::OCUPAN)
            ->orWhere(fn($q) => $q->where('estado', 'pendiente_pago')->where('expira_en', '>', now())));
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoEnLinea::class);
    }

    // Fecha y hora en que empieza la cita
    public function getInicioAttribute(): Carbon
    {
        return Carbon::parse($this->fecha->toDateString() . ' ' . $this->hora_inicio);
    }

    // Anticipo ya pagado que se descuenta al cobrar en la caja
    public function getAnticipoPagadoAttribute(): float
    {
        return $this->anticipo_estado === 'pagado' ? (float) $this->anticipo : 0;
    }

    // ¿El cliente todavía puede cancelar sin perder el anticipo?
    public function getCancelaATiempoAttribute(): bool
    {
        $horas = (int) ($this->barberia?->horas_cancelacion ?? 4);

        return now()->diffInMinutes($this->inicio, false) >= $horas * 60;
    }

    // ── Cambios de estado con anticipo ───────────────────────────────────────

    /** Pago aprobado: la cita queda confirmada (o, si ya expiró y la hora se ocupó, el dinero queda como saldo a favor). */
    public function confirmarPago(float $pagado): void
    {
        DB::transaction(function () use ($pagado) {
            $cita = self::lockForUpdate()->findOrFail($this->id);

            if (in_array($cita->anticipo_estado, ['pagado', 'aplicado', 'retenido', 'saldo_favor'], true)) {
                return;
            }

            if ($cita->estado === 'pendiente_pago' || $cita->estado === 'cancelada') {
                $duracion = self::minutos($cita->hora_fin) - self::minutos($cita->hora_inicio);
                $libre = $cita->estado === 'pendiente_pago'
                    || in_array($cita->hora_inicio, self::horasDisponibles($cita->barbero, $cita->fecha->toDateString(), $duracion, $cita->id), true);

                if ($libre) {
                    $cita->update(['estado' => 'confirmada', 'anticipo_estado' => 'pagado', 'expira_en' => null]);
                    return;
                }

                // Pagó tarde y la hora ya la tomó otra persona: no pierde la plata
                Cliente::whereKey($cita->cliente_id)->increment('saldo_favor', $pagado + (float) $cita->saldo_favor_usado);
                $cita->update(['anticipo_estado' => 'saldo_favor', 'expira_en' => null]);
            }
        });
        $this->refresh();
    }

    /** Pago rechazado o reserva vencida sin pagar: se libera la hora y se devuelve el saldo a favor usado. */
    public function liberar(): void
    {
        DB::transaction(function () {
            $cita = self::lockForUpdate()->findOrFail($this->id);
            if ($cita->estado !== 'pendiente_pago') {
                return;
            }

            if ((float) $cita->saldo_favor_usado > 0) {
                Cliente::whereKey($cita->cliente_id)->increment('saldo_favor', (float) $cita->saldo_favor_usado);
            }

            $cita->update(['estado' => 'cancelada', 'anticipo_estado' => null, 'anticipo' => 0, 'saldo_favor_usado' => 0, 'expira_en' => null]);
        });
        $this->refresh();
    }

    /**
     * Cancela la cita. Si tenía anticipo pagado:
     *  - la cancela la barbería, o el cliente con tiempo → queda como saldo a favor del cliente
     *  - el cliente cancela tarde → la barbería se queda con el anticipo
     */
    public function cancelar(bool $porCliente = false): string
    {
        if ($this->estado === 'pendiente_pago') {
            $this->liberar();
            return 'liberada';
        }

        return DB::transaction(function () use ($porCliente) {
            $cita = self::lockForUpdate()->findOrFail($this->id);
            if ($cita->estado !== 'confirmada') {
                return 'sin_cambio';
            }

            $resultado = 'cancelada';
            if ($cita->anticipo_estado === 'pagado') {
                if (!$porCliente || $cita->cancela_a_tiempo) {
                    Cliente::whereKey($cita->cliente_id)->increment('saldo_favor', (float) $cita->anticipo);
                    $cita->anticipo_estado = 'saldo_favor';
                    $resultado = 'saldo_favor';
                } else {
                    $cita->retenerAnticipo();
                    $resultado = 'retenido';
                }
            }

            $cita->estado = 'cancelada';
            $cita->save();
            $this->refresh();

            return $resultado;
        });
    }

    /** El cliente no llegó: la barbería se queda con el anticipo. */
    public function marcarNoAsistio(): void
    {
        DB::transaction(function () {
            $cita = self::lockForUpdate()->findOrFail($this->id);
            if ($cita->estado !== 'confirmada') {
                return;
            }

            if ($cita->anticipo_estado === 'pagado') {
                $cita->retenerAnticipo();
            }

            $cita->estado = 'no_asistio';
            $cita->save();
        });
        $this->refresh();
    }

    /**
     * Registra el anticipo retenido como un ingreso de la barbería (sin
     * comisión para el barbero), para que cuadre con lo que llegó por Wompi.
     */
    private function retenerAnticipo(): void
    {
        $valor = (float) $this->anticipo;
        $this->anticipo_estado = 'retenido';

        if ($valor <= 0) {
            return;
        }

        $venta = Venta::registrar(
            ['metodo_pago' => 'anticipo', 'cliente_id' => $this->cliente_id, 'monto_prepagado' => $valor],
            [[
                'servicio_id'     => null,
                'inventario_id'   => null,
                'es_producto'     => true,
                'nombre_servicio' => 'Anticipo retenido · cita del ' . $this->fecha->format('d/m') . ' ' . $this->hora_inicio,
                'precio'          => $valor,
                'cantidad'        => 1,
                'subtotal'        => $valor,
            ]],
            null,
            $this->barberia_id
        );

        $this->venta_id = $venta->id;
    }

    /** Reservas en línea que no se pagaron a tiempo: libera sus horas. */
    public static function liberarVencidas(): int
    {
        $vencidas = self::where('estado', 'pendiente_pago')->where('expira_en', '<=', now())->get();
        $vencidas->each->liberar();

        return $vencidas->count();
    }

    public function getPendienteAttribute(): bool
    {
        return $this->estado === 'confirmada';
    }

    public static function minutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', $hora));
        return $h * 60 + $m;
    }

    public static function hora(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }

    /**
     * Horas en las que el barbero puede empezar un servicio de esta duración ese día,
     * dentro de su horario y sin chocar con otras citas.
     */
    public static function horasDisponibles(Barbero $barbero, string $fecha, int $duracion, ?int $ignorarCitaId = null): array
    {
        $dia = Carbon::parse($fecha);
        $horario = $barbero->horarioDelDia($dia->dayOfWeek);
        if (!$horario || $duracion <= 0) {
            return [];
        }

        $ocupadas = self::where('barbero_id', $barbero->id)
            ->whereDate('fecha', $dia->toDateString())
            ->ocupan()
            ->when($ignorarCitaId, fn($q) => $q->where('id', '!=', $ignorarCitaId))
            ->get(['hora_inicio', 'hora_fin'])
            ->map(fn($c) => [self::minutos($c->hora_inicio), self::minutos($c->hora_fin)]);

        $paso   = max(5, (int) config('agenda.intervalo_minutos', 15));
        $inicio = self::minutos($horario['inicio']);
        $fin    = self::minutos($horario['fin']);

        // Hoy no se ofrecen horas que ya pasaron
        if ($dia->isToday()) {
            $ahora  = now()->hour * 60 + now()->minute;
            $inicio = max($inicio, (int) (ceil($ahora / $paso) * $paso));
        } elseif ($dia->isPast()) {
            return [];
        }

        $horas = [];
        for ($t = $inicio; $t + $duracion <= $fin; $t += $paso) {
            $choca = $ocupadas->contains(fn($o) => $t < $o[1] && $t + $duracion > $o[0]);
            if (!$choca) {
                $horas[] = self::hora($t);
            }
        }

        return $horas;
    }

    /**
     * Agenda una cita. Revisa el horario del barbero y que no se cruce con otra
     * dentro de la misma transacción, para que dos personas no tomen la misma hora.
     *
     * @param array $datos fecha, hora_inicio, notas
     * @param int[] $servicioIds
     * @throws ValidationException
     */
    public static function agendar(int $barberiaId, Cliente $cliente, Barbero $barbero, array $servicioIds, array $datos, ?int $userId = null): self
    {
        return DB::transaction(function () use ($barberiaId, $cliente, $barbero, $servicioIds, $datos, $userId) {
            $servicios = Servicio::where('barberia_id', $barberiaId)
                ->where('activo', true)
                ->whereIn('id', $servicioIds)
                ->get();

            if ($servicios->isEmpty()) {
                throw ValidationException::withMessages(['servicios' => 'Escoge al menos un servicio.']);
            }

            $porDefecto = (int) config('agenda.duracion_por_defecto', 30);
            $duracion = $servicios->sum(fn($s) => $s->duracion_minutos ?: $porDefecto);
            $fecha = Carbon::parse($datos['fecha'])->toDateString();
            $hora  = $datos['hora_inicio'];

            // Bloquea las citas del barbero ese día mientras se revisa el cruce
            Barbero::whereKey($barbero->id)->lockForUpdate()->first();

            if (!in_array($hora, self::horasDisponibles($barbero, $fecha, $duracion), true)) {
                throw ValidationException::withMessages([
                    'hora' => "{$barbero->nombre} no tiene libre esa hora para un servicio de {$duracion} minutos. Escoge otra.",
                ]);
            }

            $total = (float) $servicios->sum('precio');
            $extra = is_callable($datos['antes_de_crear'] ?? null) ? ($datos['antes_de_crear'])($total) : [];

            $cita = self::create(array_merge([
                'barberia_id' => $barberiaId,
                'cliente_id'  => $cliente->id,
                'barbero_id'  => $barbero->id,
                'fecha'       => $fecha,
                'hora_inicio' => $hora,
                'hora_fin'    => self::hora(self::minutos($hora) + $duracion),
                'estado'      => 'confirmada',
                'total'       => $total,
                'notas'       => $datos['notas'] ?? null,
                'creada_por'  => $userId,
            ], $extra));

            foreach ($servicios as $servicio) {
                $cita->servicios()->create([
                    'servicio_id'      => $servicio->id,
                    'nombre'           => $servicio->nombre,
                    'precio'           => $servicio->precio,
                    'duracion_minutos' => $servicio->duracion_minutos ?: $porDefecto,
                ]);
            }

            return $cita;
        });
    }
}
