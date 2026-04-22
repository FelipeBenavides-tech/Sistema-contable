<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablas = [
            'barberos',
            'servicios',
            'ventas',
            'inventario',
            'gastos',
        ];

        foreach ($tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('barberia_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('barberias')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tablas = [
            'barberos',
            'servicios',
            'ventas',
            'inventario',
            'gastos',
        ];

        foreach ($tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['barberia_id']);
                $table->dropColumn('barberia_id');
            });
        }
    }
};
