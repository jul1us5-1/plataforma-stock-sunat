<?php

namespace App\Services;

use App\Models\Comprobante;
use App\Models\ComprobanteItem;
use App\Models\Producto;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    // Estados que no cuentan como venta
    private const EXCLUIDOS = ['anulado', 'rechazado'];

    // Las notas de crédito restan
    private const SIGNO = "(CASE WHEN tipo_comprobante = '07' THEN -1 ELSE 1 END)";

    public function comprobantes(CarbonInterface $desde, CarbonInterface $hasta): Builder
    {
        return Comprobante::query()
            ->whereBetween('fecha_emision', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->whereNotIn('estado_sunat', self::EXCLUIDOS);
    }

    /** @return array{cpe: int, monto_cpe: float, monto_recibos: float, total: float, costo: float, utilidad: float, items_sin_costo: int} */
    public function resumen(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $base = $this->comprobantes($desde, $hasta);
        $cpe = (clone $base)->whereIn('tipo_comprobante', [Comprobante::FACTURA, Comprobante::BOLETA, Comprobante::NOTA_CREDITO]);

        $ventas = ComprobanteItem::whereIn('comprobante_id', (clone $base)->where('tipo_comprobante', '!=', Comprobante::NOTA_CREDITO)->select('id'));
        $devoluciones = ComprobanteItem::whereIn('comprobante_id', (clone $base)->where('tipo_comprobante', Comprobante::NOTA_CREDITO)->select('id'));
        // La utilidad se calcula sin IGV: valor de venta menos costo (el costo se asume sin IGV)
        $utilidad = fn ($items) => (float) (clone $items)->whereNotNull('costo_unitario')->sum(DB::raw('valor_venta - cantidad * costo_unitario'));
        $costo = fn ($items) => (float) (clone $items)->whereNotNull('costo_unitario')->sum(DB::raw('cantidad * costo_unitario'));

        $montoCpe = (float) (clone $cpe)->sum(DB::raw('total * '.self::SIGNO));
        $total = (float) (clone $base)->sum(DB::raw('total * '.self::SIGNO));

        return [
            'cpe' => (clone $cpe)->where('tipo_comprobante', '!=', Comprobante::NOTA_CREDITO)->count(),
            'monto_cpe' => $montoCpe,
            'monto_recibos' => round($total - $montoCpe, 2),
            'total' => $total,
            'costo' => round($costo($ventas) - $costo($devoluciones), 2),
            'utilidad' => round($utilidad($ventas) - $utilidad($devoluciones), 2),
            'items_sin_costo' => (clone $ventas)->whereNull('costo_unitario')->count(),
        ];
    }

    /** Totales por día (o por hora si el rango es un solo día). */
    public function serie(CarbonInterface $desde, CarbonInterface $hasta): Collection
    {
        $porHora = $desde->isSameDay($hasta);
        $comprobantes = $this->comprobantes($desde, $hasta)->get(['fecha_emision', 'tipo_comprobante', 'total']);

        $claves = $porHora
            ? collect(range(0, 23))->map(fn ($h) => sprintf('%02dh', $h))
            : collect(\Carbon\CarbonPeriod::create($desde->copy()->startOfDay(), $hasta->copy()->startOfDay()))->map(fn ($d) => $d->format('d/m'));

        $vacio = $claves->mapWithKeys(fn ($k) => [$k => ['cpe' => 0.0, 'recibos' => 0.0]]);

        return $comprobantes->reduce(function ($acc, $c) use ($porHora) {
            $clave = $porHora ? $c->fecha_emision->format('H').'h' : $c->fecha_emision->format('d/m');
            $tipo = $c->seEnviaASunat() ? 'cpe' : 'recibos';
            $fila = $acc[$clave];
            $fila[$tipo] += $c->esNotaCredito() ? -(float) $c->total : (float) $c->total;
            $acc[$clave] = $fila;

            return $acc;
        }, $vacio);
    }

    public function porMetodoPago(CarbonInterface $desde, CarbonInterface $hasta): Collection
    {
        return $this->comprobantes($desde, $hasta)
            ->selectRaw('metodo_pago, sum(CASE WHEN tipo_comprobante = \'07\' THEN 0 ELSE 1 END) as cantidad, sum(total * '.self::SIGNO.') as total')
            ->groupBy('metodo_pago')->orderByDesc('total')->get();
    }

    public function porTipo(CarbonInterface $desde, CarbonInterface $hasta): Collection
    {
        return $this->comprobantes($desde, $hasta)
            ->selectRaw('tipo_comprobante, count(*) as cantidad, sum((op_gravadas + op_exoneradas + op_inafectas) * '.self::SIGNO.') as valor, sum(igv * '.self::SIGNO.') as igv, sum(total * '.self::SIGNO.') as total')
            ->groupBy('tipo_comprobante')->get();
    }

    public function productosVendidos(CarbonInterface $desde, CarbonInterface $hasta, int $limite = 50): Collection
    {
        $signo = "(CASE WHEN comprobantes.tipo_comprobante = '07' THEN -1 ELSE 1 END)";

        return ComprobanteItem::join('comprobantes', 'comprobantes.id', '=', 'comprobante_items.comprobante_id')
            ->whereIn('comprobante_id', $this->comprobantes($desde, $hasta)->select('id'))
            ->selectRaw("codigo, descripcion, sum(cantidad * {$signo}) as cantidad, sum(comprobante_items.total * {$signo}) as total,
                sum(valor_venta * {$signo}) as valor_venta,
                sum(case when costo_unitario is null then null else cantidad * costo_unitario * {$signo} end) as costo")
            ->groupBy('codigo', 'descripcion')
            ->orderByDesc('total')
            ->limit($limite)
            ->get();
    }

    /** @return array{cantidad: int, total: float, igv: float} */
    public function compras(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $compras = \App\Models\Compra::where('estado', 'registrada')->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);

        return [
            'cantidad' => (clone $compras)->count(),
            'total' => (float) (clone $compras)->sum('total'),
            'igv' => (float) (clone $compras)->sum('igv'),
        ];
    }

    /** @return array{productos: int, unidades: float, valor_venta: float, valor_costo: float, sin_costo: int} */
    public function inventarioValorizado(): array
    {
        $bienes = Producto::where('activo', true)->where('unidad_medida', '!=', 'ZZ');

        return [
            'productos' => (clone $bienes)->count(),
            'unidades' => (float) (clone $bienes)->sum('stock'),
            'valor_venta' => (float) (clone $bienes)->sum(DB::raw('stock * precio_venta')),
            'valor_costo' => (float) (clone $bienes)->whereNotNull('precio_compra')->sum(DB::raw('stock * precio_compra')),
            'sin_costo' => (clone $bienes)->whereNull('precio_compra')->count(),
        ];
    }
}
