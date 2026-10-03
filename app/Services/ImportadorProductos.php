<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Importa productos desde CSV o Excel (.xlsx), incluido el "Reporte de productos" de MYPEFACT.
 * Columnas reconocidas (con encabezado, en cualquier orden):
 * codigo, nombre, precio_venta, stock, [stock_minimo], [categoria], [unidad_medida], [afectacion_igv], [descripcion]
 * El encabezado puede estar en cualquiera de las primeras filas; se ignoran los títulos que haya encima.
 * Si el código ya existe, actualiza los datos y, si el archivo trae stock, lo ajusta a ese valor.
 */
class ImportadorProductos
{
    // Nombres alternativos de columnas => nombre interno
    private const ALIAS = [
        'codigo interno' => 'codigo',
        'cod' => 'codigo',
        'producto' => 'nombre',
        'precio' => 'precio_venta',
        'precio venta' => 'precio_venta',
        'precio compra' => 'precio_compra',
        'costo' => 'precio_compra',
        'precio unitario' => 'precio_venta',
        'unidad de medida' => 'unidad_medida',
        'unidad' => 'unidad_medida',
        'posee igv' => 'posee_igv',
        'stock actual' => 'stock',
        'cantidad' => 'stock',
        'stock minimo' => 'stock_minimo',
    ];

    /** @return array{creados: int, actualizados: int, errores: array<int, string>} */
    public function importar(string $ruta, ?string $nombreOriginal = null): array
    {
        $extension = strtolower(pathinfo($nombreOriginal ?? $ruta, PATHINFO_EXTENSION));
        $filas = $extension === 'xlsx' ? $this->filasXlsx($ruta) : $this->filasCsv($ruta);

        $resultado = ['creados' => 0, 'actualizados' => 0, 'errores' => []];
        $encabezados = null;
        $numero = 0;

        foreach ($filas as $valores) {
            $numero++;
            $valores = array_map(fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : trim((string) $v), $valores);
            if (count(array_filter($valores, fn ($v) => $v !== '')) === 0) {
                continue;
            }

            if ($encabezados === null) {
                $normalizados = array_map([$this, 'normalizarEncabezado'], $valores);
                if (in_array('codigo', $normalizados, true)) {
                    $encabezados = $normalizados;
                }

                continue;
            }

            $datos = array_combine($encabezados, array_pad(array_slice($valores, 0, count($encabezados)), count($encabezados), ''));
            $this->importarFila($datos, $numero, $resultado);
        }

        if ($encabezados === null) {
            $resultado['errores'][] = 'No se encontró una fila de encabezados con la columna "codigo".';
        }

        return $resultado;
    }

    private function importarFila(array $datos, int $numero, array &$resultado): void
    {
        $codigo = $this->codigo($datos['codigo'] ?? '');
        // MYPEFACT deja "Nombre" vacío y guarda el nombre en "Descripción"
        $nombre = ($datos['nombre'] ?? '') !== '' ? $datos['nombre'] : ($datos['descripcion'] ?? '');
        $precio = $this->numero($datos['precio_venta'] ?? null);

        if ($codigo === '' && $nombre === '') {
            return;
        }
        if ($codigo === '' || $nombre === '' || $precio === null) {
            $resultado['errores'][] = "Fila {$numero}: falta código, nombre o precio";

            return;
        }

        $afectacion = $datos['afectacion_igv'] ?? '';
        if ($afectacion === '' && ($datos['posee_igv'] ?? '') !== '') {
            $afectacion = in_array(strtolower($datos['posee_igv']), ['1', 'si', 'sí', 'true'], true) ? '10' : '20';
        }

        DB::transaction(function () use ($datos, $codigo, $nombre, $precio, $afectacion, &$resultado) {
            $producto = Producto::firstOrNew(['codigo' => $codigo]);
            $existia = $producto->exists;

            $producto->fill(array_filter([
                'nombre' => Str::limit($nombre, 255, ''),
                'precio_venta' => $precio,
                'precio_compra' => $this->numero($datos['precio_compra'] ?? null),
                'descripcion' => ($datos['nombre'] ?? '') !== '' ? ($datos['descripcion'] ?? null) : null,
                'categoria' => $datos['categoria'] ?? null,
                'unidad_medida' => strtoupper($datos['unidad_medida'] ?? '') ?: null,
                'afectacion_igv' => $afectacion ?: null,
                'stock_minimo' => $this->numero($datos['stock_minimo'] ?? null),
            ], fn ($v) => $v !== null && $v !== ''));
            $producto->save();

            $stock = $this->numero($datos['stock'] ?? null);
            if ($stock !== null && $stock != (float) $producto->stock) {
                $producto->moverStock($stock - (float) $producto->stock, $existia ? 'ajuste' : 'entrada', 'Importación');
            }

            $resultado[$existia ? 'actualizados' : 'creados']++;
        });
    }

    private function filasCsv(string $ruta): \Generator
    {
        $archivo = fopen($ruta, 'r');
        $primera = (string) fgets($archivo);
        $separador = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';
        rewind($archivo);

        while (($valores = fgetcsv($archivo, null, $separador, '"', '')) !== false) {
            yield $valores;
        }

        fclose($archivo);
    }

    private function filasXlsx(string $ruta): \Generator
    {
        $reader = new XlsxReader;
        $reader->open($ruta);

        foreach ($reader->getSheetIterator() as $hoja) {
            foreach ($hoja->getRowIterator() as $fila) {
                yield $fila->toArray();
            }
            break; // solo la primera hoja
        }

        $reader->close();
    }

    private function normalizarEncabezado(string $texto): string
    {
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
        $texto = strtolower(trim(Str::ascii($texto)));
        $texto = preg_replace('/\s+/', ' ', str_replace('_', ' ', $texto));

        return self::ALIAS[$texto] ?? str_replace(' ', '_', $texto);
    }

    // Excel puede devolver el código como número (100566 o 3.0)
    private function codigo(string $valor): string
    {
        return preg_match('/^\d+\.0+$/', $valor) ? (string) (int) $valor : $valor;
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
