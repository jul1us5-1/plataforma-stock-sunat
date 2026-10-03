<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Comprobante;
use App\Models\Producto;
use App\Models\Serie;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotaCreditoService
{
    public function __construct(private ComprobanteService $comprobantes, private SunatService $sunat) {}

    /**
     * Emite una nota de crédito sobre una factura o boleta aceptada por SUNAT.
     * Motivos 01 y 06 devuelven todo lo pendiente; 07 solo los ítems indicados.
     *
     * @param  array<int, float|string>  $cantidades  producto_id => cantidad (solo motivo 07)
     */
    public function emitir(Comprobante $referencia, string $motivo, string $descripcion, array $cantidades = [], ?int $userId = null): Comprobante
    {
        if (! in_array($referencia->tipo_comprobante, [Comprobante::FACTURA, Comprobante::BOLETA], true) || ! $referencia->anulable()) {
            throw ValidationException::withMessages(['comprobante' => 'Solo se emiten notas de crédito sobre facturas o boletas aceptadas por SUNAT.']);
        }
        if (! array_key_exists($motivo, Comprobante::MOTIVOS_NC)) {
            throw ValidationException::withMessages(['motivo_codigo' => 'Motivo no válido.']);
        }

        $referencia->loadMissing('items');
        $devolvibles = $referencia->cantidadesDevolvibles();
        $lineas = $motivo === '07'
            ? collect($cantidades)->map(fn ($c) => round((float) $c, 2))->filter(fn ($c) => $c > 0)
            : collect($devolvibles)->filter(fn ($c) => $c > 0);

        if ($lineas->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'No hay productos pendientes de devolver en este comprobante.']);
        }
        foreach ($lineas as $productoId => $cantidad) {
            if ($cantidad > ($devolvibles[$productoId] ?? 0)) {
                throw ValidationException::withMessages(['items' => 'La cantidad a devolver supera lo vendido.']);
            }
        }

        $serie = $referencia->tipo_comprobante === Comprobante::FACTURA ? 'FC01' : 'BC01';

        $nota = DB::transaction(function () use ($referencia, $motivo, $descripcion, $lineas, $serie, $userId) {
            $nota = Comprobante::create([
                'tipo_comprobante' => Comprobante::NOTA_CREDITO,
                'serie' => $serie,
                'correlativo' => Serie::siguienteCorrelativo($serie),
                'cliente_id' => $referencia->cliente_id,
                'user_id' => $userId,
                'comprobante_ref_id' => $referencia->id,
                'motivo_codigo' => $motivo,
                'motivo_descripcion' => $descripcion,
                'fecha_emision' => now(),
                'moneda' => $referencia->moneda,
                'metodo_pago' => $referencia->metodo_pago,
                'estado_sunat' => 'pendiente',
            ]);

            $totales = ['op_gravadas' => 0, 'op_exoneradas' => 0, 'op_inafectas' => 0, 'igv' => 0, 'total' => 0];

            foreach ($lineas as $productoId => $cantidad) {
                $original = $referencia->items->firstWhere('producto_id', $productoId);
                $item = $this->comprobantes->calcularLinea($original->afectacion_igv, $cantidad, (float) $original->precio_unitario);

                $nota->items()->create([
                    'producto_id' => $productoId,
                    'codigo' => $original->codigo,
                    'descripcion' => $original->descripcion,
                    'unidad_medida' => $original->unidad_medida,
                    'afectacion_igv' => $original->afectacion_igv,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $original->precio_unitario,
                    'costo_unitario' => $original->costo_unitario,
                    ...$item,
                ]);

                $clave = match ($original->afectacion_igv) {
                    '20' => 'op_exoneradas',
                    '30' => 'op_inafectas',
                    default => 'op_gravadas',
                };
                $totales[$clave] += $item['valor_venta'];
                $totales['igv'] += $item['igv'];
                $totales['total'] += $item['total'];

                $producto = Producto::lockForUpdate()->find($productoId);
                if ($producto && ! $producto->esServicio()) {
                    $producto->moverStock($cantidad, 'entrada', 'Devolución '.$nota->numero(), $nota->id);
                }
            }

            $nota->update(array_map(fn ($v) => round($v, 2), $totales));
            $this->registrarDevolucionEnCaja($nota, $userId);

            return $nota;
        });

        if (config('sunat.envio_automatico')) {
            $this->sunat->enviar($nota);
        }

        return $nota->fresh(['items', 'referencia']);
    }

    /**
     * Los recibos internos no van a SUNAT: se anulan directamente, devolviendo stock y dinero.
     */
    public function anularRecibo(Comprobante $recibo, string $motivo, ?int $userId = null): Comprobante
    {
        if ($recibo->tipo_comprobante !== Comprobante::RECIBO || $recibo->estado_sunat === 'anulado') {
            throw ValidationException::withMessages(['comprobante' => 'Este comprobante no se puede anular así.']);
        }

        DB::transaction(function () use ($recibo, $motivo, $userId) {
            $recibo->loadMissing('items');
            foreach ($recibo->items as $item) {
                $producto = Producto::lockForUpdate()->find($item->producto_id);
                if ($producto && ! $producto->esServicio()) {
                    $producto->moverStock((float) $item->cantidad, 'entrada', 'Anulación '.$recibo->numero(), $recibo->id);
                }
            }
            $recibo->update(['estado_sunat' => 'anulado', 'motivo_descripcion' => $motivo]);
            $this->registrarDevolucionEnCaja($recibo, $userId, 'Anulación ');
        });

        return $recibo->fresh();
    }

    private function registrarDevolucionEnCaja(Comprobante $comprobante, ?int $userId, string $prefijo = 'Devolución '): void
    {
        Caja::abiertaDe($userId)?->movimientos()->create([
            'tipo' => 'egreso',
            'concepto' => $prefijo.$comprobante->numero(),
            'monto' => $comprobante->total,
            'metodo_pago' => $comprobante->metodo_pago,
            'comprobante_id' => $comprobante->id,
        ]);
    }
}
