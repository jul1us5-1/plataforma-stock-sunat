<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('categoria')->nullable();
            // Catálogo 03 SUNAT: NIU = unidad (bienes), ZZ = servicio
            $table->string('unidad_medida', 3)->default('NIU');
            // Precio de venta al público, IGV incluido
            $table->decimal('precio_venta', 12, 2);
            // Catálogo 07 SUNAT: 10 gravado, 20 exonerado, 30 inafecto
            $table->string('afectacion_igv', 2)->default('10');
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('stock_minimo', 12, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
