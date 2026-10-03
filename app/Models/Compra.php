<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    public const TIPOS_DOCUMENTO = ['01' => 'Factura', '03' => 'Boleta', '00' => 'Otro'];

    protected $fillable = [
        'proveedor_id', 'user_id', 'tipo_documento', 'numero_documento', 'fecha', 'subtotal', 'igv', 'total',
        'metodo_pago', 'pagado_desde_caja', 'estado', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'subtotal' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
            'pagado_desde_caja' => 'boolean',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompraItem::class);
    }

    public function descripcion(): string
    {
        return trim((self::TIPOS_DOCUMENTO[$this->tipo_documento] ?? '').' '.$this->numero_documento) ?: 'Compra #'.$this->id;
    }
}
