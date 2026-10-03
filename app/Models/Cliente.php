<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    public const TIPOS_DOCUMENTO = [
        '0' => 'Sin documento',
        '1' => 'DNI',
        '4' => 'Carnet de extranjería',
        '6' => 'RUC',
        '7' => 'Pasaporte',
    ];

    protected $fillable = [
        'tipo_documento', 'numero_documento', 'razon_social', 'direccion', 'email', 'telefono',
    ];

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class);
    }

    public function tieneRuc(): bool
    {
        return $this->tipo_documento === '6';
    }
}
