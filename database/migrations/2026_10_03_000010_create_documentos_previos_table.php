<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cotizaciones y pedidos: documentos que todavía no son venta
        Schema::create('documentos_previos', function (Blueprint $table) {
            $table->id();
            // cotizacion, pedido
            $table->string('tipo');
            $table->unsignedInteger('numero');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            // Validez de la cotización o fecha de entrega del pedido
            $table->date('fecha_limite')->nullable();
            // pendiente, convertido, anulado
            $table->string('estado')->default('pendiente');
            $table->foreignId('comprobante_id')->nullable()->constrained('comprobantes')->nullOnDelete();
            $table->decimal('total', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['tipo', 'numero']);
        });

        Schema::create('documento_previo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_previo_id')->constrained('documentos_previos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->string('descripcion');
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_previo_items');
        Schema::dropIfExists('documentos_previos');
    }
};
