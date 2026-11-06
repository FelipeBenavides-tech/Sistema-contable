<?php

namespace Tests\Feature;

use App\Livewire\GestionAgenda;
use App\Livewire\GestionBarberos;
use App\Livewire\MiAgenda;
use App\Livewire\PosVenta;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use CreaDatosDePrueba, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Un martes a las 8 a. m.: el horario general es de 9:00 a 19:00
        Carbon::setTestNow(Carbon::parse('2026-11-03 08:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_la_agenda_es_solo_del_plan_pro(): void
    {
        $base = $this->crearBarberia('Base');
        $pro  = $this->crearBarberia('Pro', 'pro');

        $this->actingAs($this->crearUsuario($base))->get('/agenda')->assertForbidden()->assertSee('plan Pro');
        $this->actingAs($this->crearUsuario($pro))->get('/agenda')->assertOk();
    }

    public function test_agendar_una_cita_y_no_permitir_cruces(): void
    {
        $barberia = $this->crearBarberia('Pro', 'pro');
        $usuario  = $this->crearUsuario($barberia);
        $barbero  = $this->crearBarbero($barberia);
        $corte    = $this->crearServicio($barberia, 20000);
        $corte->update(['duracion_minutos' => 45]);

        Livewire::actingAs($usuario)->test(GestionAgenda::class)
            ->call('nuevaCita', $barbero->id)
            ->set('citaNombre', 'Andrés Gómez')
            ->set('citaTelefono', '300 111 2233')
            ->set('citaServicios', [$corte->id])
            ->set('citaHora', '10:00')
            ->call('guardarCita')
            ->assertHasNoErrors();

        $cita = Cita::with('servicios')->sole();
        $this->assertSame('10:00', $cita->hora_inicio);
        $this->assertSame('10:45', $cita->hora_fin);
        $this->assertEquals(20000, $cita->total);
        $this->assertSame('3001112233', $cita->cliente->telefono);

        // 10:30 se cruza con la cita de 10:00 a 10:45
        $libres = Cita::horasDisponibles($barbero, '2026-11-03', 30);
        $this->assertNotContains('10:30', $libres);
        $this->assertContains('10:45', $libres);

        Livewire::actingAs($usuario)->test(GestionAgenda::class)
            ->call('nuevaCita', $barbero->id)
            ->set('citaNombre', 'Otro')
            ->set('citaServicios', [$corte->id])
            ->set('citaHora', '10:30')
            ->call('guardarCita')
            ->assertHasErrors('citaHora');

        $this->assertSame(1, Cita::count());
        // El mismo celular no crea otro cliente
        $this->assertSame(1, Cliente::where('telefono', '3001112233')->count());
    }

    public function test_no_se_agenda_fuera_del_horario_del_barbero(): void
    {
        $barberia = $this->crearBarberia('Pro', 'pro');
        $barbero  = $this->crearBarbero($barberia);
        $barbero->horarios()->create(['dia_semana' => 2, 'hora_inicio' => '14:00', 'hora_fin' => '18:00']);

        $this->assertSame([], Cita::horasDisponibles($barbero, '2026-11-04', 30)); // miércoles: no trabaja
        $libres = Cita::horasDisponibles($barbero, '2026-11-03', 30);
        $this->assertSame('14:00', $libres[0]);
        $this->assertSame('17:30', end($libres));
    }

    public function test_una_cancelada_libera_la_hora(): void
    {
        $barberia = $this->crearBarberia('Pro', 'pro');
        $usuario  = $this->crearUsuario($barberia);
        $barbero  = $this->crearBarbero($barberia);
        $corte    = $this->crearServicio($barberia);
        $cliente  = $this->crearCliente($barberia);

        $cita = Cita::agendar($barberia->id, $cliente, $barbero, [$corte->id], ['fecha' => '2026-11-03', 'hora_inicio' => '11:00']);
        $this->assertNotContains('11:00', Cita::horasDisponibles($barbero, '2026-11-03', 30));

        Livewire::actingAs($usuario)->test(GestionAgenda::class)->call('cambiarEstado', $cita->id, 'cancelada');

        $this->assertSame('cancelada', $cita->fresh()->estado);
        $this->assertContains('11:00', Cita::horasDisponibles($barbero, '2026-11-03', 30));
    }

    public function test_cobrar_la_cita_en_la_caja(): void
    {
        $barberia = $this->crearBarberia('Pro', 'pro');
        $usuario  = $this->crearUsuario($barberia);
        $barbero  = $this->crearBarbero($barberia, 50);
        $corte    = $this->crearServicio($barberia, 20000);
        $cliente  = $this->crearCliente($barberia);
        $cita = Cita::agendar($barberia->id, $cliente, $barbero, [$corte->id], ['fecha' => '2026-11-03', 'hora_inicio' => '09:00']);

        Livewire::withQueryParams(['cita' => $cita->id])
            ->actingAs($usuario)
            ->test(PosVenta::class)
            ->assertSet('barberoId', $barbero->id)
            ->assertSet('clienteId', $cliente->id)
            ->call('cobrar')
            ->assertHasNoErrors();

        $venta = Venta::sole();
        $this->assertEquals(20000, $venta->total);
        $this->assertEquals(10000, $venta->comision_barbero);
        $this->assertSame($cliente->id, $venta->cliente_id);
        $this->assertSame('atendida', $cita->fresh()->estado);
        $this->assertSame($venta->id, $cita->fresh()->venta_id);
    }

    public function test_el_barbero_solo_entra_a_su_agenda(): void
    {
        $barberia = $this->crearBarberia('Pro', 'pro');
        $barbero  = $this->crearBarbero($barberia);
        $usuario  = $this->crearUsuarioBarbero($barberia, $barbero);

        $this->actingAs($usuario)->get('/mi-agenda')->assertOk();

        foreach (['/pos', '/ventas', '/clientes', '/cartera', '/gastos', '/reportes', '/barberos', '/agenda', '/mi-plan'] as $ruta) {
            $this->actingAs($usuario)->get($ruta)->assertRedirect(route('mi-agenda'));
        }
    }

    public function test_el_barbero_solo_ve_sus_citas_y_agenda_para_si_mismo(): void
    {
        $barberia = $this->crearBarberia('Pro', 'pro');
        $yo       = $this->crearBarbero($barberia);
        $otro     = $this->crearBarbero($barberia);
        $corte    = $this->crearServicio($barberia);
        Cita::agendar($barberia->id, $this->crearCliente($barberia, nombre: 'Cliente Mío'), $yo, [$corte->id], ['fecha' => '2026-11-03', 'hora_inicio' => '09:00']);
        Cita::agendar($barberia->id, $this->crearCliente($barberia, nombre: 'Cliente Ajeno'), $otro, [$corte->id], ['fecha' => '2026-11-03', 'hora_inicio' => '09:00']);

        $usuario = $this->crearUsuarioBarbero($barberia, $yo);

        Livewire::actingAs($usuario)->test(MiAgenda::class)
            ->assertSee('Cliente Mío')
            ->assertDontSee('Cliente Ajeno')
            ->call('nuevaCita')
            ->set('citaBarberoId', $otro->id) // aunque lo intente, queda para él
            ->set('citaNombre', 'Nuevo')
            ->set('citaServicios', [$corte->id])
            ->set('citaHora', '10:00')
            ->call('guardarCita')
            ->assertHasNoErrors();

        $this->assertSame($yo->id, Cita::latest('id')->first()->barbero_id);
    }

    public function test_el_dueno_da_acceso_a_un_barbero(): void
    {
        $barberia = $this->crearBarberia('Pro', 'pro');
        $barbero  = $this->crearBarbero($barberia);

        Livewire::actingAs($this->crearUsuario($barberia))->test(GestionBarberos::class)
            ->call('abrirAcceso', $barbero->id)
            ->set('accesoEmail', 'barbero@example.com')
            ->set('accesoPassword', 'clave-segura')
            ->call('guardarAcceso')
            ->assertHasNoErrors();

        $user = User::where('email', 'barbero@example.com')->sole();
        $this->assertTrue($user->esBarbero());
        $this->assertSame($barbero->id, $user->barbero_id);
        $this->assertSame($barberia->id, $user->barberia_id);
    }

    public function test_en_plan_base_no_se_crean_usuarios_de_barbero(): void
    {
        $barberia = $this->crearBarberia();
        $barbero  = $this->crearBarbero($barberia);

        Livewire::actingAs($this->crearUsuario($barberia))->test(GestionBarberos::class)
            ->call('abrirAcceso', $barbero->id)
            ->set('accesoBarberoId', $barbero->id)
            ->set('accesoEmail', 'barbero@example.com')
            ->set('accesoPassword', 'clave-segura')
            ->call('guardarAcceso');

        $this->assertDatabaseMissing('users', ['email' => 'barbero@example.com']);
    }

    public function test_un_barbero_de_una_barberia_base_ve_el_aviso_de_pro(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuarioBarbero($barberia, $this->crearBarbero($barberia));

        $this->actingAs($usuario)->get('/mi-agenda')->assertForbidden();
    }
}
