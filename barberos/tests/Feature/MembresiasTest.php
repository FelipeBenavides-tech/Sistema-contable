<?php

namespace Tests\Feature;

use App\Livewire\GestionMembresias;
use App\Livewire\HistorialVentas;
use App\Livewire\PosVenta;
use App\Models\Barberia;
use App\Models\Membresia;
use App\Models\Venta;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class MembresiasTest extends TestCase
{
    use CreaDatosDePrueba, RefreshDatabase;

    private function venderMembresia(Barberia $barberia, $usuario, int $planId, string $nombre = 'Juan Pérez'): Membresia
    {
        Livewire::actingAs($usuario)
            ->test(GestionMembresias::class)
            ->call('nuevaVenta')
            ->set('ventaPlanId', $planId)
            ->set('nuevoNombre', $nombre)
            ->set('nuevoTelefono', '3001234567')
            ->call('venderMembresia')
            ->assertHasNoErrors();

        return Membresia::where('barberia_id', $barberia->id)->latest('id')->first();
    }

    private function atenderConMembresia($usuario, Membresia $membresia, int $servicioId, int $barberoId)
    {
        return Livewire::actingAs($usuario)
            ->test(PosVenta::class)
            ->call('seleccionarMembresia', $membresia->id)
            ->call('agregarServicio', $servicioId)
            ->set('barberoId', $barberoId)
            ->call('cobrar');
    }

    public function test_vender_una_membresia_registra_el_ingreso_sin_comision(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $plan     = $this->crearPlan($barberia, precio: 80000, visitas: 5, dias: 30);

        $membresia = $this->venderMembresia($barberia, $usuario, $plan->id);

        $this->assertSame('Juan Pérez', $membresia->cliente->nombre);
        $this->assertSame(5, $membresia->visitas_total);
        $this->assertSame(now()->addDays(29)->toDateString(), $membresia->fecha_vencimiento->toDateString());
        $this->assertTrue($membresia->vigente);

        $venta = Venta::with('items')->sole();
        $this->assertSame($venta->id, $membresia->venta_id);
        $this->assertEquals(80000, $venta->total);
        $this->assertEquals(80000, $venta->monto_efectivo);
        $this->assertEquals(0, $venta->comision_barbero);
        $this->assertTrue($venta->items->first()->es_membresia);
        $this->assertEquals(80000, Venta::resumenDia(null, $barberia->id)['total']);
    }

    public function test_atender_con_membresia_descuenta_una_visita_y_no_cobra_el_servicio(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $barbero  = $this->crearBarbero($barberia, 50);
        $corte    = $this->crearServicio($barberia, 20000);
        $membresia = $this->venderMembresia($barberia, $usuario, $this->crearPlan($barberia)->id);

        $this->atenderConMembresia($usuario, $membresia, $corte->id, $barbero->id)
            ->assertHasNoErrors()
            ->assertSet('ventaExitosa', true)
            ->assertSet('membresiaId', 0);

        $this->assertSame(1, $membresia->fresh()->visitas_usadas);

        $venta = Venta::with('items')->latest('id')->first();
        $this->assertEquals(0, $venta->total);
        $this->assertEquals(0, $venta->monto_efectivo);
        // Comisión sobre el precio normal del corte
        $this->assertEquals(10000, $venta->comision_barbero);
        $this->assertEquals(-10000, $venta->ganancia_local);

        $item = $venta->items->sole();
        $this->assertTrue($item->cubierto_membresia);
        $this->assertSame($membresia->id, $item->membresia_id);
        $this->assertEquals(20000, $item->precio);
        $this->assertEquals(0, $item->subtotal);
    }

    public function test_lo_que_no_cubre_la_membresia_se_cobra_normal(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $barbero  = $this->crearBarbero($barberia, 50);
        $corte    = $this->crearServicio($barberia, 20000);
        $barba    = $this->crearServicio($barberia, 10000);
        $cera     = $this->crearProducto($barberia, precio: 15000);
        $membresia = $this->venderMembresia($barberia, $usuario, $this->crearPlan($barberia)->id);

        Livewire::actingAs($usuario)
            ->test(PosVenta::class)
            ->call('agregarServicio', $corte->id)
            ->call('seleccionarMembresia', $membresia->id) // cubre el corte
            ->call('agregarServicio', $barba->id)
            ->call('agregarProductoInventario', $cera->id)
            ->call('alternarMembresia', 'inv_' . $cera->id) // un producto nunca se cubre
            ->set('barberoId', $barbero->id)
            ->assertSet('carrito.srv_' . $corte->id . '.cubierto', true)
            ->assertSet('carrito.srv_' . $barba->id . '.cubierto', false)
            ->call('cobrar')
            ->assertHasNoErrors();

        $venta = Venta::latest('id')->first();
        $this->assertEquals(25000, $venta->total);
        $this->assertEquals(15000, $venta->comision_barbero); // 50% de 20.000 + 10.000
        $this->assertSame(1, $membresia->fresh()->visitas_usadas);
    }

    public function test_la_membresia_se_cierra_al_agotar_las_visitas(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $barbero  = $this->crearBarbero($barberia);
        $corte    = $this->crearServicio($barberia);
        $membresia = $this->venderMembresia($barberia, $usuario, $this->crearPlan($barberia, visitas: 2)->id);

        $this->atenderConMembresia($usuario, $membresia, $corte->id, $barbero->id)->assertHasNoErrors();
        $this->atenderConMembresia($usuario, $membresia, $corte->id, $barbero->id)->assertHasNoErrors();

        $this->assertSame('agotada', $membresia->fresh()->estado);

        $this->expectException(ModelNotFoundException::class);
        $this->atenderConMembresia($usuario, $membresia, $corte->id, $barbero->id);
    }

    public function test_no_se_pueden_usar_mas_visitas_de_las_que_quedan(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $barbero  = $this->crearBarbero($barberia);
        $corte    = $this->crearServicio($barberia);
        $membresia = $this->venderMembresia($barberia, $usuario, $this->crearPlan($barberia, visitas: 1)->id);

        Livewire::actingAs($usuario)
            ->test(PosVenta::class)
            ->call('seleccionarMembresia', $membresia->id)
            ->call('agregarServicio', $corte->id)
            ->call('agregarServicio', $corte->id) // 2 cortes cubiertos, solo queda 1 visita
            ->set('barberoId', $barbero->id)
            ->call('cobrar')
            ->assertHasErrors('carrito');

        $this->assertSame(0, $membresia->fresh()->visitas_usadas);
        $this->assertSame(1, Venta::count()); // solo la venta de la membresía
    }

    public function test_la_membresia_vencida_no_se_puede_usar(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $membresia = $this->venderMembresia($barberia, $usuario, $this->crearPlan($barberia)->id);
        $membresia->update(['fecha_vencimiento' => now()->subDay()->toDateString()]);

        $this->assertSame('vencida', $membresia->fresh()->estado);

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($usuario)->test(PosVenta::class)->call('seleccionarMembresia', $membresia->id);
    }

    public function test_no_se_puede_usar_la_membresia_de_otra_barberia(): void
    {
        $otra     = $this->crearBarberia('Otra');
        $ajena    = $this->venderMembresia($otra, $this->crearUsuario($otra), $this->crearPlan($otra)->id);
        $mia      = $this->crearBarberia('Mía');

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($this->crearUsuario($mia))->test(PosVenta::class)->call('seleccionarMembresia', $ajena->id);
    }

    public function test_el_buscador_de_la_caja_solo_muestra_clientes_de_la_barberia(): void
    {
        $mia  = $this->crearBarberia('Mía');
        $otra = $this->crearBarberia('Otra');
        $this->venderMembresia($mia, $this->crearUsuario($mia), $this->crearPlan($mia)->id, 'Carlos Mío');
        $this->venderMembresia($otra, $this->crearUsuario($otra), $this->crearPlan($otra)->id, 'Carlos Ajeno');

        Livewire::actingAs($this->crearUsuario($mia))
            ->test(PosVenta::class)
            ->set('buscarCliente', 'Carlos')
            ->assertSee('Carlos Mío')
            ->assertDontSee('Carlos Ajeno');
    }

    public function test_eliminar_la_venta_de_un_servicio_devuelve_la_visita(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $barbero  = $this->crearBarbero($barberia);
        $corte    = $this->crearServicio($barberia);
        $membresia = $this->venderMembresia($barberia, $usuario, $this->crearPlan($barberia)->id);
        $this->atenderConMembresia($usuario, $membresia, $corte->id, $barbero->id);

        Livewire::actingAs($usuario)
            ->test(HistorialVentas::class)
            ->call('confirmarAccion', Venta::latest('id')->first()->id, 'eliminar')
            ->set('motivo', 'Se registro por error')
            ->set('passwordConfirm', 'password')
            ->call('verificarPassword')
            ->assertHasNoErrors();

        $this->assertSame(0, $membresia->fresh()->visitas_usadas);
    }

    public function test_eliminar_la_venta_de_la_membresia_la_anula(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $membresia = $this->venderMembresia($barberia, $usuario, $this->crearPlan($barberia)->id);

        Livewire::actingAs($usuario)
            ->test(HistorialVentas::class)
            ->call('confirmarAccion', $membresia->venta_id, 'eliminar')
            ->set('motivo', 'El cliente se arrepintio')
            ->set('passwordConfirm', 'password')
            ->call('verificarPassword')
            ->assertHasNoErrors();

        $this->assertSame('anulada', $membresia->fresh()->estado);
        $this->assertEquals(0, Venta::resumenDia(null, $barberia->id)['total']);
    }

    public function test_editar_una_venta_con_membresia_ajusta_las_visitas(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $barbero  = $this->crearBarbero($barberia, 50);
        $corte    = $this->crearServicio($barberia, 20000);
        $membresia = $this->venderMembresia($barberia, $usuario, $this->crearPlan($barberia)->id);
        $this->atenderConMembresia($usuario, $membresia, $corte->id, $barbero->id);
        $venta = Venta::latest('id')->first();

        $historial = Livewire::actingAs($usuario)
            ->test(HistorialVentas::class)
            ->call('confirmarAccion', $venta->id, 'editar')
            ->set('motivo', 'Fueron dos cortes')
            ->set('passwordConfirm', 'password')
            ->call('verificarPassword')
            ->call('aumentarCantidad', 0)
            ->assertSet('editItems.0.subtotal', 0)
            ->call('guardarEdicion')
            ->assertHasNoErrors();

        $venta->refresh();
        $this->assertSame(2, $membresia->fresh()->visitas_usadas);
        $this->assertEquals(0, $venta->total);
        $this->assertEquals(20000, $venta->comision_barbero);
    }

    public function test_los_planes_son_de_cada_barberia(): void
    {
        $mia  = $this->crearBarberia('Mía');
        $otra = $this->crearBarberia('Otra');
        $planAjeno = $this->crearPlan($otra);

        Livewire::actingAs($this->crearUsuario($mia))
            ->test(GestionMembresias::class)
            ->call('nuevoPlan')
            ->set('planNombre', 'Mensual 4 cortes')
            ->set('planPrecio', 60000)
            ->set('planVisitas', 4)
            ->set('planDias', 30)
            ->call('guardarPlan')
            ->assertHasNoErrors()
            ->set('tab', 'planes')
            ->assertSee('Mensual 4 cortes')
            ->assertDontSee($planAjeno->nombre);

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($this->crearUsuario($mia))->test(GestionMembresias::class)->call('editarPlan', $planAjeno->id);
    }

    public function test_la_pantalla_de_membresias_carga(): void
    {
        $barberia = $this->crearBarberia();

        $this->actingAs($this->crearUsuario($barberia))
            ->get(route('membresias'))
            ->assertOk()
            ->assertSee('Vender membresía');
    }
}
