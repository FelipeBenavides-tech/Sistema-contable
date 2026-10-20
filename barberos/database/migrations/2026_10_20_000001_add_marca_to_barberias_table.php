<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barberias', function (Blueprint $table) {
            $table->string('nit')->nullable()->after('nombre');
            $table->string('logo')->nullable()->after('direccion'); // ruta en el disco local
        });
    }

    public function down(): void
    {
        Schema::table('barberias', function (Blueprint $table) {
            $table->dropColumn(['nit', 'logo']);
        });
    }
};
