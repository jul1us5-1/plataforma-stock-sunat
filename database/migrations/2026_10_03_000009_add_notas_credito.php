<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            // Comprobante al que afecta una nota de crédito
            $table->foreignId('comprobante_ref_id')->nullable()->after('user_id')->constrained('comprobantes')->nullOnDelete();
            // Catálogo 09 SUNAT (motivo de la nota de crédito)
            $table->string('motivo_codigo', 2)->nullable()->after('comprobante_ref_id');
            $table->string('motivo_descripcion')->nullable()->after('motivo_codigo');
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('comprobante_ref_id');
            $table->dropColumn(['motivo_codigo', 'motivo_descripcion']);
        });
    }
};
