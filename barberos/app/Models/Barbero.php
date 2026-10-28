<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Barbero extends Model
{
    protected $fillable = [
        'barberia_id',
        'nombre',
        'telefono',
        'comision_porcentaje',
        'activo',
    ];

    protected $casts = [
        'comision_porcentaje' => 'float',
        'activo'              => 'boolean',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioBarbero::class);
    }

    // Usuario con el que el barbero entra a ver su agenda
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Horario del barbero para un día de la semana (0 = domingo), o null si no trabaja.
     * Si nunca se le configuró horario, usa el horario general de config/agenda.php.
     */
    public function horarioDelDia(int $diaSemana): ?array
    {
        $horarios = $this->relationLoaded('horarios') ? $this->horarios : $this->horarios()->get();

        if ($horarios->isEmpty()) {
            $general = config('agenda.horario_general.' . $diaSemana);
            return $general ? ['inicio' => $general[0], 'fin' => $general[1]] : null;
        }

        $dia = $horarios->firstWhere('dia_semana', $diaSemana);

        return $dia ? ['inicio' => $dia->hora_inicio, 'fin' => $dia->hora_fin] : null;
    }

    public function comisionesMes(int $mes, int $anio): array
    {
        $ventas = $this->ventas()
            ->activas()
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->get();

        return [
            'barbero'        => $this->nombre,
            'total_generado' => $ventas->sum('total'),
            'comision_total' => $ventas->sum('comision_barbero'),
            'porcentaje'     => $this->comision_porcentaje,
            'servicios'      => $ventas->count(),
        ];
    }
}
