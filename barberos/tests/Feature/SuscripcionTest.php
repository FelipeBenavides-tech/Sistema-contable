<?php

namespace Tests\Feature;

use App\Livewire\Admin\PanelAdmin;
use App\Livewire\PosVenta;
use App\Models\Barberia;
use App\Models\Gasto;
use App\Models\User;
use App\Support\PlanesKaixa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class SuscripcionTest extends TestCase
{
    use CreaDatosDePrueba, RefreshDatabase;

    public function test_los_planes_muestran_el_ahorro_frente_al_mensual(): void
    {
        $planes = PlanesKaixa::catalogo();

        $this->assertEquals(130000, $planes['mensual']['precio']);
        $this->assertEquals(80000, $planes['semestral']['ahorro']);
        $this->assertEquals(360000, $planes['anual']['ahorro']);
        $this->assertSame(23, $planes['anual']['ahorro_porcentaje']);
    }

    public function test_dentro_de_la_gracia_puede_entrar_y_ve_el_aviso_una_vez(): void
    {
        $barberia = $this->crearBarberia();
        $barberia->update(['fecha_vencimiento' => now()->subDays(3)]);
        $usuario = $this->crearUsuario($barberia);

        $this->assertSame('en_gracia', $barberia->fresh()->estado_pago);

        $this->actingAs($usuario)->get('/pos')->assertOk()
            ->assertSee('Tienes un pago pendiente')
            ->assertSee('Vencido');

        // Ya lo cerró: no vuelve a salir en la misma sesión, pero el estado sigue visible
        $this->actingAs($usuario)->get('/ventas')->assertOk()
            ->assertDontSee('Tienes un pago pendiente')
            ->assertSee('Vencido');
    }

    public function test_el_ultimo_dia_de_gracia_aun_entra_y_al_dia_siguiente_se_bloquea(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);

        $barberia->update(['fecha_vencimiento' => now()->subDays(5)]);
        $this->actingAs($usuario)->get('/pos')->assertOk();

        $barberia->update(['fecha_vencimiento' => now()->subDays(6)]);
        $this->actingAs($usuario->fresh())->get('/pos')->assertForbidden()->assertSee('Tu plan está vencido');
    }

    public function test_pagar_en_gracia_sigue_desde_el_vencimiento_y_se_anota_como_gasto(): void
    {
        $barberia = $this->crearBarberia();
        $vence = now()->subDays(2)->startOfDay();
        $barberia->update(['fecha_vencimiento' => $vence]);

        $barberia->registrarPago(130000);

        $this->assertSame($vence->copy()->addMonth()->toDateString(), $barberia->fresh()->fecha_vencimiento->toDateString());
        $this->assertDatabaseHas('gastos', ['barberia_id' => $barberia->id, 'categoria' => 'suscripcion', 'valor' => 130000]);
    }

    public function test_pagar_estando_bloqueada_cuenta_desde_hoy(): void
    {
        $barberia = $this->crearBarberia();
        $barberia->update(['fecha_vencimiento' => now()->subDays(20), 'plan' => 'semestral']);

        $barberia->registrarPago(700000);

        $this->assertSame(now()->addMonths(6)->toDateString(), $barberia->fresh()->fecha_vencimiento->toDateString());
    }

    public function test_el_plan_por_uso_cobra_el_porcentaje_de_cada_venta(): void
    {
        $barberia = $this->crearBarberia();
        $barberia->update(['plan' => 'porcentaje', 'saldo' => 1000]);
        $usuario  = $this->crearUsuario($barberia);
        $barbero  = $this->crearBarbero($barberia);
        $servicio = $this->crearServicio($barberia, 20000);

        Livewire::actingAs($usuario)->test(PosVenta::class)
            ->call('agregarServicio', $servicio->id)
            ->set('barberoId', $barbero->id)
            ->call('cobrar')
            ->assertHasNoErrors();

        // 2,5% de 20.000 = 500
        $this->assertEquals(500, (float) $barberia->fresh()->saldo);
        $this->assertDatabaseHas('ventas', ['barberia_id' => $barberia->id, 'cargo_plataforma' => 500]);
    }

    public function test_sin_saldo_suficiente_no_deja_vender(): void
    {
        $barberia = $this->crearBarberia();
        $barberia->update(['plan' => 'porcentaje', 'saldo' => 100]);
        $usuario  = $this->crearUsuario($barberia);
        $barbero  = $this->crearBarbero($barberia);
        $servicio = $this->crearServicio($barberia, 20000);

        Livewire::actingAs($usuario)->test(PosVenta::class)
            ->call('agregarServicio', $servicio->id)
            ->set('barberoId', $barbero->id)
            ->call('cobrar')
            ->assertHasErrors('carrito');

        $this->assertDatabaseCount('ventas', 0);
        $this->assertEquals(100, (float) $barberia->fresh()->saldo);
    }

    public function test_con_saldo_en_cero_el_sistema_se_deshabilita_y_la_recarga_lo_habilita(): void
    {
        $barberia = $this->crearBarberia();
        $barberia->update(['plan' => 'porcentaje', 'saldo' => 0]);
        $usuario = $this->crearUsuario($barberia);

        $this->actingAs($usuario)->get('/pos')->assertForbidden()->assertSee('Se acabó tu saldo');

        $barberia->registrarPago(50000);

        $this->actingAs($usuario->fresh())->get('/pos')->assertOk();
        $this->assertEquals(50000, (float) $barberia->fresh()->saldo);
        $this->assertSame(50000.0, Gasto::totalMes(now()->month, now()->year, $barberia->id));
    }

    public function test_el_admin_registra_pagos_de_varios_meses(): void
    {
        $barberia = $this->crearBarberia();
        $vence = $barberia->fecha_vencimiento->copy();

        Livewire::actingAs($this->crearAdmin())->test(PanelAdmin::class)
            ->call('abrirPago', $barberia->id)
            ->set('pagoCantidad', 3)
            ->assertSet('pagoValor', 390000.0)
            ->call('registrarPago')
            ->assertHasNoErrors();

        $this->assertSame($vence->addMonths(3)->toDateString(), $barberia->fresh()->fecha_vencimiento->toDateString());
        $this->assertDatabaseHas('pagos_suscripcion', ['barberia_id' => $barberia->id, 'meses' => 3, 'valor' => 390000]);
    }

    public function test_al_crear_una_barberia_mensual_arranca_el_contrato_de_seis_meses(): void
    {
        Livewire::actingAs($this->crearAdmin())->test(PanelAdmin::class)
            ->call('nuevo')
            ->set('nombre', 'Barbería Nueva')
            ->set('email', 'nueva@example.com')
            ->set('password', 'secreto123')
            ->call('guardar')
            ->assertHasNoErrors();

        $barberia = Barberia::firstWhere('nombre', 'Barbería Nueva');
        $this->assertSame(now()->addMonths(6)->toDateString(), $barberia->contrato_hasta->toDateString());
        $this->assertSame(now()->addMonth()->toDateString(), $barberia->fecha_vencimiento->toDateString());
        $this->assertSame(1, $barberia->pagosSuscripcion()->count());
        $this->assertNotNull(User::firstWhere('email', 'nueva@example.com'));
    }

    public function test_durante_el_contrato_minimo_solo_puede_pasar_a_un_plan_mas_largo(): void
    {
        $barberia = $this->crearBarberia();
        $barberia->update(['contrato_hasta' => now()->addMonths(4)]);
        $admin = $this->crearAdmin();

        Livewire::actingAs($admin)->test(PanelAdmin::class)
            ->call('editar', $barberia->id)
            ->set('plan', 'porcentaje')
            ->call('guardar')
            ->assertHasErrors('plan');

        $this->assertSame('mensual', $barberia->fresh()->plan);

        Livewire::actingAs($admin)->test(PanelAdmin::class)
            ->call('editar', $barberia->id)
            ->set('plan', 'anual')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('anual', $barberia->fresh()->plan);
    }

    public function test_el_saldo_a_favor_se_devuelve_al_terminar_el_contrato(): void
    {
        $barberia = $this->crearBarberia();
        $barberia->update(['plan' => 'porcentaje', 'saldo' => 30000, 'contrato_hasta' => now()->addMonth()]);
        $admin = $this->crearAdmin();

        Livewire::actingAs($admin)->test(PanelAdmin::class)
            ->call('devolverSaldo', $barberia->id)
            ->assertHasErrors('devolucion');
        $this->assertEquals(30000, (float) $barberia->fresh()->saldo);

        $barberia->update(['contrato_hasta' => now()->subDay()]);

        Livewire::actingAs($admin)->test(PanelAdmin::class)
            ->call('devolverSaldo', $barberia->id)
            ->assertHasNoErrors();

        $this->assertEquals(0, (float) $barberia->fresh()->saldo);
        $this->assertDatabaseHas('pagos_suscripcion', ['barberia_id' => $barberia->id, 'plan' => 'devolucion', 'valor' => -30000]);
        $this->assertDatabaseHas('gastos', ['barberia_id' => $barberia->id, 'valor' => -30000]);
    }

    public function test_la_pantalla_mi_plan_carga(): void
    {
        $barberia = $this->crearBarberia();

        $this->actingAs($this->crearUsuario($barberia))->get('/mi-plan')->assertOk()
            ->assertSee('Planes de Kaixa')
            ->assertSee('$1.200.000');
    }
}
