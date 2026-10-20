<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barberias', function (Blueprint $table) {
            // Plan por uso: saldo prepagado del que se descuenta el cobro de cada venta
            $table->decimal('saldo', 12, 2)->default(0)->after('plan');
            // Plan mensual: fecha hasta la que va el contrato mínimo
            $table->date('contrato_hasta')->nullable()->after('saldo');
        });

        // Cada pago o recarga que la barbería le hace a Kaixa
        Schema::create('pagos_suscripcion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barberia_id')->constrained('barberias')->cascadeOnDelete();
            $table->string('plan');
            $table->decimal('valor', 12, 2);
            $table->unsignedInteger('meses')->nullable();
            $table->date('fecha');
            $table->date('cubre_desde')->nullable();
            $table->date('cubre_hasta')->nullable();
            $table->string('metodo_pago')->default('transferencia');
            $table->string('nota')->nullable();
            // Gasto que se creó en la contabilidad de la barbería
            $table->foreignId('gasto_id')->nullable()->constrained('gastos')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('ventas', function (Blueprint $table) {
            // Lo que Kaixa cobró por esta venta en el plan por uso
            $table->decimal('cargo_plataforma', 10, 2)->default(0)->after('ganancia_local');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('cargo_plataforma');
        });

        Schema::dropIfExists('pagos_suscripcion');

        Schema::table('barberias', function (Blueprint $table) {
            $table->dropColumn(['saldo', 'contrato_hasta']);
        });
    }
};
