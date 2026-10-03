<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Comprobante;
use App\Services\ReporteService;
use App\Support\Periodo;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index(Request $request, ReporteService $reportes)
    {
        $periodo = Periodo::desdeRequest($request, 'mes');
        [$desde, $hasta] = [$periodo->desde, $periodo->hasta];

        return view('reportes.index', [
            'periodo' => $periodo,
            'resumen' => $reportes->resumen($desde, $hasta),
            'serie' => $reportes->serie($desde, $hasta),
            'tipos' => $reportes->porTipo($desde, $hasta),
            'metodos' => $reportes->porMetodoPago($desde, $hasta),
            'productos' => $reportes->productosVendidos($desde, $hasta),
            'inventario' => $reportes->inventarioValorizado(),
        ]);
    }

    public function exportarVentas(Request $request, ReporteService $reportes)
    {
        $periodo = Periodo::desdeRequest($request, 'mes');
        $nombre = 'ventas_'.$periodo->desde->format('Ymd').'_'.$periodo->hasta->format('Ymd').'.csv';
        $comprobantes = $reportes->comprobantes($periodo->desde, $periodo->hasta)->with('cliente')->orderBy('fecha_emision');

        return response()->streamDownload(function () use ($comprobantes) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM para que Excel lea las tildes
            fputcsv($salida, ['Fecha', 'Tipo', 'Número', 'Doc. cliente', 'Cliente', 'Método de pago', 'Op. gravadas', 'Op. exoneradas', 'Op. inafectas', 'IGV', 'Total', 'Estado SUNAT'], ';', '"', '');
            foreach ($comprobantes->lazy() as $c) {
                fputcsv($salida, [
                    $c->fecha_emision->format('d/m/Y H:i'), Comprobante::TIPOS[$c->tipo_comprobante] ?? $c->tipo_comprobante, $c->numero(),
                    $c->cliente?->numero_documento, $c->cliente?->razon_social ?? 'Clientes varios',
                    Caja::METODOS_PAGO[$c->metodo_pago] ?? $c->metodo_pago,
                    $c->op_gravadas, $c->op_exoneradas, $c->op_inafectas, $c->igv, $c->total, $c->estado_sunat,
                ], ';', '"', '');
            }
            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
