<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('ruc', 11)->nullable()->unique();
            $table->string('razon_social');
            $table->string('contacto')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->timestamps();
        });

        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // 01 factura, 03 boleta, 00 otro (guía, nota de pedido)
            $table->string('tipo_documento', 2)->default('01');
            $table->string('numero_documento')->nullable();
            $table->date('fecha');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('metodo_pago')->default('efectivo');
            // Si se pagó con dinero de la caja abierta
            $table->boolean('pagado_desde_caja')->default(false);
            // registrada, anulada
            $table->string('estado')->default('registrada');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('compra_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->decimal('cantidad', 12, 2);
            // Costo unitario sin IGV
            $table->decimal('costo_unitario', 12, 4);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->foreignId('compra_id')->nullable()->after('comprobante_id')->constrained('compras')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_stock', fn (Blueprint $table) => $table->dropConstrainedForeignId('compra_id'));
        Schema::dropIfExists('compra_items');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('proveedores');
    }
};
