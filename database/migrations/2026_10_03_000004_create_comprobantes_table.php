<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_comprobante', 2);
            $table->string('serie', 4);
            $table->unsignedInteger('correlativo');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes');
            $table->dateTime('fecha_emision');
            $table->string('moneda', 3)->default('PEN');
            $table->decimal('op_gravadas', 12, 2)->default(0);
            $table->decimal('op_exoneradas', 12, 2)->default(0);
            $table->decimal('op_inafectas', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            // pendiente, aceptado, observado, rechazado, error, no_aplica, anulado
            $table->string('estado_sunat')->default('pendiente');
            $table->string('sunat_codigo')->nullable();
            $table->text('sunat_mensaje')->nullable();
            $table->string('hash')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->timestamps();

            $table->unique(['serie', 'correlativo']);
        });

        Schema::create('comprobante_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_id')->constrained('comprobantes')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos');
            $table->string('codigo');
            $table->string('descripcion');
            $table->string('unidad_medida', 3);
            $table->string('afectacion_igv', 2);
            $table->decimal('cantidad', 12, 2);
            // Valor unitario sin IGV y precio unitario con IGV
            $table->decimal('valor_unitario', 12, 6);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('valor_venta', 12, 2);
            $table->decimal('igv', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobante_items');
        Schema::dropIfExists('comprobantes');
    }
};
