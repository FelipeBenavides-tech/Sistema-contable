<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PagoCartera extends Model
{
    protected $table = 'pagos_cartera';

    protected $fillable = [
        'barberia_id',
        'cliente_id',
        'valor',
        'metodo_pago',
        'monto_efectivo',
        'monto_nequi',
        'fecha',
        'nota',
        'user_id',
    ];

    protected $casts = [
        'valor'          => 'decimal:2',
        'monto_efectivo' => 'decimal:2',
        'monto_nequi'    => 'decimal:2',
        'fecha'          => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra un pago o abono del cliente a lo que debe. Se aplica a las
     * ventas fiadas desde la más antigua.
     *
     * @throws ValidationException si el valor no es válido o supera la deuda
     */
    public static function registrar(Cliente $cliente, float $valor, string $metodoPago, ?string $nota = null, float $efectivo = 0, float $nequi = 0): self
    {
        return DB::transaction(function () use ($cliente, $valor, $metodoPago, $nota, $efectivo, $nequi) {
            $cliente = Cliente::lockForUpdate()->findOrFail($cliente->id);
            $deuda = $cliente->deuda;

            if ($valor <= 0) {
                throw ValidationException::withMessages(['valor' => 'Escribe el valor que pagó el cliente.']);
            }

            if ($valor - $deuda > 0.009) {
                throw ValidationException::withMessages([
                    'valor' => 'El cliente debe $' . number_format($deuda, 0, ',', '.') . '; el pago no puede ser mayor.',
                ]);
            }

            if ($metodoPago === 'combinado' && abs(($efectivo + $nequi) - $valor) > 1) {
                throw ValidationException::withMessages(['valor' => 'En pago combinado, efectivo + Nequi debe ser igual al valor.']);
            }

            [$efectivo, $nequi] = match ($metodoPago) {
                'efectivo'  => [$valor, 0],
                'combinado' => [$efectivo, $nequi],
                default     => [0, $valor],
            };

            $restante = $valor;
            $ventas = $cliente->ventas()->activas()
                ->where('saldo_pendiente', '>', 0)
                ->orderBy('fecha')->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($ventas as $venta) {
                if ($restante <= 0) break;
                $abono = min($restante, (float) $venta->saldo_pendiente);
                $venta->update(['saldo_pendiente' => round((float) $venta->saldo_pendiente - $abono, 2)]);
                $restante -= $abono;
            }

            $pago = self::create([
                'barberia_id'    => $cliente->barberia_id,
                'cliente_id'     => $cliente->id,
                'valor'          => $valor,
                'metodo_pago'    => $metodoPago,
                'monto_efectivo' => $efectivo,
                'monto_nequi'    => $nequi,
                'fecha'          => now()->toDateString(),
                'nota'           => $nota,
                'user_id'        => auth()->id(),
            ]);

            $cliente->actualizarBloqueoFiado();

            return $pago;
        });
    }
}
