<?php

namespace Tests\Feature;

use App\Models\Barberia;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_respuestas_llevan_cabeceras_de_seguridad(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_por_https_se_exige_https_en_adelante(): void
    {
        $this->get('https://localhost/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_la_pagina_no_encontrada_esta_en_espanol(): void
    {
        $this->get('/no-existe')
            ->assertNotFound()
            ->assertSee('No encontramos esta página');
    }

    public function test_en_produccion_el_seeder_no_crea_la_barberia_demo(): void
    {
        $this->app['env'] = 'production';

        $this->app->make(DatabaseSeeder::class)->run();

        $this->assertSame(1, User::where('is_admin', true)->count());
        $this->assertSame(0, Barberia::count());
    }
}
