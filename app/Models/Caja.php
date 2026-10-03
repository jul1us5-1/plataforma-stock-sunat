<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    public const METODOS_PAGO = [
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'yape' => 'Yape',
        'plin' => 'Plin',
        'transferencia' => 'Transferencia',
    ];

    protected $fillable = [
        'user_id', 'abierta_en', 'monto_apertura', 'cerrada_en',
        'efectivo_esperado', 'efectivo_contado', 'diferencia', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'abierta_en' => 'datetime',
            'cerrada_en' => 'datetime',
            'monto_apertura' => 'decimal:2',
            'efectivo_esperado' => 'decimal:2',
            'efectivo_contado' => 'decimal:2',
            'diferencia' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class);
    }

    public static function abiertaDe(?int $userId): ?self
    {
        return $userId ? static::where('user_id', $userId)->whereNull('cerrada_en')->latest('id')->first() : null;
    }

    public function estaAbierta(): bool
    {
        return $this->cerrada_en === null;
    }

    public function efectivoEsperado(): float
    {
        $efectivo = $this->movimientos()->where('metodo_pago', 'efectivo');

        return round((float) $this->monto_apertura
            + (float) (clone $efectivo)->where('tipo', 'ingreso')->sum('monto')
            - (float) (clone $efectivo)->where('tipo', 'egreso')->sum('monto'), 2);
    }

    /** @return array<string, array{ingresos: float, egresos: float}> */
    public function resumenPorMetodo(): array
    {
        $resumen = [];
        foreach ($this->movimientos()->selectRaw('metodo_pago, tipo, sum(monto) as total')->groupBy('metodo_pago', 'tipo')->get() as $fila) {
            $resumen[$fila->metodo_pago] ??= ['ingresos' => 0.0, 'egresos' => 0.0];
            $resumen[$fila->metodo_pago][$fila->tipo === 'ingreso' ? 'ingresos' : 'egresos'] = (float) $fila->total;
        }

        return $resumen;
    }

    public function cerrar(float $efectivoContado, ?string $observaciones = null): void
    {
        $esperado = $this->efectivoEsperado();
        $this->update([
            'cerrada_en' => now(),
            'efectivo_esperado' => $esperado,
            'efectivo_contado' => $efectivoContado,
            'diferencia' => round($efectivoContado - $esperado, 2),
            'observaciones' => $observaciones,
        ]);
    }
}
