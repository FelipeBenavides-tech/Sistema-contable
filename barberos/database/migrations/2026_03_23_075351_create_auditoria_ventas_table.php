<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('barberia_id')->constrained('barberias')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('accion', ['editada', 'eliminada']);
            $table->string('motivo');
            $table->decimal('total_antes', 10, 2)->default(0);
            $table->decimal('total_despues', 10, 2)->nullable();
            $table->string('barbero_antes')->nullable();
            $table->string('metodo_pago_antes')->nullable();
            $table->string('metodo_pago_despues')->nullable();
            $table->timestamps();
        });

        // Agregar columnas a ventas para saber si fue editada
        Schema::table('ventas', function (Blueprint $table) {
            $table->boolean('fue_editada')->default(false);
            $table->boolean('fue_eliminada')->default(false);
            $table->decimal('total_original', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_ventas');
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['fue_editada', 'fue_eliminada', 'total_original']);
        });
    }
};
