<?php

namespace App\Livewire;

use App\Models\Barberia;
use App\Support\Negocio;
use App\Models\Barbero;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\PagoEnLinea;
use App\Models\Servicio;
use App\Support\Wompi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Página pública donde el cliente arma su carrito de servicios, escoge
 * barbero, día y hora, y paga el anticipo con Wompi.
 */
class ReservaEnLinea extends Component
{
    public string $slug = '';

    public array  $servicios = [];
    public int    $barberoId = 0; // 0 = el que esté libre
    public string $fecha = '';
    public string $hora = '';
    public string $nombre = '';
    public string $telefono = '';
    public string $email = '';
    public bool   $acepta = false;

    // Cliente que llega desde su panel "Mis citas" (ya no tiene que escribir sus datos)
    public string $clienteToken = '';

    // Recuperar el enlace de "Mis citas"
    public bool   $mostrarRecuperar = false;
    public string $recuperarTelefono = '';
    public string $recuperarMensaje = '';

    public function mount(string $slug): void
    {
        $this->slug = $slug;
        $barberia = $this->barberia();
        $this->fecha = now()->toDateString();

        $token = (string) request()->query('c', '');
        if ($token !== '' && ($cliente = $this->clienteDelPanel($barberia, $token))) {
            $this->clienteToken = $token;
            $this->nombre   = $cliente->nombre;
            $this->telefono = (string) $cliente->telefono;
            $this->email    = (string) $cliente->email;
        }
    }

    private function clienteDelPanel(Barberia $barberia, ?string $token = null): ?Cliente
    {
        $token ??= $this->clienteToken;

        return $token !== '' ? Cliente::where('barberia_id', $barberia->id)->where('token', $token)->first() : null;
    }

    /** Manda al correo del cliente el enlace de "Mis citas". Siempre responde lo mismo, para no revelar quién es cliente. */
    public function enviarEnlace(): void
    {
        $barberia = $this->barberia();
        $clave = 'recuperar-panel:' . request()->ip();
        $this->recuperarTelefono = Cliente::normalizarTelefono($this->recuperarTelefono);

        $this->validate(['recuperarTelefono' => 'required|digits_between:7,15'], [
            'recuperarTelefono.required' => 'Escribe tu celular.',
            'recuperarTelefono.digits_between' => 'Escribe un celular válido.',
        ]);

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $this->addError('recuperarTelefono', 'Hiciste demasiados intentos. Espera un momento.');
            return;
        }
        RateLimiter::hit($clave, 3600);

        $cliente = Cliente::where('barberia_id', $barberia->id)->where('telefono', $this->recuperarTelefono)->first();
        if ($cliente?->email) {
            try {
                Mail::raw(
                    "Hola {$cliente->nombre}, este es tu enlace para ver y manejar tus citas en {$barberia->nombre}: " . $cliente->urlPanel(),
                    fn($m) => $m->to($cliente->email)->subject("Tus citas en {$barberia->nombre}")
                );
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar el enlace de Mis citas', ['cliente' => $cliente->id, 'error' => $e->getMessage()]);
            }
        }

