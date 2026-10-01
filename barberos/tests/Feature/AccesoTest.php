<?php

namespace Tests\Feature;

use App\Livewire\Admin\PanelAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class AccesoTest extends TestCase
{
    use CreaDatosDePrueba, RefreshDatabase;

    public function test_un_usuario_de_barberia_no_puede_entrar_al_panel_admin(): void
    {
        $usuario = $this->crearUsuario($this->crearBarberia());

        $this->actingAs($usuario)->get('/admin')->assertForbidden();
    }

    public function test_un_usuario_de_barberia_no_puede_usar_las_acciones_del_panel_admin(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);

        Livewire::actingAs($usuario)->test(PanelAdmin::class)->assertForbidden();

        $this->assertTrue($barberia->fresh()->activo);
    }

    public function test_el_admin_entra_al_panel_y_no_a_la_caja(): void
    {
        $admin = $this->crearAdmin();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/pos')->assertRedirect(route('admin.panel'));
    }

    public function test_el_admin_va_a_su_panel_al_iniciar_sesion(): void
    {
        $admin = $this->crearAdmin();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.panel'));
    }

    public function test_un_usuario_sin_barberia_ve_aviso(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get('/pos')->assertForbidden()->assertSee('Cuenta no configurada');
    }

    public function test_una_barberia_vencida_no_puede_entrar(): void
    {
        $barberia = $this->crearBarberia();
        $barberia->update(['fecha_vencimiento' => now()->subDay()]);

        $this->actingAs($this->crearUsuario($barberia))->get('/pos')->assertForbidden();
    }

    public function test_no_hay_registro_publico(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name'                  => 'Intruso',
            'email'                 => 'intruso@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }

    public function test_las_paginas_de_la_barberia_cargan(): void
    {
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);
        $this->crearBarbero($barberia);
        $this->crearServicio($barberia);
        $this->crearProducto($barberia);

        foreach (['/pos', '/ventas', '/inventario', '/gastos', '/reportes', '/barberos', '/servicios'] as $ruta) {
            $this->actingAs($usuario)->get($ruta)->assertOk();
        }
    }

    public function test_editar_una_barberia_no_cambia_su_vencimiento(): void
    {
        $barberia = $this->crearBarberia();
        $vence    = $barberia->fecha_vencimiento->toDateString();

        Livewire::actingAs($this->crearAdmin())
            ->test(PanelAdmin::class)
            ->call('editar', $barberia->id)
            ->set('nombre', 'Nuevo nombre')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('Nuevo nombre', $barberia->fresh()->nombre);
        $this->assertSame($vence, $barberia->fresh()->fecha_vencimiento->toDateString());
    }
}
