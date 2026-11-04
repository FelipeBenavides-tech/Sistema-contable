<?php

namespace App\Livewire\Concerns;

use App\Models\Barbero;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Servicio;
use Illuminate\Validation\ValidationException;

/**
 * Formulario para agendar una cita. Lo usan la agenda del dueño y la del barbero.
 */
trait FormularioCita
{
    public bool   $mostrarCita       = false;
    public int    $citaClienteId     = 0;
    public string $citaBuscarCliente = '';
    public string $citaNombre        = '';
    public string $citaTelefono      = '';
    public int    $citaBarberoId     = 0;
    public array  $citaServicios     = [];
    public string $citaFecha         = '';
    public string $citaHora          = '';
    public string $citaNotas         = '';

    abstract protected function barberiaId(): int;

    // El barbero solo agenda para sí mismo y no puede buscar en la base de clientes
    protected function puedeBuscarClientes(): bool
    {
        return true;
    }

    protected function barberoFijo(): ?int
    {
        return null;
    }

    public function nuevaCita(int $barberoId = 0, string $hora = ''): void
    {
        $this->resetErrorBag();
        $this->reset(['citaClienteId', 'citaBuscarCliente', 'citaNombre', 'citaTelefono', 'citaServicios', 'citaNotas']);
        $this->citaBarberoId = $this->barberoFijo() ?? $barberoId;
        $this->citaFecha     = $this->fecha ?? now()->toDateString();
        $this->citaHora      = $hora;
        $this->mostrarCita   = true;
    }

    public function cerrarCita(): void
    {
        $this->resetErrorBag();
        $this->mostrarCita = false;
    }

    public function elegirClienteCita(int $clienteId): void
    {
        if (!$this->puedeBuscarClientes()) {
            return;
        }

        $cliente = Cliente::where('barberia_id', $this->barberiaId())->findOrFail($clienteId);
        $this->citaClienteId     = $cliente->id;
        $this->citaNombre        = $cliente->nombre;
        $this->citaTelefono      = $cliente->telefono ?? '';
        $this->citaBuscarCliente = '';
    }

    public function quitarClienteCita(): void
    {
        $this->reset(['citaClienteId', 'citaNombre', 'citaTelefono']);
    }

    // Si cambia el barbero, los servicios o el día, la hora escogida puede dejar de servir
    public function updatedCitaBarberoId(): void { $this->citaHora = ''; }
    public function updatedCitaServicios(): void { $this->citaHora = ''; }
    public function updatedCitaFecha(): void { $this->citaHora = ''; }

    protected function duracionCita(): int
    {
        $porDefecto = (int) config('agenda.duracion_por_defecto', 30);

        return (int) Servicio::where('barberia_id', $this->barberiaId())
            ->whereIn('id', $this->citaServicios ?: [0])
            ->get()
            ->sum(fn($s) => $s->duracion_minutos ?: $porDefecto);
    }

    protected function horasLibresCita(): array
    {
        $barbero = $this->citaBarberoId
            ? Barbero::where('barberia_id', $this->barberiaId())->where('activo', true)->find($this->citaBarberoId)
            : null;
        $duracion = $this->duracionCita();

        if (!$barbero || !$duracion || !$this->citaFecha) {
            return [];
        }

        return Cita::horasDisponibles($barbero, $this->citaFecha, $duracion);
    }

    public function guardarCita(): void
    {
        if ($fijo = $this->barberoFijo()) {
            $this->citaBarberoId = $fijo;
        }

        $this->citaTelefono = Cliente::normalizarTelefono($this->citaTelefono);

        $this->validate([
            'citaNombre'      => 'nullable|max:120',
            'citaTelefono'    => 'nullable|digits_between:7,15',
            'citaBarberoId'   => 'required|integer|min:1',
            'citaServicios'   => 'required|array|min:1',
            'citaFecha'       => 'required|date|after_or_equal:today',
            'citaHora'        => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'citaNotas'       => 'nullable|max:250',
        ], [
            'citaBarberoId.min'           => 'Escoge el barbero.',
            'citaServicios.required'      => 'Escoge al menos un servicio.',
            'citaFecha.after_or_equal'    => 'La fecha no puede ser anterior a hoy.',
            'citaHora.required'           => 'Escoge la hora.',
            'citaTelefono.digits_between' => 'Escribe un celular válido (solo números).',
        ]);

        if (!$this->citaClienteId && mb_strlen(trim($this->citaNombre)) < 2) {
            $this->addError('citaNombre', 'Escribe el nombre del cliente.');
            return;
        }

        $barberiaId = $this->barberiaId();
        $barbero = Barbero::where('barberia_id', $barberiaId)->where('activo', true)->findOrFail($this->citaBarberoId);

        $cliente = $this->citaClienteId && $this->puedeBuscarClientes()
            ? Cliente::where('barberia_id', $barberiaId)->findOrFail($this->citaClienteId)
            : Cliente::buscarOCrear($barberiaId, $this->citaNombre, $this->citaTelefono);

        try {
            Cita::agendar($barberiaId, $cliente, $barbero, array_map('intval', $this->citaServicios), [
                'fecha'       => $this->citaFecha,
                'hora_inicio' => $this->citaHora,
                'notas'       => trim($this->citaNotas) ?: null,
            ], auth()->id());
        } catch (ValidationException $e) {
            $this->addError('citaHora', collect($e->errors())->flatten()->first());
            return;
        }

        $this->fecha = $this->citaFecha;
        $this->mostrarCita = false;
        $this->exito = "Cita agendada para {$cliente->nombre} el " . \Carbon\Carbon::parse($this->citaFecha)->format('d/m') . " a las {$this->citaHora}.";
    }

    protected function datosFormularioCita(): array
    {
        if (!$this->mostrarCita) {
            return [];
        }

        $termino = trim($this->citaBuscarCliente);

        return [
            'serviciosCita'   => Servicio::where('barberia_id', $this->barberiaId())
                ->where('activo', true)->where('categoria', 'servicio')->orderBy('nombre')->get(),
            'horasLibres'     => $this->horasLibresCita(),
            'duracionCita'    => $this->duracionCita(),
            'clientesCita'    => $this->puedeBuscarClientes() && mb_strlen($termino) >= 2
                ? Cliente::where('barberia_id', $this->barberiaId())->buscar($termino)->orderBy('nombre')->limit(6)->get()
                : collect(),
        ];
    }
}
