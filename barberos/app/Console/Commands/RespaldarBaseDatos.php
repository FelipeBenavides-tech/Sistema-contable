<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Copia de seguridad de la base de datos SQLite en storage/app/respaldos.
 * Guarda una copia por día y borra las más viejas que los días indicados.
 */
class RespaldarBaseDatos extends Command
{
    protected $signature = 'kaixa:respaldo {--dias=30 : Cuántos días de respaldos conservar}';

    protected $description = 'Hace una copia de seguridad de la base de datos';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->warn('Este respaldo es para SQLite. Con MySQL usa los respaldos del hosting (por ejemplo, desde cPanel).');
            return self::SUCCESS;
        }

        $carpeta = storage_path('app/respaldos');
        File::ensureDirectoryExists($carpeta);

        $destino = $carpeta . '/kaixa-' . now()->format('Y-m-d_His') . '.sqlite';

        // VACUUM INTO saca una copia consistente aunque haya ventas registrándose
        DB::statement('VACUUM INTO ?', [$destino]);

        $limite = now()->subDays(max(1, (int) $this->option('dias')))->getTimestamp();
        foreach (File::glob($carpeta . '/kaixa-*.sqlite') as $archivo) {
            if (File::lastModified($archivo) < $limite) {
                File::delete($archivo);
            }
        }

        $this->info('Respaldo guardado en ' . $destino);

        return self::SUCCESS;
    }
}
