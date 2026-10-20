<?php

namespace Database\Seeders;

use App\Models\Barberia;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Crea el administrador y una barbería de prueba.
     *
     * Las contraseñas se leen del archivo .env (SEED_ADMIN_PASSWORD y
     * SEED_DEMO_PASSWORD). Si no existen, se generan al azar y se muestran
     * en la terminal una sola vez.
     */
    public function run(): void
    {
        $adminEmail    = env('SEED_ADMIN_EMAIL', 'admin@kaixa.local');
        $adminPassword = env('SEED_ADMIN_PASSWORD') ?: Str::password(12, symbols: false);
        $demoEmail     = env('SEED_DEMO_EMAIL', 'barberia@kaixa.local');
        $demoPassword  = env('SEED_DEMO_PASSWORD') ?: Str::password(12, symbols: false);

        // ── Admin del SaaS ───────────────────────────────────────────────────
        User::updateOrCreate(['email' => $adminEmail], [
            'name'        => 'Admin SaaS',
            'password'    => $adminPassword,
            'is_admin'    => true,
            'barberia_id' => null,
        ]);

        // ── Barbería de prueba ───────────────────────────────────────────────
        $barberia = Barberia::firstOrCreate(['nombre' => 'Barbería El Estilo'], [
            'propietario'       => 'Carlos Mendoza',
            'telefono'          => '3001234567',
            'direccion'         => 'Calle 10 # 5-20, Cali',
            'activo'            => true,
            'plan'              => 'mensual',
            'fecha_vencimiento' => now()->addMonth(),
        ]);

        // ── Usuario de la barbería de prueba ─────────────────────────────────
        User::updateOrCreate(['email' => $demoEmail], [
            'name'        => 'Carlos Mendoza',
            'password'    => $demoPassword,
            'is_admin'    => false,
            'barberia_id' => $barberia->id,
        ]);

        $this->command?->info('Usuarios de prueba listos:');
        $this->command?->table(['Rol', 'Correo', 'Contraseña'], [
            ['Administrador', $adminEmail, env('SEED_ADMIN_PASSWORD') ? '(la de tu .env)' : $adminPassword],
            ['Barbería demo', $demoEmail, env('SEED_DEMO_PASSWORD') ? '(la de tu .env)' : $demoPassword],
        ]);
    }
}
