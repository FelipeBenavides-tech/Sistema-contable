<?php

namespace Tests\Feature;

use App\Livewire\Admin\PanelAdmin;
use App\Livewire\GestionAgenda;
use App\Livewire\GestionBarberos;
use App\Models\Barberia;
use App\Models\Barbero;
use App\Models\Cita;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class TipoNegocioTest extends TestCase
{
    use CreaDatosDePrueba, RefreshDatabase;

    private function crearNegocio(string $tipo, string $nivel = 'pro'): Barberia
    {
        $negocio = $this->crearBarberia('Negocio ' . $tipo, $nivel);
        $negocio->update(['tipo_negocio' => $tipo]);

        return $negocio->fresh();
    }

    public function test_el_admin_registra_un_salon_de_belleza(): void
    {
        Livewire::actingAs($this->crearAdmin())->test(PanelAdmin::class)
            ->call('nuevo')
            ->set('tipoNegocio', 'salon')
            ->set('nombre', 'Salón Bella Rosa')
            ->set('email', 'salon@example.com')
            ->set('password', 'clave12345')
            ->set('valorPago', 0)
            ->call('guardar')
            ->assertHasNoErrors();

        $salon = Barberia::where('nombre', 'Salón Bella Rosa')->firstOrFail();
        $this->assertSame('salon', $salon->tipo_negocio);
        $this->assertTrue($salon->es_salon);
    }

    public function test_solo_agendamiento_queda_en_nivel_pro_y_sin_plan_por_uso(): void
    {
        $panel = Livewire::actingAs($this->crearAdmin())->test(PanelAdmin::class)
            ->call('nuevo')
            ->set('plan', 'porcentaje')
            ->set('tipoNegocio', 'agenda')
            ->assertSet('nivel', 'pro')
            ->assertSet('plan', 'mensual');

        $panel->set('plan', 'porcentaje')
            ->set('nombre', 'Consultorio')
            ->set('email', 'agenda@example.com')
            ->set('password', 'clave12345')
            ->call('guardar')
            ->assertHasErrors('plan');

        $panel->set('plan', 'mensual')->call('guardar')->assertHasNoErrors();

        $negocio = Barberia::where('nombre', 'Consultorio')->firstOrFail();
        $this->assertTrue($negocio->solo_agenda);
        $this->assertSame('pro', $negocio->nivel);
    }

    public function test_el_salon_usa_profesionales_y_paleta_pastel(): void
    {
        $usuario = $this->crearUsuario($this->crearNegocio('salon'));

        $this->actingAs($usuario)->get('/profesionales')
            ->assertOk()
            ->assertSee('data-tema="salon"', false)
            ->assertSee('Agregar profesional')
            ->assertSee('Mi salón')
            ->assertDontSee('Nuevo barbero');
    }

    public function test_la_barberia_sigue_igual(): void
    {
        $usuario = $this->crearUsuario($this->crearBarberia());

        $this->actingAs($usuario)->get('/barberos')
            ->assertOk()
            ->assertSee('data-tema="clasico"', false)
            ->assertSee('Nuevo barbero')
            ->assertSee('Mi barbería');
    }

    public function test_el_salon_agrega_profesionales_con_especialidad(): void
    {
        $salon = $this->crearNegocio('salon');

        $componente = Livewire::actingAs($this->crearUsuario($salon))->test(GestionBarberos::class)
            ->call('nuevo')
            ->set('nombre', 'Camila Torres')
            ->call('guardar')
            ->assertHasErrors('especialidad');

        $componente->set('especialidad', 'Manicurista')->call('guardar')->assertHasNoErrors();

        $componente->call('nuevo')
            ->set('nombre', 'Daniela Pardo')
            ->set('especialidad', 'otra')
            ->set('especialidadOtra', 'maquilladora')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('Manicurista', Barbero::where('nombre', 'Camila Torres')->value('especialidad'));
        $this->assertSame('Maquilladora', Barbero::where('nombre', 'Daniela Pardo')->value('especialidad'));
    }

    public function test_la_barberia_no_pide_especialidad(): void
    {
        $barberia = $this->crearBarberia();

        Livewire::actingAs($this->crearUsuario($barberia))->test(GestionBarberos::class)
            ->call('nuevo')
            ->set('nombre', 'Carlos')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertNull(Barbero::where('nombre', 'Carlos')->value('especialidad'));
    }

    public function test_solo_agendamiento_no_tiene_caja_y_va_a_la_agenda(): void
    {
        $usuario = $this->crearUsuario($this->crearNegocio('agenda'));

        foreach (['/pos', '/ventas', '/inventario', '/gastos', '/reportes', '/cartera', '/membresias'] as $ruta) {
            $this->actingAs($usuario)->get($ruta)->assertRedirect(route('agenda'));
        }

        $this->actingAs($usuario)->get('/agenda')->assertOk()->assertDontSee('Caja del día');
        $this->actingAs($usuario)->get('/clientes')->assertOk();
        $this->actingAs($usuario)->get('/servicios')->assertOk();
    }

    public function test_solo_agendamiento_marca_la_cita_atendida_sin_caja(): void
    {
        $negocio = $this->crearNegocio('agenda');
        $profesional = $this->crearBarbero($negocio);
        $servicio = $this->crearServicio($negocio);
        $cliente = $this->crearCliente($negocio);

        $fecha = now()->next('Monday')->toDateString();
        $cita = Cita::agendar($negocio->id, $cliente, $profesional, [$servicio->id], ['fecha' => $fecha, 'hora_inicio' => '10:00']);

        Livewire::actingAs($this->crearUsuario($negocio))->test(GestionAgenda::class)
            ->call('cambiarEstado', $cita->id, 'atendida');

        $this->assertSame('atendida', $cita->fresh()->estado);
    }

    public function test_una_barberia_no_marca_atendida_sin_cobrar(): void
    {
        $barberia = $this->crearBarberia('Barbería Pro', 'pro');
        $cita = Cita::agendar($barberia->id, $this->crearCliente($barberia), $this->crearBarbero($barberia),
            [$this->crearServicio($barberia)->id], ['fecha' => now()->next('Monday')->toDateString(), 'hora_inicio' => '10:00']);

        Livewire::actingAs($this->crearUsuario($barberia))->test(GestionAgenda::class)
            ->call('cambiarEstado', $cita->id, 'atendida');

        $this->assertSame('confirmada', $cita->fresh()->estado);
    }

    public function test_la_pagina_de_reservas_del_salon_habla_de_profesionales(): void
    {
        $salon = $this->crearNegocio('salon');
        $salon->update(['slug' => 'bella', 'reservas_activas' => true]);
        Barbero::create(['barberia_id' => $salon->id, 'nombre' => 'Laura', 'especialidad' => 'Estilista', 'comision_porcentaje' => 40, 'activo' => true]);
        Servicio::create(['barberia_id' => $salon->id, 'nombre' => 'Cepillado', 'precio' => 30000, 'categoria' => 'servicio', 'activo' => true, 'duracion_minutos' => 45]);

        $this->get('/reservar/bella')
            ->assertOk()
            ->assertSee('data-tema="salon"', false)
            ->assertDontSee('barbería');
    }
}
