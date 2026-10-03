<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            // Vendedor que emitió el comprobante
            $table->foreignId('user_id')->nullable()->after('cliente_id')->constrained('users')->nullOnDelete();
            $table->text('observaciones')->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('observaciones');
        });
    }
};
