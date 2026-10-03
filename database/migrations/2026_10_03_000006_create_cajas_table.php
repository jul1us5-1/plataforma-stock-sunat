<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('abierta_en');
            $table->decimal('monto_apertura', 12, 2)->default(0);
            $table->dateTime('cerrada_en')->nullable();
            // Efectivo que debería haber al cierre según los movimientos
            $table->decimal('efectivo_esperado', 12, 2)->nullable();
            // Efectivo contado por el cajero al cierre
            $table->decimal('efectivo_contado', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            // ingreso, egreso
            $table->string('tipo');
            $table->string('concepto');
            $table->decimal('monto', 12, 2);
            // efectivo, tarjeta, yape, plin, transferencia
            $table->string('metodo_pago')->default('efectivo');
            $table->foreignId('comprobante_id')->nullable()->constrained('comprobantes')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('comprobantes', function (Blueprint $table) {
            $table->string('metodo_pago')->default('efectivo')->after('moneda');
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes', fn (Blueprint $table) => $table->dropColumn('metodo_pago'));
        Schema::dropIfExists('movimientos_caja');
        Schema::dropIfExists('cajas');
    }
};
