<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_items', function (Blueprint $table) {
            // Producto del inventario vendido (null si es un servicio)
            $table->foreignId('inventario_id')->nullable()->after('servicio_id')
                ->constrained('inventario')->nullOnDelete();
            // true = producto (no genera comisión), false = servicio
            $table->boolean('es_producto')->default(false)->after('inventario_id');
        });

        Schema::table('ventas', function (Blueprint $table) {
            // Evita devolver el stock de una venta eliminada más de una vez
            $table->boolean('inventario_restaurado')->default(false);
        });

        // Ventas antiguas: los productos se guardaban sin servicio_id.
        DB::table('venta_items')->whereNull('servicio_id')->update(['es_producto' => true]);

        $items = DB::table('venta_items')
            ->join('ventas', 'ventas.id', '=', 'venta_items.venta_id')
            ->whereNull('venta_items.servicio_id')
            ->select('venta_items.id', 'venta_items.nombre_servicio', 'ventas.barberia_id')
            ->get();

        foreach ($items as $item) {
            $inventarioId = DB::table('inventario')
                ->where('barberia_id', $item->barberia_id)
                ->where('nombre', $item->nombre_servicio)
                ->value('id');

            if ($inventarioId) {
                DB::table('venta_items')->where('id', $item->id)->update(['inventario_id' => $inventarioId]);
            }
        }

        // Las ventas eliminadas no deben seguir sumando efectivo ni Nequi.
        DB::table('ventas')->where('fue_eliminada', true)->update([
            'monto_efectivo' => 0,
            'monto_nequi'    => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('venta_items', function (Blueprint $table) {
            $table->dropForeign(['inventario_id']);
            $table->dropColumn(['inventario_id', 'es_producto']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('inventario_restaurado');
        });
    }
};
