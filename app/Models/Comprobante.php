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
    public const NOTA_CREDITO = '07';

    public const TIPOS = [
        self::FACTURA => 'Factura electrónica',
        self::BOLETA => 'Boleta de venta electrónica',
        self::RECIBO => 'Recibo interno',
        self::NOTA_CREDITO => 'Nota de crédito electrónica',
    ];

    // Tipos que se eligen en la pantalla de venta
    public const TIPOS_VENTA = [self::FACTURA, self::BOLETA, self::RECIBO];

    // Catálogo 09 SUNAT: motivos de nota de crédito que maneja la plataforma
    public const MOTIVOS_NC = [
        '01' => 'Anulación de la operación',
        '06' => 'Devolución total',
        '07' => 'Devolución por ítem',
    ];

    // Motivos que devuelven los productos al stock
    public const MOTIVOS_CON_DEVOLUCION = ['01', '06', '07'];

    protected $fillable = [
        'tipo_comprobante', 'serie', 'correlativo', 'cliente_id', 'user_id', 'observaciones',
        'comprobante_ref_id', 'motivo_codigo', 'motivo_descripcion', 'fecha_emision', 'moneda', 'metodo_pago',
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

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function referencia(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_ref_id');
    }

    public function notasCredito(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'comprobante_ref_id');
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
        return in_array($this->tipo_comprobante, [self::FACTURA, self::BOLETA, self::NOTA_CREDITO], true);
    }

    public function esNotaCredito(): bool
    {
        return $this->tipo_comprobante === self::NOTA_CREDITO;
    }

    /** Se le puede emitir nota de crédito o anular (recibo) */
    public function anulable(): bool
    {
        if ($this->estado_sunat === 'anulado' || $this->esNotaCredito()) {
            return false;
        }
        if ($this->tipo_comprobante === self::RECIBO) {
            return true;
        }

        return in_array($this->estado_sunat, ['aceptado', 'observado'], true);
    }

    /**
     * Cantidad de cada producto que todavía se puede devolver (vendido menos lo ya devuelto con notas de crédito).
     *
     * @return array<int, float> producto_id => cantidad
     */
    public function cantidadesDevolvibles(): array
    {
        $vendidas = $this->items->groupBy('producto_id')->map(fn ($items) => (float) $items->sum('cantidad'));
        $devueltas = ComprobanteItem::whereIn('comprobante_id', $this->notasCredito()
            ->whereNotIn('estado_sunat', ['rechazado'])->whereIn('motivo_codigo', self::MOTIVOS_CON_DEVOLUCION)->select('id'))
            ->get()->groupBy('producto_id')->map(fn ($items) => (float) $items->sum('cantidad'));

        return $vendidas->map(fn ($cantidad, $id) => round($cantidad - ($devueltas[$id] ?? 0), 2))->all();
    }
}
