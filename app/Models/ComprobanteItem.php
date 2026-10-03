<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComprobanteItem extends Model
{
    protected $fillable = [
        'comprobante_id', 'producto_id', 'codigo', 'descripcion', 'unidad_medida', 'afectacion_igv',
        'cantidad', 'valor_unitario', 'precio_unitario', 'costo_unitario', 'valor_venta', 'igv', 'total',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'valor_unitario' => 'decimal:6',
            'precio_unitario' => 'decimal:2',
            'valor_venta' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
