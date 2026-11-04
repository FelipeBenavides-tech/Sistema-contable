<?php

namespace App\Livewire;

use App\Livewire\Concerns\FormularioCita;
use App\Models\Cita;
use App\Models\Venta;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Lo único que ve un barbero: sus citas, agendar para sí mismo y
 * lo que lleva hecho cada día (servicios y comisión).
 */
class MiAgenda extends Component
{
    use FormularioCita;

    public string $fecha = '';
    public string $exito = '';

    protected function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    protected function barberoFijo(): ?int
    {
        return auth()->user()->barbero_id;
    }

    // El barbero no tiene acceso a la base de clientes
    protected function puedeBuscarClientes(): bool
    {
        return false;
    }

    public function mount()
    {
        // El dueño tiene su propia agenda con todos los barberos
        if (!auth()->user()->esBarbero()) {
            return redirect()->route('agenda');
        }

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

    public function render()
    {
        $user = auth()->user();
        $barbero = $user->barbero;
        $dia = Carbon::parse($this->fecha ?: now());

        $citas = $barbero
            ? Cita::with(['cliente:id,nombre', 'servicios'])
                ->where('barberia_id', $this->barberiaId())
                ->where('barbero_id', $barbero->id)
                ->whereDate('fecha', $dia->toDateString())
                ->orderBy('hora_inicio')
                ->get()
            : collect();

        $ventas = $barbero
            ? Venta::with('items')->activas()
                ->where('barberia_id', $this->barberiaId())
                ->where('barbero_id', $barbero->id)
                ->whereDate('fecha', $dia->toDateString())
                ->orderBy('created_at')
                ->get()
            : collect();

        // Próximos 7 días: cuántas citas tiene cada día
        $semana = $barbero
            ? Cita::where('barbero_id', $barbero->id)
                ->ocupan()
                ->whereBetween('fecha', [now()->toDateString(), now()->addDays(6)->toDateString()])
                ->get(['fecha'])
                ->groupBy(fn($c) => $c->fecha->toDateString())
                ->map->count()
            : collect();

        return view('livewire.mi-agenda', [
            'barbero'  => $barbero,
            'dia'      => $dia,
            'citas'    => $citas,
            'ventas'   => $ventas,
            'semana'   => $semana,
            'horario'  => $barbero?->horarioDelDia($dia->dayOfWeek),
            'barberos' => collect(),
        ] + $this->datosFormularioCita())->layout('layouts.app', ['title' => 'Mi agenda']);
    }
}
