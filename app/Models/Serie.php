<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Serie extends Model
{
    protected $table = 'series';

    protected $fillable = ['tipo_comprobante', 'serie', 'correlativo', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /**
     * Reserva el siguiente correlativo con bloqueo de fila para evitar duplicados.
     * Debe llamarse dentro de una transacción.
     */
    public static function siguienteCorrelativo(string $serie): int
    {
        $registro = static::where('serie', $serie)->where('activo', true)->lockForUpdate()->firstOrFail();
        $registro->increment('correlativo');

        return $registro->correlativo;
    }
}
