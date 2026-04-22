<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaVenta extends Model
{
    protected $table = 'auditoria_ventas';

    protected $fillable = [
        'venta_id',
        'barberia_id',
        'user_id',
        'accion',
        'motivo',
        'total_antes',
        'total_despues',
        'barbero_antes',
        'metodo_pago_antes',
        'metodo_pago_despues',
    ];

    protected $casts = [
        'total_antes'   => 'decimal:2',
        'total_despues' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }
}
