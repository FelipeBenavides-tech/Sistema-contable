<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoSuscripcion extends Model
{
    protected $table = 'pagos_suscripcion';

    protected $fillable = [
        'barberia_id',
        'plan',
        'valor',
        'meses',
        'fecha',
        'cubre_desde',
        'cubre_hasta',
        'metodo_pago',
        'nota',
        'gasto_id',
    ];

    protected $casts = [
        'valor'       => 'decimal:2',
        'fecha'       => 'date',
        'cubre_desde' => 'date',
        'cubre_hasta' => 'date',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function gasto(): BelongsTo
    {
        return $this->belongsTo(Gasto::class);
    }
}
