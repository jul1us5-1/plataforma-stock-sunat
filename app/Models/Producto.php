<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo', 'nombre', 'descripcion', 'categoria', 'unidad_medida',
        'precio_venta', 'precio_compra', 'afectacion_igv', 'stock', 'stock_minimo', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio_venta' => 'decimal:2',
            'precio_compra' => 'decimal:2',
            'stock' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoStock::class);
    }

    public function esServicio(): bool
    {
        return $this->unidad_medida === 'ZZ';
    }

    public function stockBajo(): bool
    {
        return ! $this->esServicio() && (float) $this->stock <= (float) $this->stock_minimo;
    }

    /**
     * Registra un movimiento de stock y actualiza el saldo del producto.
     * Debe llamarse dentro de una transacción.
     */
    public function moverStock(float $cantidad, string $tipo, ?string $motivo = null, ?int $comprobanteId = null): MovimientoStock
    {
        $this->stock = round((float) $this->stock + $cantidad, 2);
        $this->save();

        return $this->movimientos()->create([
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'stock_resultante' => $this->stock,
            'motivo' => $motivo,
            'comprobante_id' => $comprobanteId,
        ]);
    }
}
