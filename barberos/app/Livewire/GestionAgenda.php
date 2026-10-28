<?php

namespace App\Livewire;

use App\Livewire\Concerns\FormularioCita;
use App\Models\Barbero;
use App\Models\Cita;
use App\Models\HorarioBarbero;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Agenda de citas de la barbería (plan Pro): día por día, por barbero,
 * y los horarios de trabajo de cada uno.
 */
class GestionAgenda extends Component
{
    use FormularioCita;

    public string $tab = 'agenda';
    public string $fecha = '';
    public int    $barberoFiltro = 0;
    public string $exito = '';

    // Horarios: [barbero_id => [dia => ['trabaja' => bool, 'inicio' => 'HH:MM', 'fin' => 'HH:MM']]]
    public ?int  $horarioBarberoId = null;
    public array $horario = [];

    protected function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function mount(): void
    {
        $this->fecha = now()->toDateString();
    }

    public function irA(string $cuando): void
    {
        $fecha = Carbon::parse($this->fecha ?: now());
        $this->fecha = match ($cuando) {
            'anterior'  => $fecha->subDay()->toDateString(),
            'siguiente' => $fecha->addDay()->toDateString(),
            default     => now()->toDateString(),
        };
        $this->exito = '';
    }

    private function cita(int $id): Cita
    {
        return Cita::where('barberia_id', $this->barberiaId())->findOrFail($id);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        if (!in_array($estado, ['cancelada', 'no_asistio', 'confirmada'], true)) {
            return;
        }

        $cita = $this->cita($id);

        // Una cita cobrada ya tiene su venta: se maneja desde Ventas
        if ($cita->estado === 'atendida') {
            return;
        }

        $dinero = fn($v) => '$' . number_format((float) $v, 0, ',', '.');

        if ($estado === 'cancelada') {
            $resultado = $cita->cancelar(porCliente: false);
            $this->exito = $resultado === 'saldo_favor'
                ? "Cita cancelada. El anticipo ({$dinero($cita->anticipo)}) le quedó a {$cita->cliente->nombre} como saldo a favor."
                : 'Cita cancelada. La hora quedó libre.';
            return;
        }

        if ($estado === 'no_asistio') {
            $tenia = $cita->anticipo_estado === 'pagado';
            $cita->marcarNoAsistio();
            $this->exito = $tenia
                ? "Se marcó que el cliente no asistió. El anticipo ({$dinero($cita->anticipo)}) quedó como ingreso de la barbería."
                : 'Se marcó que el cliente no asistió.';
            return;
        }

        // Volver a confirmar solo citas sin anticipo y si la hora sigue libre
        if ($estado === 'confirmada') {
            if ($cita->anticipo_estado !== null || !in_array($cita->estado, ['cancelada', 'no_asistio'], true)) {
                return;
            }
            $libres = Cita::horasDisponibles($cita->barbero, $cita->fecha->toDateString(), Cita::minutos($cita->hora_fin) - Cita::minutos($cita->hora_inicio), $cita->id);
            if (!in_array($cita->hora_inicio, $libres, true)) {
                $this->exito = '';
                $this->addError('agenda', 'Esa hora ya la ocupa otra cita.');
                return;
            }
        }

        $cita->update(['estado' => 'confirmada']);
        $this->exito = 'Cita confirmada de nuevo.';
    }

    // ── Horarios ─────────────────────────────────────────────────────────────

    public function editarHorario(int $barberoId): void
    {
        $barbero = Barbero::with('horarios')->where('barberia_id', $this->barberiaId())->findOrFail($barberoId);

        $this->horarioBarberoId = $barbero->id;
        $this->horario = [];
        foreach (array_keys(HorarioBarbero::DIAS) as $dia) {
            $actual = $barbero->horarioDelDia($dia);
            $this->horario[$dia] = [
                'trabaja' => (bool) $actual,
                'inicio'  => $actual['inicio'] ?? '09:00',
                'fin'     => $actual['fin'] ?? '19:00',
            ];
        }
    }

    public function guardarHorario(): void
    {
        $barbero = Barbero::where('barberia_id', $this->barberiaId())->findOrFail($this->horarioBarberoId);

        foreach ($this->horario as $dia => $h) {
            if (empty($h['trabaja'])) continue;
            if (!preg_match('/^\d{2}:\d{2}$/', $h['inicio'] ?? '') || !preg_match('/^\d{2}:\d{2}$/', $h['fin'] ?? '') || $h['inicio'] >= $h['fin']) {
                $this->addError('horario', 'Revisa el horario del ' . mb_strtolower(HorarioBarbero::DIAS[$dia]) . ': la hora de salida debe ser después de la de entrada.');
                return;
            }
        }

        DB::transaction(function () use ($barbero) {
            $barbero->horarios()->delete();
            foreach ($this->horario as $dia => $h) {
                if (!empty($h['trabaja'])) {
                    $barbero->horarios()->create(['dia_semana' => (int) $dia, 'hora_inicio' => $h['inicio'], 'hora_fin' => $h['fin']]);
                }
            }
        });

        $this->horarioBarberoId = null;
        $this->exito = "Horario de {$barbero->nombre} guardado.";
    }

    public function cerrarHorario(): void
    {
        $this->resetErrorBag();
        $this->horarioBarberoId = null;
    }

    public function render()
    {
        $barberiaId = $this->barberiaId();
        $dia = Carbon::parse($this->fecha ?: now());

        $barberos = Barbero::with('horarios')
            ->where('barberia_id', $barberiaId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $citas = Cita::with(['cliente', 'barbero', 'servicios'])
            ->where('barberia_id', $barberiaId)
            ->whereDate('fecha', $dia->toDateString())
            ->when($this->barberoFiltro, fn($q) => $q->where('barbero_id', $this->barberoFiltro))
            ->orderBy('hora_inicio')
            ->get();

        $columnas = $barberos
            ->when($this->barberoFiltro, fn($c) => $c->where('id', $this->barberoFiltro))
            ->map(fn($b) => [
                'barbero' => $b,
                'horario' => $b->horarioDelDia($dia->dayOfWeek),
                'citas'   => $citas->where('barbero_id', $b->id)->values(),
            ])
            ->values();

        $activas = $citas->whereIn('estado', Cita::OCUPAN);

        return view('livewire.gestion-agenda', [
            'dia'       => $dia,
            'barberos'  => $barberos,
            'columnas'  => $columnas,
            'resumen'   => [
                'citas'      => $activas->count(),
                'pendientes' => $citas->where('estado', 'confirmada')->count(),
                'atendidas'  => $citas->where('estado', 'atendida')->count(),
                'valor'      => $activas->sum('total'),
            ],
            'barberoHorario' => $this->horarioBarberoId ? $barberos->firstWhere('id', $this->horarioBarberoId) : null,
        ] + $this->datosFormularioCita())->layout('layouts.app', ['title' => 'Agenda de citas']);
    }
}
