<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barberos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('telefono')->nullable();
            $table->decimal('comision_porcentaje', 5, 2)->default(50.00);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barberos');
    }
};
