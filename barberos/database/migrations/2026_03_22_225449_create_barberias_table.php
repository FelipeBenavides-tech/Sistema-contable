<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barberias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('propietario')->nullable();
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();
            $table->boolean('activo')->default(true);
            $table->date('fecha_vencimiento')->nullable();
            $table->string('plan')->default('mensual'); // mensual | semestral | anual
            $table->timestamps();
        });

        // Agregar barberia_id a la tabla users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('barberia_id')->nullable()->constrained('barberias')->nullOnDelete();
            $table->boolean('is_admin')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['barberia_id']);
            $table->dropColumn(['barberia_id', 'is_admin']);
        });
        Schema::dropIfExists('barberias');
    }
};
