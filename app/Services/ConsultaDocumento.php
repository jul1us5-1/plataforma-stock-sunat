<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Consulta datos de un RUC o DNI en un servicio externo para autocompletar formularios.
 */
class ConsultaDocumento
{
    public function disponible(): bool
    {
        return filled(config('services.consulta_documentos.token'));
    }

    /**
     * @return array{razon_social: string, direccion: ?string, estado: ?string, condicion: ?string}|null
     */
    public function consultar(string $numero): ?array
    {
        if (! $this->disponible() || ! preg_match('/^(\d{8}|\d{11})$/', $numero)) {
            return null;
        }

        $ruta = strlen($numero) === 11 ? 'sunat/ruc' : 'reniec/dni';

        try {
            $respuesta = Http::withToken(config('services.consulta_documentos.token'))
                ->acceptJson()->timeout(8)
                ->get(rtrim(config('services.consulta_documentos.url'), '/')."/{$ruta}", ['numero' => $numero]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if (! $respuesta->successful()) {
            return null;
        }

        // Los proveedores usan nombres de campo distintos (snake_case o camelCase)
        $datos = collect($respuesta->json())->mapWithKeys(fn ($valor, $clave) => [Str::snake($clave) => $valor]);

        $nombre = $datos['razon_social'] ?? $datos['nombre_completo'] ?? $datos['full_name'] ?? $datos['nombre'] ?? null;
        if (! $nombre && isset($datos['nombres'])) {
            $nombre = trim(implode(' ', array_filter([
                $datos['apellido_paterno'] ?? $datos['first_last_name'] ?? null,
                $datos['apellido_materno'] ?? $datos['second_last_name'] ?? null,
                $datos['nombres'],
            ])));
        }
        if (! $nombre) {
            return null;
        }

        $direccion = $datos['direccion'] ?? null;
        if ($direccion && filled($datos['distrito'] ?? null)) {
            $direccion .= ', '.$datos['distrito'];
        }

        return [
            'razon_social' => trim($nombre),
            'direccion' => $direccion && trim($direccion, ' -,') !== '' ? trim($direccion) : null,
            'estado' => $datos['estado'] ?? null,
            'condicion' => $datos['condicion'] ?? null,
        ];
    }
}
