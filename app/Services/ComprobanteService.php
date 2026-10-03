<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Producto;
use App\Models\Serie;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComprobanteService
{
    public function __construct(private SunatService $sunat) {}

    /**
     * Emite un comprobante: reserva correlativo, calcula impuestos, descuenta stock
     * y, si corresponde, lo envía a SUNAT.
     *
     * @param  array{tipo_comprobante: string, serie: string, cliente_id?: int|null, items: array<int, array{producto_id: int, cantidad: float|string, precio_unitario?: float|string|null}>}  $datos
     */
    public function emitir(array $datos): Comprobante
    {
        $cliente = isset($datos['cliente_id']) ? Cliente::find($datos['cliente_id']) : null;
        $this->validarCliente($datos['tipo_comprobante'], $cliente);

        $comprobante = DB::transaction(function () use ($datos, $cliente) {
            $serie = Serie::where('serie', $datos['serie'])->firstOrFail();
            if ($serie->tipo_comprobante !== $datos['tipo_comprobante']) {
                throw ValidationException::withMessages(['serie' => 'La serie no corresponde al tipo de comprobante.']);
            }

            $comprobante = Comprobante::create([
                'tipo_comprobante' => $datos['tipo_comprobante'],
                'serie' => $serie->serie,
                'correlativo' => Serie::siguienteCorrelativo($serie->serie),
                'cliente_id' => $cliente?->id,
                'fecha_emision' => now(),
                'moneda' => 'PEN',
                'estado_sunat' => in_array($datos['tipo_comprobante'], [Comprobante::FACTURA, Comprobante::BOLETA], true)
                    ? 'pendiente' : 'no_aplica',
            ]);

            $totales = ['op_gravadas' => 0, 'op_exoneradas' => 0, 'op_inafectas' => 0, 'igv' => 0, 'total' => 0];

            foreach ($datos['items'] as $linea) {
                $producto = Producto::lockForUpdate()->findOrFail($linea['producto_id']);
                $cantidad = round((float) $linea['cantidad'], 2);
                $precio = round((float) ($linea['precio_unitario'] ?? $producto->precio_venta), 2);

                if (! $producto->esServicio() && (float) $producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente para {$producto->nombre} (disponible: {$producto->stock}).",
                    ]);
                }

                $item = $this->calcularLinea($producto->afectacion_igv, $cantidad, $precio);

                $comprobante->items()->create([
                    'producto_id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'descripcion' => $producto->nombre,
                    'unidad_medida' => $producto->unidad_medida,
                    'afectacion_igv' => $producto->afectacion_igv,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    ...$item,
                ]);

                $clave = match ($producto->afectacion_igv) {
                    '20' => 'op_exoneradas',
                    '30' => 'op_inafectas',
                    default => 'op_gravadas',
                };
                $totales[$clave] += $item['valor_venta'];
                $totales['igv'] += $item['igv'];
                $totales['total'] += $item['total'];

                if (! $producto->esServicio()) {
                    $producto->moverStock(-$cantidad, 'salida', 'Venta '.$comprobante->numero(), $comprobante->id);
                }
            }

            // SUNAT exige identificar al cliente en boletas mayores a S/ 700
            if ($datos['tipo_comprobante'] === Comprobante::BOLETA && $totales['total'] > 700
                && (! $cliente || $cliente->tipo_documento === '0')) {
                throw ValidationException::withMessages([
                    'cliente_id' => 'Las boletas mayores a S/ 700 requieren cliente con documento de identidad.',
                ]);
            }

            $comprobante->update(array_map(fn ($v) => round($v, 2), $totales));

            return $comprobante;
        });

        if ($comprobante->seEnviaASunat() && config('sunat.envio_automatico')) {
            $this->sunat->enviar($comprobante);
        }

        return $comprobante->fresh(['items', 'cliente']);
    }

    /**
     * Calcula valores de una línea a partir del precio con IGV incluido.
     *
     * @return array{valor_unitario: float, valor_venta: float, igv: float, total: float}
     */
    public function calcularLinea(string $afectacion, float $cantidad, float $precioUnitario): array
    {
        $tasa = $afectacion === '10' ? (float) config('sunat.igv') : 0.0;
        $total = round($cantidad * $precioUnitario, 2);
        $valorVenta = round($total / (1 + $tasa), 2);

        return [
            'valor_unitario' => round($precioUnitario / (1 + $tasa), 6),
            'valor_venta' => $valorVenta,
            'igv' => round($total - $valorVenta, 2),
            'total' => $total,
        ];
    }

    private function validarCliente(string $tipo, ?Cliente $cliente): void
    {
        if ($tipo === Comprobante::FACTURA && ! $cliente?->tieneRuc()) {
            throw ValidationException::withMessages(['cliente_id' => 'La factura requiere un cliente con RUC.']);
        }
    }
}
