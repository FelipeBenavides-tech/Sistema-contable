<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barberias', function (Blueprint $table) {
            // Dirección de la página pública: /reservar/{slug}
            $table->string('slug', 80)->nullable()->unique()->after('nombre');
            $table->boolean('reservas_activas')->default(false)->after('limite_fiado');
            // Porcentaje del valor de la cita que se paga al reservar (0 = sin anticipo)
            $table->unsignedTinyInteger('anticipo_porcentaje')->default(50)->after('reservas_activas');
            // Con al menos estas horas de aviso, el anticipo queda como saldo a favor
            $table->unsignedSmallInteger('horas_cancelacion')->default(4)->after('anticipo_porcentaje');
            // Llaves de la cuenta Wompi de la barbería (las secretas se guardan cifradas)
            $table->string('wompi_llave_publica')->nullable()->after('horas_cancelacion');
            $table->text('wompi_llave_privada')->nullable()->after('wompi_llave_publica');
            $table->text('wompi_secreto_eventos')->nullable()->after('wompi_llave_privada');
            $table->text('wompi_secreto_integridad')->nullable()->after('wompi_secreto_eventos');
        });

        Schema::table('clientes', function (Blueprint $table) {
            // Anticipos de citas canceladas a tiempo, que se usan en la siguiente
            $table->decimal('saldo_favor', 10, 2)->default(0)->after('acepta_mensajes');
        });

        Schema::table('citas', function (Blueprint $table) {
            // local = la agendó la barbería; en_linea = la reservó el cliente
            $table->string('origen', 10)->default('local')->after('estado');
            // Lo que el cliente pagó por adelantado (pasarela + saldo a favor)
            $table->decimal('anticipo', 10, 2)->default(0)->after('total');
            // Parte del anticipo que se cubrió con saldo a favor
            $table->decimal('saldo_favor_usado', 10, 2)->default(0)->after('anticipo');
            // pendiente | pagado | aplicado | saldo_favor | retenido
            $table->string('anticipo_estado', 15)->nullable()->after('saldo_favor_usado');
            // Mientras se paga, la hora queda apartada hasta este momento
            $table->timestamp('expira_en')->nullable()->after('anticipo_estado');
            // Enlace privado del cliente para ver o cancelar su cita
            $table->string('token', 64)->nullable()->unique()->after('expira_en');
        });

        Schema::table('ventas', function (Blueprint $table) {
            // Parte del total que ya estaba pagada (anticipo en línea o saldo a favor)
            $table->decimal('monto_prepagado', 10, 2)->default(0)->after('monto_nequi');
        });

        // Cada intento de pago en Wompi
        Schema::create('pagos_en_linea', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barberia_id')->constrained('barberias')->cascadeOnDelete();
            $table->foreignId('cita_id')->nullable()->constrained('citas')->nullOnDelete();
            $table->string('referencia', 64)->unique();
            $table->unsignedBigInteger('monto_centavos');
            // PENDING | APPROVED | DECLINED | VOIDED | ERROR (estados de Wompi)
            $table->string('estado', 10)->default('PENDING');
            $table->string('wompi_id')->nullable();
            $table->string('metodo')->nullable();
            $table->json('respuesta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_en_linea');

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('monto_prepagado');
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn(['origen', 'anticipo', 'saldo_favor_usado', 'anticipo_estado', 'expira_en', 'token']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('saldo_favor');
        });

        Schema::table('barberias', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'slug', 'reservas_activas', 'anticipo_porcentaje', 'horas_cancelacion',
                'wompi_llave_publica', 'wompi_llave_privada', 'wompi_secreto_eventos', 'wompi_secreto_integridad',
            ]);
        });
    }
};
