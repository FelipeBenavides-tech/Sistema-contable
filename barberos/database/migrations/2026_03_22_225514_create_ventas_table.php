<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbero_id')->constrained('barberos');
            $table->decimal('total', 10, 2);
            $table->decimal('comision_barbero', 10, 2);
            $table->decimal('ganancia_local', 10, 2);
            $table->enum('metodo_pago', ['efectivo', 'nequi', 'transferencia', 'combinado']);
            $table->decimal('monto_efectivo', 10, 2)->default(0);
            $table->decimal('monto_nequi', 10, 2)->default(0);
            $table->date('fecha');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
