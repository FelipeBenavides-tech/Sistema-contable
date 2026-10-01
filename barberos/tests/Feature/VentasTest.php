<?php

namespace Tests\Feature;

use App\Livewire\HistorialVentas;
use App\Livewire\PosVenta;
use App\Models\Venta;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class VentasTest extends TestCase
{
    use CreaDatosDePrueba, RefreshDatabase;

    public function test_la_comision_se_cobra_solo_sobre_servicios(): void
    {
        $barberia = $this->crearBarberia();
        $barbero  = $this->crearBarbero($barberia, 50);
        $servicio = $this->crearServicio($barberia, 20000);
        $producto = $this->crearProducto($barberia, stock: 5, precio: 10000);

        Livewire::actingAs($this->crearUsuario($barberia))
            ->test(PosVenta::class)
            ->call('agregarServicio', $servicio->id)
            ->call('agregarProductoInventario', $producto->id)
            ->set('barberoId', $barbero->id)
            ->call('cobrar')
            ->assertHasNoErrors()
            ->assertSet('ventaExitosa', true);

        $venta = Venta::with('items')->sole();
        $this->assertEquals(30000, $venta->total);
        $this->assertEquals(10000, $venta->comision_barbero); // 50% de 20.000, no de 30.000
        $this->assertEquals(20000, $venta->ganancia_local);
        $this->assertEquals(30000, $venta->monto_efectivo);
        $this->assertSame(4, $producto->fresh()->stock_actual);
        $this->assertTrue($venta->items->firstWhere('inventario_id', $producto->id)->es_producto);
    }

    public function test_venta_solo_de_productos_no_pide_barbero_ni_genera_comision(): void
    {
        $barberia = $this->crearBarberia();
        $producto = $this->crearProducto($barberia);

        Livewire::actingAs($this->crearUsuario($barberia))
            ->test(PosVenta::class)
            ->call('agregarProductoInventario', $producto->id)
            ->call('cobrar')
            ->assertHasNoErrors();

        $this->assertEquals(0, Venta::sole()->comision_barbero);
    }

    public function test_un_servicio_exige_barbero(): void
    {
        $barberia = $this->crearBarberia();
        $servicio = $this->crearServicio($barberia);

        Livewire::actingAs($this->crearUsuario($barberia))
            ->test(PosVenta::class)
            ->call('agregarServicio', $servicio->id)
            ->call('cobrar')
            ->assertHasErrors('barberoId');

        $this->assertSame(0, Venta::count());
    }

    public function test_no_se_puede_vender_un_servicio_de_otra_barberia(): void
    {
        $mia   = $this->crearBarberia('Mía');
        $otra  = $this->crearBarberia('Otra');
        $ajeno = $this->crearServicio($otra);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($this->crearUsuario($mia))
            ->test(PosVenta::class)
            ->call('agregarServicio', $ajeno->id);
    }

    public function test_no_se_puede_usar_un_barbero_de_otra_barberia(): void
    {
        $mia      = $this->crearBarberia('Mía');
        $otra     = $this->crearBarberia('Otra');
        $servicio = $this->crearServicio($mia);
        $ajeno    = $this->crearBarbero($otra);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($this->crearUsuario($mia))
            ->test(PosVenta::class)
            ->call('agregarServicio', $servicio->id)
            ->set('barberoId', $ajeno->id)
            ->call('cobrar');
    }

    public function test_si_el_stock_no_alcanza_no_se_registra_nada(): void
    {
        $barberia = $this->crearBarberia();
        $producto = $this->crearProducto($barberia, stock: 1);

        $pos = Livewire::actingAs($this->crearUsuario($barberia))
            ->test(PosVenta::class)
            ->call('agregarProductoInventario', $producto->id);

        // Otra caja vendió la última unidad mientras tanto
        $producto->update(['stock_actual' => 0]);

        $pos->call('cobrar')->assertHasErrors('carrito');

        $this->assertSame(0, Venta::count());
        $this->assertSame(0, $producto->fresh()->stock_actual);
    }

    public function test_no_se_puede_agregar_mas_producto_que_el_stock(): void
    {
        $barberia = $this->crearBarberia();
        $producto = $this->crearProducto($barberia, stock: 1);

        Livewire::actingAs($this->crearUsuario($barberia))
            ->test(PosVenta::class)
            ->call('agregarProductoInventario', $producto->id)
            ->call('agregarProductoInventario', $producto->id)
            ->assertHasErrors('carrito')
            ->assertSet('carrito.inv_' . $producto->id . '.cantidad', 1);
    }

    public function test_pago_combinado_debe_sumar_el_total(): void
    {
        $barberia = $this->crearBarberia();
        $producto = $this->crearProducto($barberia, precio: 10000);

        $pos = Livewire::actingAs($this->crearUsuario($barberia))
            ->test(PosVenta::class)
            ->call('agregarProductoInventario', $producto->id)
            ->set('metodoPago', 'combinado')
            ->set('montoEfectivo', 3000)
            ->set('montoNequi', 2000)
            ->call('cobrar')
            ->assertHasErrors('montoEfectivo');

        $this->assertSame(0, Venta::count());

        $pos->set('montoNequi', 7000)->call('cobrar')->assertHasNoErrors();

        $venta = Venta::sole();
        $this->assertEquals(3000, $venta->monto_efectivo);
        $this->assertEquals(7000, $venta->monto_nequi);
    }

    public function test_el_precio_se_toma_de_la_base_de_datos(): void
    {
        $barberia = $this->crearBarberia();
        $producto = $this->crearProducto($barberia, precio: 10000);

        Livewire::actingAs($this->crearUsuario($barberia))
            ->test(PosVenta::class)
            ->call('agregarProductoInventario', $producto->id)
            ->set('carrito.inv_' . $producto->id . '.precio', 1)
            ->set('carrito.inv_' . $producto->id . '.subtotal', 1)
            ->call('cobrar');

        $this->assertEquals(10000, Venta::sole()->total);
    }

    public function test_una_venta_eliminada_deja_de_sumar_y_el_stock_se_devuelve_una_sola_vez(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $producto = $this->crearProducto($barberia, stock: 5);

        Livewire::actingAs($usuario)
            ->test(PosVenta::class)
            ->call('agregarProductoInventario', $producto->id)
            ->call('agregarProductoInventario', $producto->id)
            ->call('cobrar');

        $venta = Venta::sole();
        $this->assertSame(3, $producto->fresh()->stock_actual);
        $this->assertEquals(20000, Venta::resumenDia(null, $barberia->id)['total_efectivo']);

        $historial = Livewire::actingAs($usuario)
            ->test(HistorialVentas::class)
            ->call('confirmarAccion', $venta->id, 'eliminar')
            ->set('motivo', 'El cliente devolvio el producto')
            ->set('passwordConfirm', 'password')
            ->call('verificarPassword')
            ->assertHasNoErrors();

        $venta->refresh();
        $this->assertTrue($venta->fue_eliminada);
        $this->assertEquals(0, $venta->monto_efectivo);
        $this->assertEquals(0, Venta::resumenDia(null, $barberia->id)['total_efectivo']);
        $this->assertSame(1, $venta->auditorias()->count());

        $historial->call('restaurarInventario', $venta->id, true);
        $historial->call('restaurarInventario', $venta->id, true);

        $this->assertSame(5, $producto->fresh()->stock_actual);
    }

    public function test_eliminar_exige_la_contrasena(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $producto = $this->crearProducto($barberia);

        Livewire::actingAs($usuario)->test(PosVenta::class)
            ->call('agregarProductoInventario', $producto->id)
            ->call('cobrar');

        Livewire::actingAs($usuario)
            ->test(HistorialVentas::class)
            ->call('confirmarAccion', Venta::sole()->id, 'eliminar')
            ->set('motivo', 'El cliente devolvio el producto')
            ->set('passwordConfirm', 'incorrecta')
            ->call('verificarPassword')
            ->assertSet('errorPassword', 'Contraseña incorrecta.');

        $this->assertFalse(Venta::sole()->fue_eliminada);
    }

    public function test_editar_una_venta_ajusta_items_stock_y_comision_al_guardar(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $barbero  = $this->crearBarbero($barberia, 40);
        $servicio = $this->crearServicio($barberia, 20000);
        $producto = $this->crearProducto($barberia, stock: 5, precio: 10000);

        Livewire::actingAs($usuario)->test(PosVenta::class)
            ->call('agregarServicio', $servicio->id)
            ->call('agregarProductoInventario', $producto->id)
            ->set('barberoId', $barbero->id)
            ->call('cobrar');

        $venta = Venta::sole();
        $this->assertSame(4, $producto->fresh()->stock_actual);

        $historial = Livewire::actingAs($usuario)
            ->test(HistorialVentas::class)
            ->call('confirmarAccion', $venta->id, 'editar')
            ->set('motivo', 'Se llevo otra unidad de cera')
            ->set('passwordConfirm', 'password')
            ->call('verificarPassword')
            ->assertSet('mostrarEdicion', true);

        $indice = collect($historial->get('editItems'))->search(fn($i) => $i['es_producto']);
        $historial->call('aumentarCantidad', $indice);

        // Mientras no se guarda, el stock no cambia
        $this->assertSame(4, $producto->fresh()->stock_actual);

        $historial->call('guardarEdicion')->assertHasNoErrors();

        $venta->refresh();
        $this->assertEquals(40000, $venta->total);
        $this->assertEquals(8000, $venta->comision_barbero); // 40% de 20.000
        $this->assertEquals(40000, $venta->monto_efectivo);
        $this->assertTrue($venta->fue_editada);
        $this->assertSame(2, $venta->items()->where('inventario_id', $producto->id)->value('cantidad'));
        $this->assertSame(3, $producto->fresh()->stock_actual);
    }

    public function test_cancelar_la_edicion_no_toca_el_stock(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $producto = $this->crearProducto($barberia, stock: 5);

        Livewire::actingAs($usuario)->test(PosVenta::class)
            ->call('agregarProductoInventario', $producto->id)
            ->call('agregarProductoInventario', $producto->id)
            ->call('cobrar');

        Livewire::actingAs($usuario)
            ->test(HistorialVentas::class)
            ->call('confirmarAccion', Venta::sole()->id, 'editar')
            ->set('motivo', 'Prueba de cancelar edicion')
            ->set('passwordConfirm', 'password')
            ->call('verificarPassword')
            ->call('disminuirCantidad', 0)
            ->call('cancelarEdicion');

        $this->assertSame(3, $producto->fresh()->stock_actual);
    }

    public function test_no_se_puede_guardar_una_edicion_sin_pasar_por_la_contrasena(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $producto = $this->crearProducto($barberia);

        Livewire::actingAs($usuario)->test(PosVenta::class)
            ->call('agregarProductoInventario', $producto->id)
            ->call('cobrar');

        Livewire::actingAs($usuario)
            ->test(HistorialVentas::class)
            ->call('guardarEdicion')
            ->assertForbidden();
    }

    public function test_los_reportes_ignoran_ventas_eliminadas(): void
    {
        $barberia = $this->crearBarberia();
        $producto = $this->crearProducto($barberia, precio: 10000);

        Livewire::actingAs($this->crearUsuario($barberia))->test(PosVenta::class)
            ->call('agregarProductoInventario', $producto->id)->call('cobrar')
            ->call('agregarProductoInventario', $producto->id)->call('cobrar');

        Venta::first()->update(['fue_eliminada' => true, 'total' => 0]);

        $resumen = Venta::resumenMes(now()->month, now()->year, $barberia->id);
        $this->assertEquals(10000, $resumen['ingresos']);
        $this->assertSame(1, $resumen['cantidad_ventas']);
    }
}
