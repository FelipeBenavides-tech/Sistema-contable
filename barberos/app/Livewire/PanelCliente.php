<?php

namespace App\Livewire;

use App\Livewire\Concerns\ReagendarCita;
use App\Models\Cita;
use App\Models\Cliente;
use Livewire\Component;

/**
 * Panel del cliente ("Mis citas"): sus próximas citas, pagar el anticipo
 * pendiente, responder cuando la barbería le mueve una cita, escoger otra
 * hora, cancelar y reservar de nuevo. Se entra con un enlace privado.
 */
class PanelCliente extends Component
{
    use ReagendarCita;

    public string $token = '';
    public string $exito = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->cliente();
    }

    private function cliente(): Cliente
    {
        $cliente = Cliente::with('barberia')->where('token', $this->token)->first();
        abort_unless($cliente && $cliente->barberia?->recibe_reservas, 404);

        return $cliente;
    }

    private function cita(int $id): Cita
    {
        return Cita::with(['barberia', 'barbero', 'servicios', 'cliente'])
            ->where('cliente_id', $this->cliente()->id)
            ->findOrFail($id);
    }

    // ── Reagendar (el cliente escoge otra hora) ─────────────────────────────

    protected function citaParaReagendar(int $id): Cita
    {
        $cita = $this->cita($id);
        abort_unless($cita->cliente_puede_reagendar, 403);

        return $cita;
    }

    protected function reagendaQuien(): string
    {
        return 'cliente';
    }

    // ── Acciones ────────────────────────────────────────────────────────────

    public function pagar(int $id)
    {
        $url = $this->cita($id)->urlPagoPendiente();

        return $url ? redirect()->away($url) : null;
    }

    public function aceptar(int $id): void
    {
        $cita = $this->cita($id);
        if (!$cita->espera_respuesta) {
            return;
        }

        $cita->aceptarReagendo();
        $this->exito = 'Gracias. Te esperamos el ' . $cita->fecha->translatedFormat('l j \d\e F') . ' a las ' . $cita->inicio->format('g:i a') . '.';
    }

    public function cancelar(int $id): void
    {
        $cita = $this->cita($id);
        if (!in_array($cita->estado, ['confirmada', 'pendiente_pago'], true) || $cita->inicio->isPast()) {
            return;
        }

        $saldoAntes = (float) $cita->cliente->saldo_favor;
        $resultado = $cita->cancelarPorCliente();
        $abono = (float) $cita->cliente->fresh()->saldo_favor - $saldoAntes;
        $dinero = fn($v) => '$' . number_format((float) $v, 0, ',', '.');

        $this->exito = match ($resultado) {
            'compensada'  => "Tu cita quedó cancelada. Te quedan {$dinero($abono)} de saldo a favor para tu próxima reserva.",
            'saldo_favor' => "Tu cita quedó cancelada. Los {$dinero($cita->anticipo)} del anticipo te quedan como saldo a favor.",
            'retenido'    => 'Tu cita quedó cancelada. Como faltaban menos de ' . $cita->barberia->horas_cancelacion . ' horas, la barbería se queda con el anticipo.',
            default       => 'Tu cita quedó cancelada.',
        };
    }

    public function render()
    {
        $cliente = $this->cliente();
        $barberia = $cliente->barberia;

        $citas = Cita::with(['barbero', 'servicios', 'pagos'])
            ->where('cliente_id', $cliente->id)
            ->orderBy('fecha')->orderBy('hora_inicio')
            ->get();

        // Próximas: confirmadas y las que se están pagando, desde hoy
        $proximas = $citas->filter(fn($c) => in_array($c->estado, ['confirmada', 'pendiente_pago'], true)
            && !($c->estado === 'pendiente_pago' && $c->expira_en?->isPast())
            && $c->fecha->gte(today()))->values();

        $historial = $citas->diff($proximas)->sortByDesc(fn($c) => $c->fecha->toDateString() . $c->hora_inicio)->take(15)->values();

        return view('livewire.panel-cliente', [
            'cliente'   => $cliente,
            'barberia'  => $barberia,
            'proximas'  => $proximas,
            'historial' => $historial,
        ] + $this->datosReagendar())->layout('layouts.publico', ['barberia' => $barberia, 'title' => 'Mis citas']);
    }
}
