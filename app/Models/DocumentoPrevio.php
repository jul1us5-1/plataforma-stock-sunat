<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cotización o pedido: se registra antes de la venta y luego se convierte en comprobante.
 */
class DocumentoPrevio extends Model
{
    protected $table = 'documentos_previos';

    public const TIPOS = [
        'cotizacion' => ['nombre' => 'Cotización', 'plural' => 'Cotizaciones', 'prefijo' => 'COT', 'limite' => 'Válida hasta', 'ruta' => 'cotizaciones'],
        'pedido' => ['nombre' => 'Pedido', 'plural' => 'Pedidos', 'prefijo' => 'PED', 'limite' => 'Fecha de entrega', 'ruta' => 'pedidos'],
    ];

    protected $fillable = [
        'tipo', 'numero', 'cliente_id', 'user_id', 'fecha', 'fecha_limite', 'estado', 'comprobante_id', 'total', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_limite' => 'date',
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

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentoPrevioItem::class);
    }

    /** Tipo interno a partir del segmento de la URL (cotizaciones, pedidos) */
    public static function tipoDeRuta(string $ruta): string
    {
        return collect(self::TIPOS)->search(fn ($t) => $t['ruta'] === $ruta) ?: abort(404);
    }

    public function ruta(): string
    {
        return self::TIPOS[$this->tipo]['ruta'];
    }

    public function nombreTipo(): string
    {
        return self::TIPOS[$this->tipo]['nombre'];
    }

    public function codigo(): string
    {
        return self::TIPOS[$this->tipo]['prefijo'].'-'.str_pad((string) $this->numero, 5, '0', STR_PAD_LEFT);
    }

    public function pendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public static function siguienteNumero(string $tipo): int
    {
        return (int) static::where('tipo', $tipo)->lockForUpdate()->max('numero') + 1;
    }
}
