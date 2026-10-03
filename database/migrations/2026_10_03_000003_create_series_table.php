<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            // 01 factura, 03 boleta, RI recibo interno (nota de venta, no se envía a SUNAT)
            $table->string('tipo_comprobante', 2);
            $table->string('serie', 4)->unique();
            $table->unsignedInteger('correlativo')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
