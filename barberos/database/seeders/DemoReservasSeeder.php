<?php

namespace Database\Seeders;

use App\Models\Barberia;
use App\Models\Barbero;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\PagoEnLinea;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Datos para probar en local el panel del cliente "Mis citas" y el reagendar.
 *   php artisan db:seed --class=DemoReservasSeeder
 * Imprime el enlace del panel de una clienta de prueba. No corre en producción.
 */
class DemoReservasSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('Este seeder es solo para pruebas locales.');
            return;
        }

        $barberia = Barberia::where('slug', 'demo')->first() ?? Barberia::where('nivel', 'pro')->first();
        if (!$barberia) {
            $this->command?->error('Primero corre "php artisan db:seed" para crear la barbería demo.');
            return;
        }

        $barberia->update([
            'nivel'            => 'pro',
            'slug'             => $barberia->slug ?: 'demo',
            'reservas_activas' => true,
        ]);

        $barbero = Barbero::where('barberia_id', $barberia->id)->where('activo', true)->first()
            ?? Barbero::create(['barberia_id' => $barberia->id, 'nombre' => 'Carlos Ruiz', 'comision_porcentaje' => 50, 'activo' => true]);

        $usuarioBarbero = User::updateOrCreate(['email' => 'barbero@kaixa.local'], [
            'name' => $barbero->nombre, 'password' => 'demo12345',
            'barberia_id' => $barberia->id, 'barbero_id' => $barbero->id, 'rol' => 'barbero',
        ]);

        foreach ([['Corte clásico', 25000, 30], ['Corte + barba', 40000, 45], ['Barba', 18000, 20]] as [$nombre, $precio, $min]) {
            Servicio::firstOrCreate(
                ['barberia_id' => $barberia->id, 'nombre' => $nombre],
                ['precio' => $precio, 'duracion_minutos' => $min, 'categoria' => 'servicio', 'activo' => true]
            );
        }
        $servicios = Servicio::where('barberia_id', $barberia->id)->where('categoria', 'servicio')->where('activo', true)->get();

        $cliente = Cliente::buscarOCrear($barberia->id, 'Laura Gómez (prueba)', '3001112233');
        $cliente->update(['email' => $cliente->email ?: 'laura@example.com', 'acepta_mensajes' => true]);

        $conWompi = $barberia->wompi_configurado;

        // Busca un día de trabajo libre a partir de $dias días
        $agendar = function (int $dias, string $hora, Servicio $servicio, \Closure $extra) use ($barberia, $barbero, $cliente) {
            for ($i = $dias; $i < $dias + 14; $i++) {
                $fecha = now()->addDays($i)->toDateString();
                if (in_array($hora, Cita::horasDisponibles($barbero, $fecha, $servicio->duracion_minutos ?: 30), true)) {
                    return Cita::agendar($barberia->id, $cliente, $barbero, [$servicio->id], [
                        'fecha' => $fecha, 'hora_inicio' => $hora,
                        'antes_de_crear' => fn($total) => array_merge(['origen' => 'en_linea', 'token' => Str::random(40)], $extra($total)),
                    ]);
                }
            }
            return null;
        };

        $pagado = fn($total) => $conWompi ? ['anticipo' => $barberia->anticipoPara($total), 'anticipo_estado' => 'pagado'] : [];

        // 1. Cita confirmada (con anticipo pagado si hay Wompi)
        $agendar(1, '10:00', $servicios[0], $pagado);

        // 2. Cita que la barbería movió y espera la respuesta del cliente
        $movida = $agendar(3, '16:00', $servicios[1] ?? $servicios[0], $pagado);
        if ($movida) {
            $nueva = now()->addDays(4)->toDateString();
            $hora = Cita::horasDisponibles($barbero, $nueva, $movida->duracion)[4] ?? null;
            if ($hora) {
                $movida->reagendar($barbero, $nueva, $hora, 'barberia', 'El barbero tiene una cita médica');
            }
        }

        // 3. Reserva con el anticipo sin pagar (solo si hay Wompi)
        if ($conWompi) {
            $pendiente = $agendar(6, '09:00', $servicios[0], fn($total) => [
                'anticipo' => $barberia->anticipoPara($total), 'anticipo_estado' => 'pendiente',
                'estado' => 'pendiente_pago', 'expira_en' => now()->addMinutes((int) config('agenda.minutos_para_pagar', 15)),
            ]);
            if ($pendiente) {
                PagoEnLinea::create([
                    'barberia_id' => $barberia->id, 'cita_id' => $pendiente->id,
                    'referencia' => 'KX-' . $pendiente->id . '-' . Str::upper(Str::random(8)),
                    'monto_centavos' => (int) round((float) $pendiente->anticipo * 100),
                ]);
            }
        }

        $this->command?->info('Panel del cliente:   ' . $cliente->urlPanel());
        $this->command?->info('Página de reservas:  ' . route('reservar', $barberia->slug));
        $this->command?->info("Agenda del barbero:  {$usuarioBarbero->email} / demo12345  →  /mi-agenda");
        if (!$conWompi) {
            $this->command?->warn('La barbería no tiene llaves de Wompi: las reservas se hacen sin anticipo. Pon las llaves de pruebas en "Mi barbería" para probar el pago.');
        }
    }
}
