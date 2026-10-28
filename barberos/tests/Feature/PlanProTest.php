<?php

namespace Tests\Feature;

use App\Livewire\Admin\PanelAdmin;
use App\Models\Barberia;
use App\Models\Venta;
use App\Livewire\PosVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class PlanProTest extends TestCase
{
    use CreaDatosDePrueba, RefreshDatabase;

    public function test_el_plan_pro_cuesta_mas_que_el_base(): void
    {
        $base = $this->crearBarberia('Base');
        $pro  = $this->crearBarberia('Pro', 'pro');

        $this->assertEquals(config('suscripcion.planes.mensual.precio'), $base->precio_plan);
        $this->assertEquals(config('suscripcion.planes.mensual.precio_pro'), $pro->precio_plan);
        $this->assertSame('Mensual', $base->nombre_plan);
        $this->assertSame('Mensual Pro', $pro->nombre_plan);
    }

    public function test_el_admin_crea_una_barberia_pro_con_su_precio(): void
    {
        Livewire::actingAs($this->crearAdmin())
            ->test(PanelAdmin::class)
            ->call('nuevo')
            ->set('nombre', 'Barbería Pro')
            ->set('nivel', 'pro')
            ->assertSet('valorPago', (float) config('suscripcion.planes.mensual.precio_pro'))
            ->set('email', 'pro@example.com')
            ->set('password', 'password123')
            ->call('guardar')
            ->assertHasNoErrors();

        $barberia = Barberia::where('nombre', 'Barbería Pro')->sole();
        $this->assertTrue($barberia->es_pro);
        $this->assertSame('dueno', $barberia->users()->sole()->rol);
    }

    public function test_el_admin_pasa_una_barberia_a_pro(): void
    {
        $barberia = $this->crearBarberia();

        Livewire::actingAs($this->crearAdmin())
            ->test(PanelAdmin::class)
            ->call('editar', $barberia->id)
            ->set('nivel', 'pro')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertTrue($barberia->fresh()->es_pro);
    }

    public function test_el_plan_por_uso_pro_cobra_su_porcentaje(): void
    {
        $barberia = $this->crearBarberia('Pro', 'pro');
        $barberia->update(['plan' => 'porcentaje', 'saldo' => 50000, 'fecha_vencimiento' => null]);
        $servicio = $this->crearServicio($barberia, 20000);

        Livewire::actingAs($this->crearUsuario($barberia))
            ->test(PosVenta::class)
            ->call('agregarServicio', $servicio->id)
            ->set('barberoId', $this->crearBarbero($barberia)->id)
            ->call('cobrar')
            ->assertHasNoErrors();

        $esperado = round(20000 * config('suscripcion.porcentaje.valor_pro') / 100, 2);
        $this->assertEquals($esperado, Venta::sole()->cargo_plataforma);
    }

    public function test_el_menu_marca_la_agenda_como_pro_en_el_plan_base(): void
    {
        $barberia = $this->crearBarberia();

        $this->actingAs($this->crearUsuario($barberia))->get('/pos')->assertOk()->assertSee('Agenda de citas');
    }

    public function test_las_pantallas_nuevas_cargan(): void
    {
        $barberia = $this->crearBarberia('Pro', 'pro');
        $usuario  = $this->crearUsuario($barberia);
        $this->crearBarbero($barberia);
        $this->crearCliente($barberia, true);

        foreach (['/clientes', '/cartera', '/agenda', '/mi-plan', '/barberos', '/servicios'] as $ruta) {
            $this->actingAs($usuario)->get($ruta)->assertOk();
        }
    }
}
