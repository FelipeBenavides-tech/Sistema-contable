<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->string('concepto');
            $table->string('categoria')->default('operativo');
            $table->decimal('valor', 10, 2);
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'tarjeta'])->default('efectivo');
            $table->date('fecha');
            $table->string('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
