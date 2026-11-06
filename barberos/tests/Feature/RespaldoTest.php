<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RespaldoTest extends TestCase
{
    public function test_el_respaldo_copia_la_base_de_datos_y_borra_los_viejos(): void
    {
        // VACUUM no corre dentro de una transacción: aquí se usa un archivo aparte
        $base = storage_path('framework/testing-respaldo.sqlite');
        File::ensureDirectoryExists(dirname($base));
        File::put($base, '');
        config(['database.connections.sqlite.database' => $base]);
        DB::purge('sqlite');
        DB::statement('CREATE TABLE ventas (id INTEGER PRIMARY KEY)');

        $carpeta = storage_path('app/respaldos');
        File::deleteDirectory($carpeta);
        File::ensureDirectoryExists($carpeta);
        File::put($carpeta . '/kaixa-viejo.sqlite', '');
        touch($carpeta . '/kaixa-viejo.sqlite', now()->subDays(40)->getTimestamp());

        $this->artisan('kaixa:respaldo')->assertSuccessful();

        $archivos = File::glob($carpeta . '/kaixa-*.sqlite');
        $this->assertCount(1, $archivos);
        $this->assertGreaterThan(0, filesize($archivos[0]));

        File::deleteDirectory($carpeta);
        File::delete($base);
    }
}
