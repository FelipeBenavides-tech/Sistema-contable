<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioBarbero extends Model
{
    protected $table = 'horarios_barbero';

    protected $fillable = [
        'barbero_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
    ];

    // 0 = domingo, igual que Carbon::dayOfWeek
    public const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 0 => 'Domingo'];

    public function barbero(): BelongsTo
    {
        return $this->belongsTo(Barbero::class);
    }
}
