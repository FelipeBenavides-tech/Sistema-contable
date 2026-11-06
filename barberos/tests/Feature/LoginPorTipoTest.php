<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class LoginPorTipoTest extends TestCase
{
    use CreaDatosDePrueba, RefreshDatabase;

    public function test_el_login_muestra_los_cuatro_botones(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('Cliente')->assertSee('Barbero')->assertSee('Negocio')->assertSee('Administrador del sistema')
            ->assertSee('como=cliente', false)->assertDontSee('name="password"', false);
    }

    public function test_cada_boton_muestra_su_formulario(): void
    {
        $this->get('/login?como=negocio')->assertOk()->assertSee('name="password"', false)->assertSee('value="negocio"', false);
        $this->get('/login?como=cliente')->assertOk()->assertSee('name="contacto"', false)->assertDontSee('name="password"', false);
    }

    public function test_cada_tipo_llega_a_su_panel(): void
    {
        $barberia = $this->crearBarberia('Uno', 'pro');
        $dueno = $this->crearUsuario($barberia);
        $barbero = $this->crearUsuarioBarbero($barberia, $this->crearBarbero($barberia));
        $admin = $this->crearAdmin();

        $this->post('/login', ['email' => $dueno->email, 'password' => 'password', 'tipo' => 'negocio'])->assertRedirect(route('pos'));
        $this->post('/logout');
        $this->post('/login', ['email' => $barbero->email, 'password' => 'password', 'tipo' => 'barbero'])->assertRedirect(route('mi-agenda'));
        $this->post('/logout');
        $this->post('/login', ['email' => $admin->email, 'password' => 'password', 'tipo' => 'admin'])->assertRedirect(route('admin.panel'));
    }

    public function test_si_elige_el_boton_equivocado_se_le_indica_el_correcto(): void
    {
        $barberia = $this->crearBarberia('Uno', 'pro');
        $barbero = $this->crearUsuarioBarbero($barberia, $this->crearBarbero($barberia));

        $this->post('/login', ['email' => $barbero->email, 'password' => 'password', 'tipo' => 'negocio'])
            ->assertRedirect(route('login', ['como' => 'barbero']))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_el_cliente_recibe_por_correo_el_enlace_de_sus_citas(): void
    {
        $barberia = $this->crearBarberia('Barbería Pro', 'pro');
        $barberia->update(['slug' => 'barberia-pro', 'reservas_activas' => true]);
        $cliente = $this->crearCliente($barberia);
        $cliente->update(['email' => 'juan@correo.com', 'telefono' => '3001234567']);

        $enviados = [];
        Event::listen(MessageSending::class, function ($e) use (&$enviados) {
            $enviados[] = $e->message;
        });

        $this->post('/login/cliente', ['contacto' => '300 123 4567'])
            ->assertRedirect(route('login', ['como' => 'cliente']))
            ->assertSessionHas('status');

        $this->assertCount(1, $enviados);
        $this->assertSame('juan@correo.com', $enviados[0]->getTo()[0]->getAddress());
        $this->assertStringContainsString($cliente->fresh()->urlPanel(), $enviados[0]->getTextBody());

        $this->post('/login/cliente', ['contacto' => 'JUAN@correo.com']);
        $this->assertCount(2, $enviados);
    }

    public function test_un_contacto_desconocido_ve_el_mismo_mensaje_y_no_se_envia_nada(): void
    {
        $enviados = 0;
        Event::listen(MessageSending::class, function () use (&$enviados) {
            $enviados++;
        });

        $this->post('/login/cliente', ['contacto' => 'nadie@correo.com'])->assertSessionHas('status');
        $this->assertSame(0, $enviados);
    }
}
