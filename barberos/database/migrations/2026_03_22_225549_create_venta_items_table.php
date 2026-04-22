<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios');
            $table->string('nombre_servicio');
            $table->decimal('precio', 10, 2);
            $table->integer('cantidad')->default(1);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_items');
    }
};
