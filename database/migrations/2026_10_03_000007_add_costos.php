<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Costo de compra unitario, para calcular utilidad
            $table->decimal('precio_compra', 12, 2)->nullable()->after('precio_venta');
        });

        Schema::table('comprobante_items', function (Blueprint $table) {
            // Costo del producto al momento de la venta
            $table->decimal('costo_unitario', 12, 2)->nullable()->after('precio_unitario');
        });
    }

    public function down(): void
    {
        Schema::table('comprobante_items', fn (Blueprint $table) => $table->dropColumn('costo_unitario'));
        Schema::table('productos', fn (Blueprint $table) => $table->dropColumn('precio_compra'));
    }
};
