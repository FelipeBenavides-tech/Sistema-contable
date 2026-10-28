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
        'total',
        'notas',
        'venta_id',
        'creada_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'decimal:2',
    ];

    public const ESTADOS = [
        'confirmada' => ['Confirmada', 'badge-blue'],
        'atendida'   => ['Atendida', 'badge-green'],
        'cancelada'  => ['Cancelada', 'badge-gray'],
        'no_asistio' => ['No asistió', 'badge-red'],
    ];

    // Estados que ocupan la hora del barbero
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
        return $query->whereIn('estado', self::OCUPAN);
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

            $cita = self::create([
                'barberia_id' => $barberiaId,
                'cliente_id'  => $cliente->id,
                'barbero_id'  => $barbero->id,
                'fecha'       => $fecha,
                'hora_inicio' => $hora,
                'hora_fin'    => self::hora(self::minutos($hora) + $duracion),
                'estado'      => 'confirmada',
                'total'       => $servicios->sum('precio'),
                'notas'       => $datos['notas'] ?? null,
                'creada_por'  => $userId,
            ]);

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
