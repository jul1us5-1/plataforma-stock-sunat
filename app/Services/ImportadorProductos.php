<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Support\Facades\DB;

/**
 * Importa productos desde CSV. Columnas esperadas (con encabezado):
 * codigo, nombre, precio_venta, stock, [stock_minimo], [categoria], [unidad_medida], [afectacion_igv], [descripcion]
 * Si el código ya existe, actualiza los datos y ajusta el stock al valor del archivo.
 */
class ImportadorProductos
{
    /** @return array{creados: int, actualizados: int, errores: array<int, string>} */
    public function importar(string $ruta): array
    {
        $resultado = ['creados' => 0, 'actualizados' => 0, 'errores' => []];
        $archivo = fopen($ruta, 'r');

        $primera = fgets($archivo);
        $separador = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';
        $encabezados = array_map(
            fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h))),
            str_getcsv($primera, $separador, '"', '')
        );

        $fila = 1;
        while (($valores = fgetcsv($archivo, null, $separador, '"', '')) !== false) {
            $fila++;
            if (count(array_filter($valores, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $datos = array_combine($encabezados, array_pad(array_slice($valores, 0, count($encabezados)), count($encabezados), null));
            $codigo = trim($datos['codigo'] ?? '');
            $nombre = trim($datos['nombre'] ?? '');
            $precio = $this->numero($datos['precio_venta'] ?? $datos['precio'] ?? null);

            if ($codigo === '' || $nombre === '' || $precio === null) {
                $resultado['errores'][] = "Fila {$fila}: falta código, nombre o precio";

                continue;
            }

            DB::transaction(function () use ($datos, $codigo, $nombre, $precio, &$resultado) {
                $producto = Producto::firstOrNew(['codigo' => $codigo]);
                $existia = $producto->exists;

                $producto->fill(array_filter([
                    'nombre' => $nombre,
                    'precio_venta' => $precio,
                    'descripcion' => $datos['descripcion'] ?? null,
                    'categoria' => $datos['categoria'] ?? null,
                    'unidad_medida' => strtoupper(trim($datos['unidad_medida'] ?? '')) ?: null,
                    'afectacion_igv' => trim($datos['afectacion_igv'] ?? '') ?: null,
                    'stock_minimo' => $this->numero($datos['stock_minimo'] ?? null),
                ], fn ($v) => $v !== null && $v !== ''));
                $producto->save();

                $stock = $this->numero($datos['stock'] ?? null);
                if ($stock !== null && $stock != (float) $producto->stock) {
                    $producto->moverStock($stock - (float) $producto->stock, $existia ? 'ajuste' : 'entrada', 'Importación CSV');
                }

                $resultado[$existia ? 'actualizados' : 'creados']++;
            });
        }

        fclose($archivo);

        return $resultado;
    }

    private function numero(mixed $valor): ?float
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }
        $valor = str_replace(['S/', ' '], '', $valor);
        // Acepta 1.234,50 y 1,234.50
        if (preg_match('/,\d{1,2}$/', $valor)) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        } else {
            $valor = str_replace(',', '', $valor);
        }

        return is_numeric($valor) ? (float) $valor : null;
    }
}
