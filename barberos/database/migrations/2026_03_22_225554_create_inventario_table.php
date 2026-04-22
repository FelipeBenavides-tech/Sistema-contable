<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('categoria')->default('insumo'); // insumo | venta
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(3);
            $table->decimal('precio_costo', 10, 2)->default(0);
            $table->decimal('precio_venta', 10, 2)->default(0);
            $table->string('unidad')->default('unidad');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario');
    }
};
