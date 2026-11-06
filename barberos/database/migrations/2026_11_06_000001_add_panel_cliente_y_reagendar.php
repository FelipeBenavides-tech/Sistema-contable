<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Enlace privado del panel del cliente: /mis-citas/{token}
            $table->string('token', 64)->nullable()->unique()->after('saldo_favor');
        });

        Schema::table('citas', function (Blueprint $table) {
            // Fecha, hora y barbero que tenía la cita antes de moverla
            $table->json('reagendada_desde')->nullable()->after('token');
            // barberia = la movió la barbería (el barbero no podía) | cliente = la movió el cliente
            $table->string('reagendada_por', 10)->nullable()->after('reagendada_desde');
            $table->timestamp('reagendada_en')->nullable()->after('reagendada_por');
            $table->string('reagendo_motivo', 250)->nullable()->after('reagendada_en');
            // pendiente = el cliente aún no dice si le sirve la nueva hora | aceptada
            $table->string('reagendo_respuesta', 10)->nullable()->after('reagendo_motivo');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn(['reagendada_desde', 'reagendada_por', 'reagendada_en', 'reagendo_motivo', 'reagendo_respuesta']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn('token');
        });
    }
};