        $this->mostrarRecuperar = true;
        $this->recuperarMensaje = 'Si tu celular tiene un correo registrado, te enviamos allí el enlace de tus citas. Si no te llega, escríbele ' . voc('al_negocio', $barberia) . '.';
        $this->recuperarTelefono = '';
    }

    private function barberia(): Barberia
    {
        $barberia = Barberia::where('slug', $this->slug)->first();
        abort_unless($barberia && $barberia->recibe_reservas, 404);
        // Palabras y colores según el tipo de negocio (barbería o salón de belleza)
        Negocio::usar($barberia);

        return $barberia;
    }

    public function alternarServicio(int $id): void
    {
        if (in_array($id, $this->servicios, true)) {
            $this->servicios = array_values(array_diff($this->servicios, [$id]));
        } else {
            $this->servicios[] = $id;
        }
        $this->hora = '';
    }

    public function updatedBarberoId(): void { $this->hora = ''; }
    public function updatedFecha(): void { $this->hora = ''; }

    private function serviciosElegidos(Barberia $barberia)
    {
        return Servicio::where('barberia_id', $barberia->id)
            ->where('activo', true)->where('categoria', 'servicio')
            ->whereIn('id', $this->servicios ?: [0])
            ->get();
    }

    private function duracion($servicios): int
    {
        $porDefecto = (int) config('agenda.duracion_por_defecto', 30);
        return (int) $servicios->sum(fn($s) => $s->duracion_minutos ?: $porDefecto);
    }

    private function barberos(Barberia $barberia)
    {
        return Barbero::with('horarios')->where('barberia_id', $barberia->id)->where('activo', true)->orderBy('nombre')->get();
    }

    private function fechaValida(): bool
    {
        try {
            $dia = Carbon::parse($this->fecha)->startOfDay();
        } catch (\Throwable) {
            return false;
        }

        return $dia->gte(today()) && $dia->lte(today()->addDays((int) config('agenda.dias_reserva', 30)));
    }

    /** Horas libres: del barbero escogido o de cualquiera que esté libre. */
    private function horasLibres(Barberia $barberia, int $duracion): array
    {
        if (!$duracion || !$this->fechaValida()) {
            return [];
        }

        $barberos = $this->barberos($barberia)->when($this->barberoId, fn($c) => $c->where('id', $this->barberoId));

        return $barberos
            ->flatMap(fn($b) => Cita::horasDisponibles($b, $this->fecha, $duracion))
            ->unique()->sort()->values()->all();
    }

    public function confirmar()
    {
        $barberia = $this->barberia();
        $clave = 'reserva:' . request()->ip();

        if (RateLimiter::tooManyAttempts($clave, 10)) {
            $this->addError('reserva', 'Hiciste demasiados intentos. Espera un momento e inténtalo de nuevo.');
            return null;
        }
        RateLimiter::hit($clave, 3600);

        $delPanel = $this->clienteDelPanel($barberia);
        if ($delPanel) {
            $this->nombre   = $delPanel->nombre;
            $this->telefono = (string) $delPanel->telefono;
        }

        $this->telefono = Cliente::normalizarTelefono($this->telefono);
        $this->validate([
            'servicios' => 'required|array|min:1',
            'fecha'     => 'required|date',
            'hora'      => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'nombre'    => 'required|min:2|max:120',
            'telefono'  => 'required|digits_between:7,15',
            'email'     => 'nullable|email|max:150',
            'acepta'    => 'accepted',
        ], [
            'servicios.required' => 'Escoge al menos un servicio.',
            'hora.required'      => 'Escoge la hora.',
            'nombre.required'    => 'Escribe tu nombre.',
            'telefono.required'  => 'Escribe tu celular.',
            'telefono.digits_between' => 'Escribe un celular válido.',
            'acepta.accepted'    => 'Debes aceptar la política de reservas y el tratamiento de datos.',
        ]);

        if (!$this->fechaValida()) {
            $this->addError('fecha', 'Escoge una fecha dentro de los próximos ' . config('agenda.dias_reserva') . ' días.');
            return null;
        }

        $servicios = $this->serviciosElegidos($barberia);
        $duracion = $this->duracion($servicios);

        // Barbero escogido, o el primero que tenga libre esa hora
        $barbero = $this->barberos($barberia)
            ->when($this->barberoId, fn($c) => $c->where('id', $this->barberoId))
            ->first(fn($b) => in_array($this->hora, Cita::horasDisponibles($b, $this->fecha, $duracion), true));

        if (!$barbero) {
            $this->hora = '';
            $this->addError('hora', 'Esa hora ya no está disponible. Escoge otra.');
            return null;
        }

        $cliente = $delPanel ?? Cliente::buscarOCrear($barberia->id, $this->nombre, $this->telefono);
        $cliente->update(array_filter([
            'email'           => $cliente->email ?: (trim($this->email) ?: null),
            'acepta_mensajes' => true,
        ], fn($v) => $v !== null));

        $wompi = new Wompi($barberia);
        $porPagar = 0.0;

        try {
            $cita = Cita::agendar($barberia->id, $cliente, $barbero, $servicios->pluck('id')->all(), [
                'fecha'       => $this->fecha,
                'hora_inicio' => $this->hora,
                'antes_de_crear' => function (float $total) use ($barberia, $cliente, $wompi, &$porPagar) {
                    $anticipo = $wompi->configurado() ? $barberia->anticipoPara($total) : 0;
                    $saldo = Cliente::lockForUpdate()->find($cliente->id);
                    $usado = min((float) $saldo->saldo_favor, $anticipo);
                    if ($usado > 0) {
                        $saldo->decrement('saldo_favor', $usado);
                    }
                    $porPagar = $anticipo - $usado;

                    return [
                        'origen'            => 'en_linea',
                        'token'             => Str::random(40),
                        'anticipo'          => $anticipo,
                        'saldo_favor_usado' => $usado,
                        'anticipo_estado'   => $anticipo <= 0 ? null : ($porPagar > 0 ? 'pendiente' : 'pagado'),
                        'estado'            => $porPagar > 0 ? 'pendiente_pago' : 'confirmada',
                        'expira_en'         => $porPagar > 0 ? now()->addMinutes((int) config('agenda.minutos_para_pagar', 15)) : null,
                    ];
                },
            ]);
        } catch (ValidationException $e) {
            $this->hora = '';
            $this->addError('hora', 'Esa hora ya no está disponible. Escoge otra.');
            return null;
        }

        if ($porPagar <= 0) {
            return redirect()->route('reserva.ver', $cita->token);
        }

        $pago = PagoEnLinea::create([
            'barberia_id'    => $barberia->id,
            'cita_id'        => $cita->id,
            'referencia'     => 'KX-' . $cita->id . '-' . Str::upper(Str::random(8)),
            'monto_centavos' => (int) round($porPagar * 100),
        ]);

        return redirect()->away($wompi->urlCheckout(
            $pago->referencia,
            $pago->monto_centavos,
            route('reserva.ver', $cita->token),
            $cita->expira_en,
            ['email' => $cliente->email, 'nombre' => $cliente->nombre, 'telefono' => $cliente->telefono],
        ));
    }

    public function render()
    {
        $barberia = $this->barberia();
        $catalogo = Servicio::where('barberia_id', $barberia->id)
            ->where('activo', true)->where('categoria', 'servicio')->orderBy('nombre')->get();
        $elegidos = $catalogo->whereIn('id', $this->servicios);
        $duracion = $this->duracion($elegidos);
        $total = (float) $elegidos->sum('precio');

        return view('livewire.reserva-en-linea', [
            'barberia' => $barberia,
            'catalogo' => $catalogo,
            'elegidos' => $elegidos,
            'duracion' => $duracion,
            'total'    => $total,
            'anticipo' => (new Wompi($barberia))->configurado() ? $barberia->anticipoPara($total) : 0,
            'barberos' => $this->barberos($barberia),
            'horas'    => $this->horasLibres($barberia, $duracion),
            'clientePanel' => $this->clienteDelPanel($barberia),
        ])->layout('layouts.publico', ['barberia' => $barberia, 'title' => 'Reservar cita']);
    }
}
