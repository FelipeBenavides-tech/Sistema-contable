<?php

namespace Tests\Feature;

use App\Livewire\GestionAgenda;
use App\Livewire\MiAgenda;
use App\Livewire\PanelCliente;
use App\Livewire\ReservaEnLinea;
use App\Livewire\VerReserva;
use App\Models\Barberia;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\PagoEnLinea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class PanelClienteYReagendarTest extends TestCase
{
    use CreaDatosDePrueba, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Martes 3 de noviembre, 8:00 a. m.
        Carbon::setTestNow(Carbon::parse('2026-11-03 08:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function barberiaConReservas(): Barberia
    {
        $barberia = $this->crearBarberia('Barbería Pro', 'pro');
        $barberia->update([
            'slug'                     => 'barberia-pro',
            'reservas_activas'         => true,
            'anticipo_porcentaje'      => 50,
            'horas_cancelacion'        => 4,
            'wompi_llave_publica'      => 'pub_test_abc123',
            'wompi_llave_privada'      => 'prv_test_abc123',
            'wompi_secreto_eventos'    => 'test_events_secreto',
            'wompi_secreto_integridad' => 'test_integrity_secreto',
        ]);

        return $barberia->fresh();
    }

    /** Reserva y paga el anticipo (aviso de Wompi aprobado). */
    private function citaPagada(Barberia $barberia, int $servicioId, string $hora = '10:00'): Cita
    {
        Livewire::test(ReservaEnLinea::class, ['slug' => $barberia->slug])
            ->call('alternarServicio', $servicioId)
            ->set('fecha', '2026-11-04')
            ->set('hora', $hora)
            ->set('nombre', 'Laura Gómez')
            ->set('telefono', '3001112233')
            ->set('email', 'laura@example.com')
            ->set('acepta', true)
            ->call('confirmar');

        $cita = Cita::latest('id')->first();
        PagoEnLinea::where('cita_id', $cita->id)->sole()->aplicarTransaccion([
            'id' => 'tx-' . $cita->id, 'reference' => $cita->pagos()->sole()->referencia, 'status' => 'APPROVED',
            'amount_in_cents' => $cita->pagos()->sole()->monto_centavos, 'currency' => 'COP', 'payment_method_type' => 'NEQUI',
        ]);

        return $cita->fresh();
    }

    public function test_el_cliente_ve_sus_citas_en_su_panel(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $cita = $this->citaPagada($barberia, $this->crearServicio($barberia, 20000)->id);
        $cliente = Cliente::sole();

        // El enlace de la reserva lleva al panel
        Livewire::test(VerReserva::class, ['token' => $cita->token])->assertSee('Ver todas mis citas');
        $this->assertNotNull($cliente->fresh()->token);

        $this->get(route('cliente.panel', $cliente->fresh()->token))
            ->assertOk()
            ->assertSee('Hola, Laura')
            ->assertSee('Anticipo pagado')
            ->assertSee('Reservar una cita');

        $this->get('/mis-citas/token-que-no-existe')->assertNotFound();
    }

    public function test_desde_el_panel_reserva_sin_volver_a_escribir_sus_datos(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $corte = $this->crearServicio($barberia, 20000);
        $cliente = $this->crearCliente($barberia);
        $token = $cliente->tokenPanel();

        $this->withoutExceptionHandling();
        $respuesta = Livewire::withQueryParams(['c' => $token])->test(ReservaEnLinea::class, ['slug' => $barberia->slug])
            ->assertSet('nombre', $cliente->nombre)
            ->call('alternarServicio', $corte->id)
            ->set('fecha', '2026-11-04')
            ->set('hora', '10:00')
            ->set('acepta', true)
            ->call('confirmar');

        $this->assertStringStartsWith('https://checkout.wompi.co/p/?', $respuesta->effects['redirect']);
        $this->assertSame($cliente->id, Cita::sole()->cliente_id);
        $this->assertSame(1, Cliente::count());
    }

    public function test_el_cliente_paga_el_anticipo_pendiente_desde_el_panel(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        Livewire::test(ReservaEnLinea::class, ['slug' => $barberia->slug])
            ->call('alternarServicio', $this->crearServicio($barberia, 20000)->id)
            ->set('fecha', '2026-11-04')->set('hora', '10:00')
            ->set('nombre', 'Laura')->set('telefono', '3001112233')->set('acepta', true)
            ->call('confirmar');
        $cita = Cita::sole();

        $respuesta = Livewire::test(PanelCliente::class, ['token' => Cliente::sole()->tokenPanel()])
            ->assertSee('Falta pagar el anticipo')
            ->call('pagar', $cita->id);

        $this->assertStringContainsString('reference=' . PagoEnLinea::sole()->referencia, $respuesta->effects['redirect']);
    }

    public function test_el_barbero_reagenda_una_cita_que_no_puede_atender(): void
    {
        Mail::fake();
        $barberia = $this->barberiaConReservas();
        $barbero = $this->crearBarbero($barberia);
        $cita = $this->citaPagada($barberia, $this->crearServicio($barberia, 20000)->id);
        $usuario = $this->crearUsuarioBarbero($barberia, $barbero);

        Livewire::actingAs($usuario)->test(MiAgenda::class)
            ->set('fecha', '2026-11-04')
            ->assertSee('No puedo · Reagendar')
            ->call('abrirReagendar', $cita->id)
            ->set('reFecha', '2026-11-05')
            ->set('reHora', '15:00')
            ->set('reMotivo', 'Tengo una cita médica')
            ->call('guardarReagendar')
            ->assertHasNoErrors()
            ->assertSee('Avisar por WhatsApp');

        $cita->refresh();
        $this->assertSame('2026-11-05', $cita->fecha->toDateString());
        $this->assertSame('15:00', $cita->hora_inicio);
        $this->assertSame('15:30', $cita->hora_fin);
        $this->assertSame('confirmada', $cita->estado);
        $this->assertSame('pagado', $cita->anticipo_estado); // el anticipo se conserva
        $this->assertSame('pendiente', $cita->reagendo_respuesta);
        $this->assertSame(['fecha' => '2026-11-04', 'hora_inicio' => '10:00', 'barbero_id' => $barbero->id], $cita->reagendada_desde);

        // La hora vieja quedó libre y la nueva ocupada
        $this->assertContains('10:00', Cita::horasDisponibles($barbero, '2026-11-04', 30));
        $this->assertNotContains('15:00', Cita::horasDisponibles($barbero, '2026-11-05', 30));
    }

    public function test_no_se_reagenda_a_una_hora_ocupada(): void
    {
        $barberia = $this->barberiaConReservas();
        $barbero = $this->crearBarbero($barberia);
        $corte = $this->crearServicio($barberia, 20000);
        $cita = $this->citaPagada($barberia, $corte->id, '10:00');
        $this->citaPagada($barberia, $corte->id, '11:00');

        Livewire::actingAs($this->crearUsuario($barberia))->test(GestionAgenda::class)
            ->call('abrirReagendar', $cita->id)
            ->set('reFecha', '2026-11-04')
            ->set('reHora', '11:00')
            ->call('guardarReagendar')
            ->assertHasErrors('reagendar');

        $this->assertSame('10:00', $cita->fresh()->hora_inicio);
    }

    public function test_el_barbero_no_reagenda_citas_de_otro_barbero(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $cita = $this->citaPagada($barberia, $this->crearServicio($barberia, 20000)->id);
        $otro = $this->crearBarbero($barberia);
        $otro->update(['nombre' => 'Otro']);

        Livewire::actingAs($this->crearUsuarioBarbero($barberia, $otro))->test(MiAgenda::class)
            ->call('abrirReagendar', $cita->id)
            ->assertNotFound();
    }

    public function test_el_dueno_puede_pasar_la_cita_a_otro_barbero(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $cita = $this->citaPagada($barberia, $this->crearServicio($barberia, 20000)->id);
        $otro = $this->crearBarbero($barberia);

        Livewire::actingAs($this->crearUsuario($barberia))->test(GestionAgenda::class)
            ->call('abrirReagendar', $cita->id)
            ->set('reBarberoId', $otro->id)
            ->set('reHora', '10:00')
            ->call('guardarReagendar')
            ->assertHasNoErrors();

        $this->assertSame($otro->id, $cita->fresh()->barbero_id);
    }

    public function test_el_cliente_acepta_la_nueva_hora(): void
    {
        $barberia = $this->barberiaConReservas();
        $barbero = $this->crearBarbero($barberia);
        $cita = $this->citaPagada($barberia, $this->crearServicio($barberia, 20000)->id);
        $cita->reagendar($barbero, '2026-11-05', '15:00', 'barberia', 'Calamidad');

        Livewire::test(PanelCliente::class, ['token' => Cliente::sole()->tokenPanel()])
            ->assertSee('La barbería no puede atenderte')
            ->assertSee('Calamidad')
            ->call('aceptar', $cita->id)
            ->assertSee('Te esperamos');

        $this->assertSame('aceptada', $cita->fresh()->reagendo_respuesta);
    }

    public function test_si_la_nueva_hora_no_le_sirve_cancela_sin_perder_el_anticipo(): void
    {
        $barberia = $this->barberiaConReservas();
        $barbero = $this->crearBarbero($barberia);
        $cita = $this->citaPagada($barberia, $this->crearServicio($barberia, 20000)->id);
        // La barbería la mueve a una hora cercana (menos de 4 horas de aviso)
        Carbon::setTestNow(Carbon::parse('2026-11-04 09:00:00'));
        $cita->reagendar($barbero, '2026-11-04', '11:00', 'barberia');

        Livewire::test(PanelCliente::class, ['token' => Cliente::sole()->tokenPanel()])
            ->call('cancelar', $cita->id);

        // Conserva el anticipo (10.000) y recibe el 10% de descuento (2.000)
        $this->assertSame('cancelada', $cita->fresh()->estado);
        $this->assertEquals(12000, Cliente::sole()->saldo_favor);
    }

    public function test_el_cliente_escoge_otra_hora(): void
    {
        $barberia = $this->barberiaConReservas();
        $barbero = $this->crearBarbero($barberia);
        $cita = $this->citaPagada($barberia, $this->crearServicio($barberia, 20000)->id);

        Livewire::test(PanelCliente::class, ['token' => Cliente::sole()->tokenPanel()])
            ->call('abrirReagendar', $cita->id)
            ->set('reFecha', '2026-11-06')
            ->set('reHora', '09:30')
            ->call('guardarReagendar')
            ->assertHasNoErrors();

        $cita->refresh();
        $this->assertSame('2026-11-06', $cita->fecha->toDateString());
        $this->assertSame('cliente', $cita->reagendada_por);
        $this->assertNull($cita->reagendo_respuesta);
    }

    public function test_el_cliente_no_cambia_la_hora_fuera_de_tiempo(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $cita = $this->citaPagada($barberia, $this->crearServicio($barberia, 20000)->id);
        Carbon::setTestNow(Carbon::parse('2026-11-04 08:00:00'));

        Livewire::test(PanelCliente::class, ['token' => Cliente::sole()->tokenPanel()])
            ->call('abrirReagendar', $cita->id)
            ->assertForbidden();
    }

    public function test_un_cliente_no_ve_ni_mueve_citas_de_otro(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $cita = $this->citaPagada($barberia, $this->crearServicio($barberia, 20000)->id);
        $otro = $this->crearCliente($barberia, nombre: 'Pedro');

        Livewire::test(PanelCliente::class, ['token' => $otro->tokenPanel()])
            ->call('cancelar', $cita->id)
            ->assertNotFound();
        $this->assertSame('confirmada', $cita->fresh()->estado);
    }

    public function test_recuperar_el_enlace_lo_envia_al_correo(): void
    {
        Mail::fake();
        $barberia = $this->barberiaConReservas();
        $cliente = $this->crearCliente($barberia);
        $cliente->update(['email' => 'cliente@example.com']);

        Livewire::test(ReservaEnLinea::class, ['slug' => $barberia->slug])
            ->set('recuperarTelefono', $cliente->telefono)
            ->call('enviarEnlace')
            ->assertSee('te enviamos');

        $this->assertNotNull($cliente->fresh()->token);
    }
}
