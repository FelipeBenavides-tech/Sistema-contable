<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barberia_id')->constrained('barberias')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('telefono', 30)->nullable();
            $table->timestamps();
        });

        // Planes que vende cada barbería, por ejemplo "Mensual 5 cortes"
        Schema::create('planes_membresia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barberia_id')->constrained('barberias')->cascadeOnDelete();
            $table->string('nombre');
            $table->decimal('precio', 10, 2);
            $table->unsignedInteger('visitas');
            $table->unsignedInteger('duracion_dias');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Membresía comprada por un cliente. Copia los datos del plan para que
        // cambiar el plan después no altere las membresías ya vendidas.
        Schema::create('membresias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barberia_id')->constrained('barberias')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('plan_membresia_id')->nullable()->constrained('planes_membresia')->nullOnDelete();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->string('nombre_plan');
            $table->decimal('precio', 10, 2);
            $table->unsignedInteger('visitas_total');
            $table->unsignedInteger('visitas_usadas')->default(0);
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento');
            $table->boolean('anulada')->default(false);
            $table->timestamps();
        });

        Schema::table('venta_items', function (Blueprint $table) {
            // Membresía vendida o usada en este ítem
            $table->foreignId('membresia_id')->nullable()->after('inventario_id')
                ->constrained('membresias')->nullOnDelete();
            // true = el ítem es la venta de una membresía (no genera comisión)
            $table->boolean('es_membresia')->default(false)->after('es_producto');
            // true = servicio pagado con una visita de membresía (subtotal 0)
            $table->boolean('cubierto_membresia')->default(false)->after('es_membresia');
        });
    }

    public function down(): void
    {
        Schema::table('venta_items', function (Blueprint $table) {
            $table->dropForeign(['membresia_id']);
            $table->dropColumn(['membresia_id', 'es_membresia', 'cubierto_membresia']);
        });

        Schema::dropIfExists('membresias');
        Schema::dropIfExists('planes_membresia');
        Schema::dropIfExists('clientes');
    }
};
