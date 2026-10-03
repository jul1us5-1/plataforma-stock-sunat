<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            // entrada, salida, ajuste
            $table->string('tipo');
            // Positivo suma stock, negativo resta
            $table->decimal('cantidad', 12, 2);
            $table->decimal('stock_resultante', 12, 2);
            $table->foreignId('comprobante_id')->nullable()->constrained('comprobantes')->nullOnDelete();
            $table->string('motivo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
