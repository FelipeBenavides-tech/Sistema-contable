<?php

namespace App\Livewire\Concerns;

use App\Models\Barbero;
use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Mover una cita a otro día u hora. Lo usan la agenda del dueño, la del
 * barbero (cuando no puede atenderla) y el panel del cliente.
 */
trait ReagendarCita
{
    public ?int   $reCitaId    = null;
    public int    $reBarberoId = 0;
    public string $reFecha     = '';
    public string $reHora      = '';
    public string $reMotivo    = '';

    // Enlace de WhatsApp para avisarle al cliente después de moverle la cita
    public string $avisoWhatsapp = '';

    /** La cita que se puede mover desde esta pantalla (filtrada por barbería, barbero o cliente). */
    abstract protected function citaParaReagendar(int $id): Cita;

    /** barberia | cliente */
    abstract protected function reagendaQuien(): string;

    // El barbero solo mueve citas dentro de su propia agenda
    protected function reagendarBarberoFijo(): ?int
    {
        return null;
    }

    public function abrirReagendar(int $id): void
    {
        $cita = $this->citaParaReagendar($id);
        if ($cita->estado !== 'confirmada') {
            return;
        }

        $this->resetErrorBag();
        $this->reCitaId    = $cita->id;
        $this->reBarberoId = $this->reagendarBarberoFijo() ?? $cita->barbero_id;
        $this->reFecha     = $cita->fecha->isPast() ? now()->toDateString() : $cita->fecha->toDateString();
        $this->reHora      = '';
        $this->reMotivo    = '';
        $this->avisoWhatsapp = '';
    }

    public function cerrarReagendar(): void
    {
        $this->resetErrorBag();
        $this->reCitaId = null;
    }

    public function updatedReBarberoId(): void { $this->reHora = ''; }
    public function updatedReFecha(): void { $this->reHora = ''; }

    private function barberoReagendar(Cita $cita): ?Barbero
    {
        $id = $this->reagendarBarberoFijo() ?? ($this->reBarberoId ?: $cita->barbero_id);

        return Barbero::with('horarios')->where('barberia_id', $cita->barberia_id)->where('activo', true)->find($id);
    }

    private function fechaReagendarValida(): bool
    {
        try {
            $dia = Carbon::parse($this->reFecha)->startOfDay();
        } catch (\Throwable) {
            return false;
        }

        return $dia->gte(today()) && $dia->lte(today()->addDays((int) config('agenda.dias_reserva', 30) + 30));
    }

    public function guardarReagendar(): void
    {
        if (!$this->reCitaId) {
            return;
        }

        $this->validate([
            'reFecha'  => 'required|date',
            'reHora'   => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'reMotivo' => 'nullable|max:250',
        ], [
            'reHora.required' => 'Escoge la nueva hora.',
            'reMotivo.max'    => 'El motivo puede tener máximo 250 letras.',
        ]);

        $cita = $this->citaParaReagendar($this->reCitaId);
        $barbero = $this->barberoReagendar($cita);

        if (!$barbero || !$this->fechaReagendarValida()) {
            $this->addError('reagendar', 'Escoge ' . voc('un_profesional') . ' y una fecha válida.');
            return;
        }

        $antes = $cita->inicio->copy();

        try {
            $cita->reagendar($barbero, $this->reFecha, $this->reHora, $this->reagendaQuien(), $this->reMotivo ?: null);
        } catch (ValidationException $e) {
            $this->reHora = '';
            $this->addError('reagendar', collect($e->errors())->flatten()->first());
            return;
        }

        $this->reCitaId = null;
        $this->despuesDeReagendar($cita->load(['cliente', 'barbero', 'barberia']), $antes);
    }

    /** Mensaje de confirmación y aviso al cliente. */
    protected function despuesDeReagendar(Cita $cita, Carbon $antes): void
    {
        $nueva = $cita->fecha->translatedFormat('l j \d\e F') . ' a las ' . Carbon::createFromFormat('H:i', $cita->hora_inicio)->format('g:i a');

        if ($this->reagendaQuien() === 'cliente') {
            $this->exito = "Listo, tu cita quedó para el {$nueva} con {$cita->barbero->nombre}.";
            return;
        }

        $cliente = $cita->cliente;
        $texto = "Hola {$cliente->nombre}, te escribimos de {$cita->barberia->nombre}. "
            . 'No podemos atenderte el ' . $antes->translatedFormat('l j \d\e F') . ' a las ' . $antes->format('g:i a')
            . ". Te pasamos la cita para el {$nueva} con {$cita->barbero->nombre}."
            . ($cita->reagendo_motivo ? " Motivo: {$cita->reagendo_motivo}." : '')
            . ' Confirma si te sirve, escoge otra hora o cancela sin perder tu anticipo aquí: ' . $cliente->urlPanel();

        $this->avisoWhatsapp = (string) $cliente->whatsappUrl($texto);

        if ($cliente->email) {
            try {
                Mail::raw($texto, fn($m) => $m->to($cliente->email)->subject("Tu cita en {$cita->barberia->nombre} cambió de hora"));
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar el correo de cita reagendada', ['cita' => $cita->id, 'error' => $e->getMessage()]);
            }
        }

        $this->exito = "Cita de {$cliente->nombre} movida al {$nueva} con {$cita->barbero->nombre}. "
            . ($this->avisoWhatsapp ? 'Avísale por WhatsApp para que confirme.' : 'El cliente no tiene celular: avísale por otro medio.')
            . ($cliente->email ? ' También le enviamos un correo.' : '');
    }

    protected function datosReagendar(): array
    {
        if (!$this->reCitaId) {
            return ['citaReagendar' => null];
        }

        $cita = $this->citaParaReagendar($this->reCitaId)->load(['cliente', 'barbero', 'servicios']);
        $barbero = $this->barberoReagendar($cita);

        return [
            'citaReagendar'      => $cita,
            'barberosReagendar'  => $this->reagendarBarberoFijo() ? collect()
                : Barbero::where('barberia_id', $cita->barberia_id)->where('activo', true)->orderBy('nombre')->get(),
            'horasReagendar'     => $barbero && $this->fechaReagendarValida()
                ? Cita::horasDisponibles($barbero, $this->reFecha, $cita->duracion, $cita->id)
                : [],
        ];
    }
}
