<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barberias', function (Blueprint $table) {
            // base = caja, inventario, gastos, reportes, membresías y cartera
            // pro  = todo lo anterior + agenda de citas y usuarios para barberos
            $table->string('nivel', 10)->default('base')->after('plan');
            // Servicios que se le pueden fiar a un cliente antes de exigirle el pago total
            $table->unsignedInteger('limite_fiado')->default(4)->after('contrato_hasta');
        });

        Schema::table('users', function (Blueprint $table) {
            // dueno = ve todo el sistema de su barbería; barbero = solo su agenda
            $table->string('rol', 10)->default('dueno')->after('is_admin');
            $table->foreignId('barbero_id')->nullable()->after('barberia_id')
                ->constrained('barberos')->nullOnDelete();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('email')->nullable()->after('telefono');
            $table->date('fecha_nacimiento')->nullable()->after('email');
            $table->text('notas')->nullable()->after('fecha_nacimiento');
            // Se le puede fiar (cliente en cartera)
            $table->boolean('permite_fiado')->default(false)->after('notas');
            // Llegó al límite de servicios fiados: no se le fía más hasta que pague todo
            $table->boolean('fiado_bloqueado')->default(false)->after('permite_fiado');
            // Autorizó el tratamiento de datos y recibir mensajes (Ley 1581 de 2012)
            $table->boolean('acepta_mensajes')->default(false)->after('fiado_bloqueado');
            $table->index(['barberia_id', 'telefono']);
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->unsignedSmallInteger('duracion_minutos')->default(30)->after('precio');
        });

        Schema::table('ventas', function (Blueprint $table) {
            // Se deja como texto para poder agregar "fiado" (y otros métodos) sin
            // depender de la lista cerrada del enum original
            $table->string('metodo_pago', 20)->default('efectivo')->change();
            $table->foreignId('cliente_id')->nullable()->after('barbero_id')
                ->constrained('clientes')->nullOnDelete();
            // Lo que falta por pagar de una venta fiada
            $table->decimal('saldo_pendiente', 10, 2)->default(0)->after('monto_nequi');
        });

        // Pagos y abonos de los clientes a su cartera
        Schema::create('pagos_cartera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barberia_id')->constrained('barberias')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->decimal('valor', 10, 2);
            $table->string('metodo_pago', 20);
            $table->decimal('monto_efectivo', 10, 2)->default(0);
            $table->decimal('monto_nequi', 10, 2)->default(0);
            $table->date('fecha');
            $table->string('nota')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Días y horas en que trabaja cada barbero (0 = domingo … 6 = sábado)
        Schema::create('horarios_barbero', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbero_id')->constrained('barberos')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->string('hora_inicio', 5);
            $table->string('hora_fin', 5);
            $table->timestamps();
            $table->unique(['barbero_id', 'dia_semana']);
        });

        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barberia_id')->constrained('barberias')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('barbero_id')->constrained('barberos')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('hora_inicio', 5);
            $table->string('hora_fin', 5);
            // confirmada | atendida | cancelada | no_asistio
            $table->string('estado', 15)->default('confirmada');
            $table->decimal('total', 10, 2)->default(0);
            $table->string('notas')->nullable();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->foreignId('creada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['barbero_id', 'fecha']);
            $table->index(['barberia_id', 'fecha']);
        });

        // Servicios de cada cita. Copia nombre, precio y duración del momento en que se agendó
        Schema::create('cita_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
            $table->string('nombre');
            $table->decimal('precio', 10, 2);
            $table->unsignedSmallInteger('duracion_minutos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cita_servicios');
        Schema::dropIfExists('citas');
        Schema::dropIfExists('horarios_barbero');
        Schema::dropIfExists('pagos_cartera');

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn(['cliente_id', 'saldo_pendiente']);
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn('duracion_minutos');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['barberia_id', 'telefono']);
            $table->dropColumn(['email', 'fecha_nacimiento', 'notas', 'permite_fiado', 'fiado_bloqueado', 'acepta_mensajes']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['barbero_id']);
            $table->dropColumn(['rol', 'barbero_id']);
        });

        Schema::table('barberias', function (Blueprint $table) {
            $table->dropColumn(['nivel', 'limite_fiado']);
        });
    }
};
