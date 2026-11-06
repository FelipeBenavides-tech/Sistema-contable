<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Un intento de pago del anticipo de una cita en Wompi.
 */
class PagoEnLinea extends Model
{
    protected $table = 'pagos_en_linea';

    protected $fillable = [
        'barberia_id',
        'cita_id',
        'referencia',
        'monto_centavos',
        'estado',
        'wompi_id',
        'metodo',
        'respuesta',
    ];

    protected $casts = [
        'monto_centavos' => 'integer',
        'respuesta'      => 'array',
    ];

    public const FINALES = ['APPROVED', 'DECLINED', 'VOIDED', 'ERROR'];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    /**
     * Aplica el estado de una transacción de Wompi (del aviso o de la consulta
     * al volver del checkout). Ignora transacciones que no cuadran con el pago.
     */
    public function aplicarTransaccion(array $transaccion): void
    {
        $estado = (string) ($transaccion['status'] ?? '');

        if (($transaccion['reference'] ?? null) !== $this->referencia
            || (int) ($transaccion['amount_in_cents'] ?? -1) !== $this->monto_centavos
            || ($transaccion['currency'] ?? 'COP') !== 'COP') {
            Log::warning('Wompi: transacción que no cuadra con el pago', ['referencia' => $this->referencia, 'transaccion' => $transaccion]);
            return;
        }

        $aplicar = DB::transaction(function () use ($transaccion, $estado) {
            $pago = self::lockForUpdate()->findOrFail($this->id);
            if (in_array($pago->estado, self::FINALES, true) || !in_array($estado, self::FINALES, true)) {
                return false;
            }

            $pago->update([
                'estado'    => $estado,
                'wompi_id'  => $transaccion['id'] ?? null,
                'metodo'    => $transaccion['payment_method_type'] ?? null,
                'respuesta' => $transaccion,
            ]);

            return true;
        });

        $this->refresh();
        if (!$aplicar || !$this->cita) {
            return;
        }

        if ($estado === 'APPROVED') {
            $this->cita->confirmarPago($this->monto_centavos / 100);
        } else {
            $this->cita->liberar();
        }
    }
}
