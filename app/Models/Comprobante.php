<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprobante extends Model
{
    public const FACTURA = '01';
    public const BOLETA = '03';
    public const RECIBO = 'RI';

    public const TIPOS = [
        self::FACTURA => 'Factura electrónica',
        self::BOLETA => 'Boleta de venta electrónica',
        self::RECIBO => 'Recibo interno',
    ];

    protected $fillable = [
        'tipo_comprobante', 'serie', 'correlativo', 'cliente_id', 'fecha_emision', 'moneda',
        'op_gravadas', 'op_exoneradas', 'op_inafectas', 'igv', 'total',
        'estado_sunat', 'sunat_codigo', 'sunat_mensaje', 'hash', 'xml_path', 'cdr_path',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'datetime',
            'op_gravadas' => 'decimal:2',
            'op_exoneradas' => 'decimal:2',
            'op_inafectas' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComprobanteItem::class);
    }

    public function numero(): string
    {
        return $this->serie.'-'.str_pad((string) $this->correlativo, 8, '0', STR_PAD_LEFT);
    }

    public function nombreTipo(): string
    {
        return self::TIPOS[$this->tipo_comprobante] ?? $this->tipo_comprobante;
    }

    public function seEnviaASunat(): bool
    {
        return in_array($this->tipo_comprobante, [self::FACTURA, self::BOLETA], true);
    }
}
