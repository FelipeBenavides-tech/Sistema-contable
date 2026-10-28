<?php

namespace Tests\Feature;

use App\Livewire\GestionAgenda;
use App\Livewire\HistorialVentas;
use App\Livewire\MiBarberia;
use App\Livewire\PosVenta;
use App\Livewire\ReservaEnLinea;
use App\Livewire\VerReserva;
use App\Models\Barberia;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\PagoEnLinea;
use App\Models\Venta;
use App\Support\Wompi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class ReservasEnLineaTest extends TestCase
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

    private function barberiaConReservas(int $anticipo = 50): Barberia
    {
        $barberia = $this->crearBarberia('Barbería Pro', 'pro');
        $barberia->update([
            'slug'                     => 'barberia-pro',
            'reservas_activas'         => true,
            'anticipo_porcentaje'      => $anticipo,
            'horas_cancelacion'        => 4,
            'wompi_llave_publica'      => 'pub_test_abc123',
            'wompi_llave_privada'      => 'prv_test_abc123',
            'wompi_secreto_eventos'    => 'test_events_secreto',
            'wompi_secreto_integridad' => 'test_integrity_secreto',
        ]);

        return $barberia->fresh();
    }

    private function reservar(Barberia $barberia, int $servicioId, string $hora = '10:00', string $telefono = '3001112233')
    {
        return Livewire::test(ReservaEnLinea::class, ['slug' => $barberia->slug])
            ->call('alternarServicio', $servicioId)
            ->set('fecha', '2026-11-04')
            ->set('hora', $hora)
            ->set('nombre', 'Laura Gómez')
            ->set('telefono', $telefono)
            ->set('acepta', true)
            ->call('confirmar');
    }

    private function eventoWompi(PagoEnLinea $pago, string $estado, string $secreto = 'test_events_secreto'): array
    {
        $transaccion = [
            'id' => '12345-1700000000-11111', 'reference' => $pago->referencia, 'status' => $estado,
            'amount_in_cents' => $pago->monto_centavos, 'currency' => 'COP', 'payment_method_type' => 'NEQUI',
        ];
        $timestamp = 1700000000;
        $checksum = hash('sha256', $transaccion['id'] . $estado . $pago->monto_centavos . $timestamp . $secreto);

        return [
            'event' => 'transaction.updated',
            'data' => ['transaction' => $transaccion],
            'environment' => 'test',
            'signature' => ['properties' => ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'], 'checksum' => $checksum],
            'timestamp' => $timestamp,
            'sent_at' => '2026-11-03T13:00:00.000Z',
        ];
    }

    public function test_la_firma_de_eventos_sigue_el_formato_de_wompi(): void
    {
        // Wompi concatena los valores de signature.properties, el timestamp y el secreto de eventos (SHA-256)
        $secreto = 'prod_events_OcHnIzeBl5socpwByQ4hA52Em3USQ93Z';
        $checksum = strtoupper(hash('sha256', '1234-1610641025-49201' . 'APPROVED' . '4490000' . '1530291411' . $secreto));
        $evento = [
            'data' => ['transaction' => ['id' => '1234-1610641025-49201', 'status' => 'APPROVED', 'amount_in_cents' => 4490000]],
            'signature' => ['properties' => ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'], 'checksum' => $checksum],
            'timestamp' => 1530291411,
        ];

        $this->assertTrue(Wompi::eventoValido($evento, $secreto));
        $this->assertFalse(Wompi::eventoValido($evento, 'prod_events_otro'));

        $evento['data']['transaction']['amount_in_cents'] = 100;
        $this->assertFalse(Wompi::eventoValido($evento, $secreto));
    }

    public function test_la_firma_de_integridad_sigue_el_formato_de_wompi(): void
    {
        $barberia = new Barberia(['wompi_secreto_integridad' => 'prod_integrity_Z5mMke9x0k8gpErbDqwrJXMqsI6SFli6']);
        $firma = (new Wompi($barberia))->firmaIntegridad('sk8-438k4-xmxm392-sn2m2490000', 0, 'COP', '2023-06-09T20:28:50.000Z');

        // Ejemplo de la documentación: "sk8-438k4-xmxm392-sn2m2490000COP2023-06-09T20:28:50.000Zprod_integrity_…"
        $this->assertSame(hash('sha256', 'sk8-438k4-xmxm392-sn2m2490000' . '0' . 'COP2023-06-09T20:28:50.000Zprod_integrity_Z5mMke9x0k8gpErbDqwrJXMqsI6SFli6'), $firma);
    }

    public function test_la_pagina_de_reservas_solo_existe_si_esta_activa_y_es_pro(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->get('/reservar/barberia-pro')->assertOk()->assertSee('Reserva tu cita');

        $barberia->update(['reservas_activas' => false]);
        $this->get('/reservar/barberia-pro')->assertNotFound();

        $barberia->update(['reservas_activas' => true, 'nivel' => 'base']);
        $this->get('/reservar/barberia-pro')->assertNotFound();
    }

    public function test_reservar_aparta_la_hora_y_manda_a_pagar_el_anticipo_a_wompi(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $corte = $this->crearServicio($barberia, 25000);

        $respuesta = $this->reservar($barberia, $corte->id);

        $cita = Cita::sole();
        $pago = PagoEnLinea::sole();
        $this->assertSame('pendiente_pago', $cita->estado);
        $this->assertSame('en_linea', $cita->origen);
        $this->assertEquals(12500, $cita->anticipo); // 50% de 25.000
        $this->assertSame(1250000, $pago->monto_centavos);
        $this->assertNotNull($cita->expira_en);

        $url = $respuesta->effects['redirect'];
        $this->assertStringStartsWith('https://checkout.wompi.co/p/?', $url);
        $this->assertStringContainsString('reference=' . $pago->referencia, $url);
        $this->assertStringContainsString('amount-in-cents=1250000', $url);

        // Mientras paga, nadie más puede tomar esa hora
        $this->assertNotContains('10:00', Cita::horasDisponibles($cita->barbero, '2026-11-04', 30));
    }

    public function test_el_aviso_de_wompi_aprobado_confirma_la_cita(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $this->reservar($barberia, $this->crearServicio($barberia, 20000)->id);
        $pago = PagoEnLinea::sole();

        $this->postJson('/webhooks/wompi', $this->eventoWompi($pago, 'APPROVED'))->assertOk();

        $cita = Cita::sole();
        $this->assertSame('confirmada', $cita->estado);
        $this->assertSame('pagado', $cita->anticipo_estado);
        $this->assertSame('APPROVED', $pago->fresh()->estado);
        $this->assertSame('NEQUI', $pago->fresh()->metodo);
    }

    public function test_un_aviso_con_firma_falsa_no_confirma(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $this->reservar($barberia, $this->crearServicio($barberia)->id);
        $pago = PagoEnLinea::sole();

        $this->postJson('/webhooks/wompi', $this->eventoWompi($pago, 'APPROVED', 'test_events_falso'))->assertStatus(401);

        $this->assertSame('pendiente_pago', Cita::sole()->estado);
    }

    public function test_un_aviso_con_otro_monto_no_confirma(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $this->reservar($barberia, $this->crearServicio($barberia)->id);
        $pago = PagoEnLinea::sole();
        $evento = $this->eventoWompi($pago, 'APPROVED');
        $pago->update(['monto_centavos' => 100]);
        $evento['signature']['checksum'] = hash('sha256', $evento['data']['transaction']['id'] . 'APPROVED' . $evento['data']['transaction']['amount_in_cents'] . $evento['timestamp'] . 'test_events_secreto');

        $this->postJson('/webhooks/wompi', $evento)->assertOk();

        $this->assertSame('pendiente_pago', Cita::sole()->estado);
    }

    public function test_pago_rechazado_libera_la_hora(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $this->reservar($barberia, $this->crearServicio($barberia)->id);

        $this->postJson('/webhooks/wompi', $this->eventoWompi(PagoEnLinea::sole(), 'DECLINED'))->assertOk();

        $cita = Cita::sole();
        $this->assertSame('cancelada', $cita->estado);
        $this->assertContains('10:00', Cita::horasDisponibles($cita->barbero, '2026-11-04', 30));
    }

    public function test_las_reservas_sin_pagar_se_liberan_al_vencer(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $this->reservar($barberia, $this->crearServicio($barberia)->id);

        Carbon::setTestNow(now()->addMinutes(16));
        // Aunque el programador no haya corrido, la hora ya se ofrece
        $this->assertContains('10:00', Cita::horasDisponibles(Cita::sole()->barbero, '2026-11-04', 30));

        $this->artisan('citas:liberar')->assertSuccessful();
        $this->assertSame('cancelada', Cita::sole()->estado);
    }

    public function test_al_volver_de_wompi_se_consulta_la_transaccion(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $this->reservar($barberia, $this->crearServicio($barberia)->id);
        $pago = PagoEnLinea::sole();
        $cita = Cita::sole();

        Http::fake(['sandbox.wompi.co/v1/transactions/*' => Http::response(['data' => [
            'id' => 'tx-1', 'reference' => $pago->referencia, 'status' => 'APPROVED',
            'amount_in_cents' => $pago->monto_centavos, 'currency' => 'COP', 'payment_method_type' => 'PSE',
        ]])]);

        $this->get('/reserva/' . $cita->token . '?id=tx-1')->assertOk()->assertSee('Tu cita está confirmada');
        $this->assertSame('confirmada', $cita->fresh()->estado);
    }

    public function test_cancelar_a_tiempo_deja_saldo_a_favor_y_se_usa_en_la_siguiente_reserva(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $corte = $this->crearServicio($barberia, 20000);
        $this->reservar($barberia, $corte->id);
        $this->postJson('/webhooks/wompi', $this->eventoWompi(PagoEnLinea::sole(), 'APPROVED'));
        $cita = Cita::sole();

        Livewire::test(VerReserva::class, ['token' => $cita->token])->call('cancelar');

        $cliente = Cliente::sole();
        $this->assertSame('cancelada', $cita->fresh()->estado);
        $this->assertEquals(10000, $cliente->saldo_favor);

        // La siguiente reserva usa el saldo y no pasa por Wompi
        $this->reservar($barberia, $corte->id, '11:00');
        $nueva = Cita::latest('id')->first();
        $this->assertSame('confirmada', $nueva->estado);
        $this->assertSame('pagado', $nueva->anticipo_estado);
        $this->assertEquals(10000, $nueva->saldo_favor_usado);
        $this->assertEquals(0, $cliente->fresh()->saldo_favor);
        $this->assertSame(1, PagoEnLinea::count());
    }

    public function test_cancelar_tarde_o_no_llegar_deja_el_anticipo_a_la_barberia(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $corte = $this->crearServicio($barberia, 20000);
        $this->reservar($barberia, $corte->id);
        $this->postJson('/webhooks/wompi', $this->eventoWompi(PagoEnLinea::sole(), 'APPROVED'));
        $cita = Cita::sole();

        // Faltan 2 horas para la cita (menos de las 4 de la política)
        Carbon::setTestNow(Carbon::parse('2026-11-04 08:00:00'));
        Livewire::test(VerReserva::class, ['token' => $cita->token])->call('cancelar');

        $this->assertSame('retenido', $cita->fresh()->anticipo_estado);
        $this->assertEquals(0, Cliente::sole()->saldo_favor);
        $venta = Venta::sole();
        $this->assertSame('anticipo', $venta->metodo_pago);
        $this->assertEquals(10000, $venta->total);
        $this->assertEquals(0, $venta->comision_barbero);
        $this->assertEquals(0, $venta->monto_efectivo);
    }

    public function test_si_la_barberia_cancela_el_cliente_conserva_el_anticipo(): void
    {
        $barberia = $this->barberiaConReservas();
        $this->crearBarbero($barberia);
        $this->reservar($barberia, $this->crearServicio($barberia, 20000)->id);
        $this->postJson('/webhooks/wompi', $this->eventoWompi(PagoEnLinea::sole(), 'APPROVED'));

        Carbon::setTestNow(Carbon::parse('2026-11-04 09:30:00'));
        Livewire::actingAs($this->crearUsuario($barberia))->test(GestionAgenda::class)
            ->call('cambiarEstado', Cita::sole()->id, 'cancelada');

        $this->assertEquals(10000, Cliente::sole()->saldo_favor);
        $this->assertSame(0, Venta::count());
    }

    public function test_al_cobrar_la_cita_se_descuenta_el_anticipo(): void
    {
        $barberia = $this->barberiaConReservas();
        $barbero = $this->crearBarbero($barberia, 50);
        $this->reservar($barberia, $this->crearServicio($barberia, 20000)->id);
        $this->postJson('/webhooks/wompi', $this->eventoWompi(PagoEnLinea::sole(), 'APPROVED'));
        $cita = Cita::sole();

        Carbon::setTestNow(Carbon::parse('2026-11-04 10:30:00'));
        Livewire::withQueryParams(['cita' => $cita->id])
            ->actingAs($this->crearUsuario($barberia))
            ->test(PosVenta::class)
            ->assertSet('barberoId', $barbero->id)
            ->call('cobrar')
            ->assertHasNoErrors();

        $venta = Venta::sole();
        $this->assertEquals(20000, $venta->total);
        $this->assertEquals(10000, $venta->monto_prepagado);
        $this->assertEquals(10000, $venta->monto_efectivo); // solo lo que pagó en la caja
        $this->assertEquals(10000, $venta->comision_barbero); // la comisión es sobre el servicio completo
        $this->assertSame('aplicado', $cita->fresh()->anticipo_estado);
        $this->assertSame('atendida', $cita->fresh()->estado);
    }

    public function test_el_saldo_a_favor_se_usa_en_la_caja_y_vuelve_si_se_elimina_la_venta(): void
    {
        $barberia = $this->crearBarberia('Uno', 'pro');
        $usuario = $this->crearUsuario($barberia);
        $cliente = $this->crearCliente($barberia);
        $cliente->update(['saldo_favor' => 5000]);

        Livewire::actingAs($usuario)->test(PosVenta::class)
            ->call('seleccionarCliente', $cliente->id)
            ->call('agregarServicio', $this->crearServicio($barberia, 20000)->id)
            ->set('barberoId', $this->crearBarbero($barberia)->id)
            ->set('usarSaldoFavor', true)
            ->call('cobrar')
            ->assertHasNoErrors();

        $venta = Venta::sole();
        $this->assertEquals(5000, $venta->monto_prepagado);
        $this->assertEquals(15000, $venta->monto_efectivo);
        $this->assertEquals(0, $cliente->fresh()->saldo_favor);

        Livewire::actingAs($usuario)->test(HistorialVentas::class)
            ->call('confirmarAccion', $venta->id, 'eliminar')
            ->set('motivo', 'Se registro por error')
            ->set('passwordConfirm', 'password')
            ->call('verificarPassword');

        $this->assertEquals(5000, $cliente->fresh()->saldo_favor);
    }

    public function test_sin_wompi_configurado_se_reserva_sin_anticipo(): void
    {
        $barberia = $this->barberiaConReservas();
        $barberia->update(['wompi_secreto_integridad' => null]);
        $this->crearBarbero($barberia);

        $this->reservar($barberia, $this->crearServicio($barberia)->id)->assertRedirect();

        $cita = Cita::sole();
        $this->assertSame('confirmada', $cita->estado);
        $this->assertEquals(0, $cita->anticipo);
        $this->assertSame(0, PagoEnLinea::count());
    }

    public function test_la_configuracion_guarda_las_llaves_cifradas(): void
    {
        $barberia = $this->crearBarberia('Mi Barbería', 'pro');

        Livewire::actingAs($this->crearUsuario($barberia))->test(MiBarberia::class)
            ->set('slug', 'Mi Barbería Centro')
            ->set('reservas_activas', true)
            ->set('wompi_llave_publica', 'pub_prod_123')
            ->set('wompi_llave_privada', 'prv_prod_456')
            ->set('wompi_secreto_eventos', 'prod_events_789')
            ->set('wompi_secreto_integridad', 'prod_integrity_000')
            ->call('guardarReservas')
            ->assertHasNoErrors();

        $barberia->refresh();
        $this->assertSame('mi-barberia-centro', $barberia->slug);
        $this->assertSame('prod_integrity_000', $barberia->wompi_secreto_integridad);
        $crudo = \DB::table('barberias')->where('id', $barberia->id)->value('wompi_secreto_integridad');
        $this->assertStringNotContainsString('prod_integrity_000', $crudo);
        $this->assertTrue($barberia->recibe_reservas);
    }
}
