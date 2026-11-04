<?php

namespace App\Livewire;

use App\Models\Cita;
use App\Support\Wompi;
use Livewire\Component;

/**
 * Enlace privado del cliente para ver el estado de su reserva y cancelarla.
 */
class VerReserva extends Component
{
    public string $token = '';
    public string $mensaje = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $cita = $this->cita();

        // Al volver de Wompi llega ?id=<transacción>. Se consulta por si el aviso aún no llegó.
        $transaccionId = (string) request()->query('id', '');
        if ($transaccionId !== '' && $cita->estado === 'pendiente_pago') {
            $pago = $cita->pagos()->latest('id')->first();
            $transaccion = $pago ? (new Wompi($cita->barberia))->consultarTransaccion($transaccionId) : null;
            if ($pago && $transaccion) {
                $pago->aplicarTransaccion($transaccion);
            }
        }
    }

    private function cita(): Cita
    {
        return Cita::with(['barberia', 'barbero', 'servicios', 'cliente'])->where('token', $this->token)->firstOrFail();
    }

    public function pagar()
    {
        $cita = $this->cita();
        $pago = $cita->pagos()->where('estado', 'PENDING')->latest('id')->first();

        if ($cita->estado !== 'pendiente_pago' || !$pago || $cita->expira_en?->isPast()) {
            return null;
        }

        return redirect()->away((new Wompi($cita->barberia))->urlCheckout(
            $pago->referencia, $pago->monto_centavos, route('reserva.ver', $cita->token), $cita->expira_en,
            ['email' => $cita->cliente->email, 'nombre' => $cita->cliente->nombre, 'telefono' => $cita->cliente->telefono],
        ));
    }

    public function cancelar(): void
    {
        $cita = $this->cita();
        if (!in_array($cita->estado, ['confirmada', 'pendiente_pago'], true) || $cita->inicio->isPast()) {
            return;
        }

        $dinero = '$' . number_format((float) $cita->anticipo, 0, ',', '.');
        $this->mensaje = match ($cita->cancelar(porCliente: true)) {
            'saldo_favor' => "Tu cita quedó cancelada. Los {$dinero} del anticipo te quedan como saldo a favor para tu próxima reserva.",
            'retenido'    => 'Tu cita quedó cancelada. Como faltaban menos de ' . $cita->barberia->horas_cancelacion . ' horas, la barbería se queda con el anticipo.',
            default       => 'Tu cita quedó cancelada.',
        };
    }

    public function render()
    {
        $cita = $this->cita();

        return view('livewire.ver-reserva', [
            'cita'     => $cita,
            'barberia' => $cita->barberia,
            'pago'     => $cita->pagos()->latest('id')->first(),
        ])->layout('layouts.publico', ['barberia' => $cita->barberia, 'title' => 'Tu reserva']);
    }
}
