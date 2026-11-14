<?php

namespace Database\Seeders;

use App\Models\Barberia;
use App\Models\Barbero;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Un salón de belleza y un negocio de "solo agendamiento" para probar en local.
 *   php artisan db:seed --class=DemoSalonSeeder
 * No corre en producción.
 */
class DemoSalonSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('Este seeder es solo para pruebas locales.');
            return;
        }

        $salon = $this->negocio('salon', 'Salón Bella Rosa', 'Valentina Ríos', 'bella-rosa', 'salon@kaixa.local',
            [['Laura Gómez', 'Estilista'], ['Camila Torres', 'Manicurista'], ['Daniela Pardo', 'Maquilladora']],
            [['Corte y cepillado', 45000, 60], ['Manicure semipermanente', 35000, 45], ['Pedicure spa', 40000, 60],
             ['Tinte completo', 120000, 120], ['Maquillaje social', 80000, 60]]);

        $agenda = $this->negocio('agenda', 'Centro Bienestar Vital', 'Andrés Salazar', 'bienestar', 'agenda@kaixa.local',
            [['Paula Méndez', 'Fisioterapeuta'], ['Julián Ortiz', 'Masajista']],
            [['Sesión de fisioterapia', 70000, 60], ['Masaje relajante', 90000, 60], ['Valoración inicial', 50000, 30]]);

        $this->command?->info('Negocios de prueba listos (contraseña demo12345):');
        $this->command?->table(['Tipo', 'Correo', 'Reservas en línea'], [
            [$salon->nombre_tipo, 'salon@kaixa.local', route('reservar', $salon->slug)],
            [$agenda->nombre_tipo, 'agenda@kaixa.local', route('reservar', $agenda->slug)],
        ]);
    }

    private function negocio(string $tipo, string $nombre, string $dueno, string $slug, string $correo, array $equipo, array $servicios): Barberia
    {
        $negocio = Barberia::updateOrCreate(['slug' => $slug], [
            'tipo_negocio'      => $tipo,
            'nombre'            => $nombre,
            'propietario'       => $dueno,
            'telefono'          => '3104567890',
            'direccion'         => 'Carrera 7 # 45-12, Bogotá',
            'activo'            => true,
            'plan'              => 'mensual',
            'nivel'             => 'pro',
            'reservas_activas'  => true,
            'fecha_vencimiento' => now()->addMonth(),
            'contrato_hasta'    => now()->addMonths((int) config('suscripcion.contrato_minimo_meses')),
        ]);

        User::updateOrCreate(['email' => $correo], [
            'name' => $dueno, 'password' => 'demo12345', 'is_admin' => false, 'barberia_id' => $negocio->id,
        ]);

        $profesionales = collect($equipo)->map(fn($p) => Barbero::updateOrCreate(
            ['barberia_id' => $negocio->id, 'nombre' => $p[0]],
            ['especialidad' => $p[1], 'telefono' => '300' . random_int(1000000, 9999999), 'comision_porcentaje' => 40, 'activo' => true]
        ));

        foreach ($servicios as [$servicio, $precio, $minutos]) {
            Servicio::updateOrCreate(
                ['barberia_id' => $negocio->id, 'nombre' => $servicio],
                ['precio' => $precio, 'duracion_minutos' => $minutos, 'categoria' => 'servicio', 'activo' => true]
            );
        }

        // Algunas citas para mañana (o el próximo día de trabajo)
        if (!Cita::where('barberia_id', $negocio->id)->exists()) {
            $ids = Servicio::where('barberia_id', $negocio->id)->pluck('id');
            $clientas = ['María Fernanda López', 'Sara Jiménez', 'Natalia Castro', 'Juliana Herrera', 'Carolina Vega'];
            $fecha = now()->addDay();
            while ($fecha->isSunday()) {
                $fecha = $fecha->addDay();
            }
            foreach ($clientas as $i => $clienta) {
                $profesional = $profesionales[$i % $profesionales->count()];
                $hora = ['09:00', '10:30', '14:00', '15:30', '11:00'][$i];
                try {
                    Cita::agendar($negocio->id, Cliente::buscarOCrear($negocio->id, $clienta, '31' . str_pad((string) ($i + 1), 8, '7', STR_PAD_LEFT)),
                        $profesional, [$ids[$i % $ids->count()]], ['fecha' => $fecha->toDateString(), 'hora_inicio' => $hora]);
                } catch (\Throwable) {
                    // Hora fuera del horario: se omite
                }
            }
        }

        return $negocio;
    }
}
