<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            // Catálogo 06 SUNAT: 0 sin documento, 1 DNI, 4 carnet extranjería, 6 RUC, 7 pasaporte
            $table->string('tipo_documento', 1);
            $table->string('numero_documento', 15);
            $table->string('razon_social');
            $table->string('direccion')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->timestamps();

            $table->unique(['tipo_documento', 'numero_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
