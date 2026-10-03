<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Compra;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompraService
{
    /**
     * Registra una compra: suma stock, actualiza el costo de cada producto y, si se pagó
     * con la caja abierta, registra el egreso.
     *
     * @param  array{proveedor_id: int, tipo_documento: string, numero_documento?: string|null, fecha: string, metodo_pago: string,
     *               pagado_desde_caja?: bool, incluye_igv?: bool, observaciones?: string|null, user_id?: int|null,
     *               items: array<int, array{producto_id: int, cantidad: float|string, costo: float|string}>}  $datos
     */
    public function registrar(array $datos): Compra
    {
        $tasa = (float) config('sunat.igv');
        // Con factura el IGV es crédito fiscal y el costo se guarda sin IGV; con boleta u otro, el IGV es parte del costo
        $separaIgv = $datos['tipo_documento'] === '01';
        $incluyeIgv = $datos['incluye_igv'] ?? true;

        if (! empty($datos['pagado_desde_caja']) && ! Caja::abiertaDe($datos['user_id'] ?? null)) {
            throw ValidationException::withMessages(['pagado_desde_caja' => 'No tienes una caja abierta para pagar esta compra.']);
        }

        return DB::transaction(function () use ($datos, $tasa, $separaIgv, $incluyeIgv) {
            $compra = Compra::create([
                ...collect($datos)->only(['proveedor_id', 'tipo_documento', 'numero_documento', 'fecha', 'metodo_pago', 'observaciones', 'user_id'])->all(),
                'pagado_desde_caja' => ! empty($datos['pagado_desde_caja']),
            ]);

            $subtotal = 0.0;
            $total = 0.0;

            foreach ($datos['items'] as $linea) {
                $producto = Producto::lockForUpdate()->findOrFail($linea['producto_id']);
                $cantidad = round((float) $linea['cantidad'], 2);
                $costoIngresado = (float) $linea['costo'];

                // Precio pagado por unidad con IGV
                $precioConIgv = $separaIgv && ! $incluyeIgv ? $costoIngresado * (1 + $tasa) : $costoIngresado;
                $costoUnitario = $separaIgv ? $precioConIgv / (1 + $tasa) : $precioConIgv;
                $lineaTotal = round($cantidad * $precioConIgv, 2);

                $compra->items()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'costo_unitario' => round($costoUnitario, 4),
                    'total' => $lineaTotal,
                ]);

                $subtotal += $separaIgv ? $lineaTotal / (1 + $tasa) : $lineaTotal;
                $total += $lineaTotal;

                $producto->precio_compra = round($costoUnitario, 2);
                $producto->moverStock($cantidad, 'entrada', 'Compra '.$compra->descripcion(), null, $compra->id);
            }

            $compra->update([
                'subtotal' => round($subtotal, 2),
                'igv' => round($total - $subtotal, 2),
                'total' => round($total, 2),
            ]);

            if ($compra->pagado_desde_caja) {
                Caja::abiertaDe($compra->user_id)->movimientos()->create([
                    'tipo' => 'egreso',
                    'concepto' => 'Compra '.$compra->descripcion().' - '.$compra->proveedor->razon_social,
                    'monto' => $compra->total,
                    'metodo_pago' => $compra->metodo_pago,
                ]);
            }

            return $compra;
        });
    }

    /** Anula una compra registrada por error: descuenta el stock que había sumado. */
    public function anular(Compra $compra): void
    {
        if ($compra->estado === 'anulada') {
            return;
        }

        DB::transaction(function () use ($compra) {
            foreach ($compra->items as $item) {
                Producto::lockForUpdate()->find($item->producto_id)
                    ?->moverStock(-(float) $item->cantidad, 'salida', 'Anulación compra '.$compra->descripcion(), null, $compra->id);
            }
            $compra->update(['estado' => 'anulada']);
        });
    }
}
