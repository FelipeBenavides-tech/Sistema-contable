<?php

namespace Tests\Feature;

use App\Livewire\GestionCartera;
use App\Livewire\HistorialVentas;
use App\Livewire\PosVenta;
use App\Models\Barberia;
use App\Models\Barbero;
use App\Models\Cliente;
use App\Models\PagoCartera;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class CarteraTest extends TestCase
{
    use CreaDatosDePrueba, RefreshDatabase;

    private function fiar(User $usuario, Cliente $cliente, Servicio $servicio, Barbero $barbero, int $cantidad = 1)
    {
        $pos = Livewire::actingAs($usuario)->test(PosVenta::class)->call('seleccionarCliente', $cliente->id);
        for ($i = 0; $i < $cantidad; $i++) {
            $pos->call('agregarServicio', $servicio->id);
        }

        return $pos->set('barberoId', $barbero->id)->set('metodoPago', 'fiado')->call('cobrar');
    }

    public function test_un_servicio_fiado_queda_en_la_cartera_y_no_entra_a_caja(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $cliente  = $this->crearCliente($barberia, permiteFiado: true);

        $this->fiar($usuario, $cliente, $this->crearServicio($barberia, 20000), $this->crearBarbero($barberia))
            ->assertHasNoErrors()
            ->assertSet('ventaExitosa', true);

        $venta = Venta::sole();
        $this->assertSame('fiado', $venta->metodo_pago);
        $this->assertEquals(20000, $venta->saldo_pendiente);
        $this->assertEquals(0, $venta->monto_efectivo);
        $this->assertSame($cliente->id, $venta->cliente_id);
        $this->assertEquals(20000, $cliente->fresh()->deuda);
        $this->assertSame(1, $cliente->fresh()->servicios_fiados);

        $resumen = Venta::resumenDia(null, $barberia->id);
        $this->assertEquals(0, $resumen['total_efectivo']);
        $this->assertEquals(20000, $resumen['total_fiado']);
    }

    public function test_no_se_fia_sin_cliente_ni_a_un_cliente_sin_cartera(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $servicio = $this->crearServicio($barberia);
        $barbero  = $this->crearBarbero($barberia);

        Livewire::actingAs($usuario)->test(PosVenta::class)
            ->call('agregarServicio', $servicio->id)
            ->set('barberoId', $barbero->id)
            ->set('metodoPago', 'fiado')
            ->call('cobrar')
            ->assertHasErrors('carrito');

        $this->fiar($usuario, $this->crearCliente($barberia, permiteFiado: false), $servicio, $barbero)
            ->assertHasErrors('carrito');

        $this->assertSame(0, Venta::count());
    }

    public function test_al_cuarto_servicio_no_se_fia_mas_hasta_pagar_todo(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $cliente  = $this->crearCliente($barberia, permiteFiado: true);
        $servicio = $this->crearServicio($barberia, 20000);
        $barbero  = $this->crearBarbero($barberia);

        // 3 visitas + 1 = 4 servicios: llega al límite
        $this->fiar($usuario, $cliente, $servicio, $barbero, 3)->assertHasNoErrors();
        $this->assertFalse($cliente->fresh()->fiado_bloqueado);
        $this->fiar($usuario, $cliente, $servicio, $barbero)->assertHasNoErrors();
        $this->assertTrue($cliente->fresh()->fiado_bloqueado);

        // El quinto ya no se fía
        $this->fiar($usuario, $cliente, $servicio, $barbero)->assertHasErrors('carrito');
        $this->assertSame(2, Venta::count());

        // Un abono no lo desbloquea: debe pagar la totalidad
        PagoCartera::registrar($cliente->fresh(), 60000, 'efectivo');
        $this->assertEquals(20000, $cliente->fresh()->deuda);
        $this->assertTrue($cliente->fresh()->fiado_bloqueado);
        $this->fiar($usuario, $cliente, $servicio, $barbero)->assertHasErrors('carrito');

        // Paga el resto y ya se le puede fiar de nuevo
        PagoCartera::registrar($cliente->fresh(), 20000, 'nequi');
        $this->assertEquals(0, $cliente->fresh()->deuda);
        $this->assertFalse($cliente->fresh()->fiado_bloqueado);
        $this->fiar($usuario, $cliente, $servicio, $barbero)->assertHasNoErrors();
    }

    public function test_no_se_puede_pasar_del_limite_en_una_sola_venta(): void
    {
        $barberia = $this->crearBarberia();
        $cliente  = $this->crearCliente($barberia, permiteFiado: true);

        $this->fiar($this->crearUsuario($barberia), $cliente, $this->crearServicio($barberia), $this->crearBarbero($barberia), 5)
            ->assertHasErrors('carrito');

        $this->assertSame(0, Venta::count());
    }

    public function test_el_pago_de_cartera_entra_a_la_caja_del_dia(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $cliente  = $this->crearCliente($barberia, permiteFiado: true);
        $this->fiar($usuario, $cliente, $this->crearServicio($barberia, 25000), $this->crearBarbero($barberia));

        Livewire::actingAs($usuario)->test(GestionCartera::class)
            ->call('abrirPago', $cliente->id)
            ->assertSet('pagoValor', 25000.0)
            ->set('pagoMetodo', 'nequi')
            ->call('registrarPago')
            ->assertHasNoErrors();

        $this->assertEquals(0, $cliente->fresh()->deuda);
        $this->assertEquals(0, Venta::sole()->saldo_pendiente);

        $resumen = Venta::resumenDia(null, $barberia->id);
        $this->assertEquals(25000, $resumen['total_nequi']);
        $this->assertEquals(25000, $resumen['cobros_cartera']);
    }

    public function test_no_se_puede_pagar_mas_de_lo_que_debe(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $cliente  = $this->crearCliente($barberia, permiteFiado: true);
        $this->fiar($usuario, $cliente, $this->crearServicio($barberia, 20000), $this->crearBarbero($barberia));

        Livewire::actingAs($usuario)->test(GestionCartera::class)
            ->call('abrirPago', $cliente->id)
            ->set('pagoValor', 50000)
            ->call('registrarPago')
            ->assertHasErrors('pagoValor');

        $this->assertSame(0, PagoCartera::count());
    }

    public function test_el_limite_se_puede_cambiar(): void
    {
        $barberia = $this->crearBarberia();

        Livewire::actingAs($this->crearUsuario($barberia))->test(GestionCartera::class)
            ->set('limite', 6)
            ->call('guardarLimite')
            ->assertHasNoErrors();

        $this->assertSame(6, $barberia->fresh()->limite_fiado);
    }

    public function test_una_venta_fiada_no_se_edita_ni_se_elimina_con_abonos(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $cliente  = $this->crearCliente($barberia, permiteFiado: true);
        $this->fiar($usuario, $cliente, $this->crearServicio($barberia, 20000), $this->crearBarbero($barberia));
        $venta = Venta::sole();

        Livewire::actingAs($usuario)->test(HistorialVentas::class)
            ->call('confirmarAccion', $venta->id, 'editar')
            ->assertSet('mostrarModal', false);

        PagoCartera::registrar($cliente, 5000, 'efectivo');

        Livewire::actingAs($usuario)->test(HistorialVentas::class)
            ->call('confirmarAccion', $venta->id, 'eliminar')
            ->assertSet('mostrarModal', false);
    }

    public function test_la_cartera_no_muestra_clientes_de_otra_barberia(): void
    {
        $mia  = $this->crearBarberia('Mía');
        $otra = $this->crearBarberia('Otra');
        $this->crearCliente($mia, true, 'Pedro Mío');
        $this->crearCliente($otra, true, 'Pedro Ajeno');

        Livewire::actingAs($this->crearUsuario($mia))->test(GestionCartera::class)
            ->assertSee('Pedro Mío')
            ->assertDontSee('Pedro Ajeno');
    }
}
