<?php

namespace Tests\Feature;

use App\Livewire\MiBarberia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class ReportePdfTest extends TestCase
{
    use CreaDatosDePrueba, RefreshDatabase;

    public function test_el_pdf_sale_con_el_nombre_de_la_barberia(): void
    {
        $barberia = $this->crearBarberia('Barbería Los Reyes');
        $usuario  = $this->crearUsuario($barberia);

        $respuesta = $this->actingAs($usuario)->get(route('reportes.pdf', ['mes' => now()->month, 'anio' => now()->year]));

        $respuesta->assertOk();
        $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
        $this->assertStringContainsString('barberia-los-reyes-reporte', $respuesta->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }

    public function test_la_barberia_puede_cambiar_su_nombre_y_logo(): void
    {
        Storage::fake('local');
        $barberia = $this->crearBarberia();
        $usuario  = $this->crearUsuario($barberia);

        Livewire::actingAs($usuario)
            ->test(MiBarberia::class)
            ->set('nombre', 'Barbería Nueva')
            ->set('nit', '900123456')
            ->set('logo', UploadedFile::fake()->image('logo.png', 200, 200))
            ->call('guardar')
            ->assertHasNoErrors();

        $barberia->refresh();
        $this->assertSame('Barbería Nueva', $barberia->nombre);
        $this->assertSame('900123456', $barberia->nit);
        Storage::disk('local')->assertExists($barberia->logo);
        $this->assertStringStartsWith('data:image/png;base64,', $barberia->logoDataUri());

        Livewire::actingAs($usuario)->test(MiBarberia::class)->call('quitarLogo');
        $this->assertNull($barberia->fresh()->logo);
    }
}
