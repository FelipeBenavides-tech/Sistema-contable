<?php

namespace Database\Seeders;

use App\Models\Barberia;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin del SaaS (tú) ──────────────────────────────────────────────
        User::create([
            'name'         => 'Admin SaaS',
            'email'        => 'admin@barberos.com',
            'password'     => bcrypt('admin123'),
            'is_admin'     => true,
            'barberia_id'  => null,
        ]);

        // ── Barbería de prueba ───────────────────────────────────────────────
        $barberia = Barberia::create([
            'nombre'            => 'Barbería El Estilo',
            'propietario'       => 'Carlos Mendoza',
            'telefono'          => '3001234567',
            'direccion'         => 'Calle 10 # 5-20, Cali',
            'activo'            => true,
            'plan'              => 'mensual',
            'fecha_vencimiento' => now()->addMonth(),
        ]);

        // ── Usuario de la barbería de prueba ─────────────────────────────────
        User::create([
            'name'        => 'Carlos Mendoza',
            'email'       => 'barberia@demo.com',
            'password'    => bcrypt('demo123'),
            'is_admin'    => false,
            'barberia_id' => $barberia->id,
        ]);
    }
}
