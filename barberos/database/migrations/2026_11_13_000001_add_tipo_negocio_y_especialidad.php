<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barberias', function (Blueprint $table) {
            // barberia | salon (salón de belleza) | agenda (solo agendamiento de citas)
            $table->string('tipo_negocio', 20)->default('barberia')->after('nombre');
        });

        Schema::table('barberos', function (Blueprint $table) {
            // En salones: estilista, manicurista u otra especialidad
            $table->string('especialidad', 60)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('barberos', function (Blueprint $table) {
            $table->dropColumn('especialidad');
        });

        Schema::table('barberias', function (Blueprint $table) {
            $table->dropColumn('tipo_negocio');
        });
    }
};
