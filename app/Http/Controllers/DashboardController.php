<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\Producto;
use App\Services\ReporteService;
use App\Support\Periodo;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReporteService $reportes)
    {
        $periodo = Periodo::desdeRequest($request);

        return view('dashboard', [
            'periodo' => $periodo,
            'resumen' => $reportes->resumen($periodo->desde, $periodo->hasta),
            'serie' => $reportes->serie($periodo->desde, $periodo->hasta),
            'metodos' => $reportes->porMetodoPago($periodo->desde, $periodo->hasta),
            'masVendidos' => $reportes->productosVendidos($periodo->desde, $periodo->hasta, 5),
            'stockBajo' => Producto::where('activo', true)->where('unidad_medida', '!=', 'ZZ')
                ->whereColumn('stock', '<=', 'stock_minimo')->orderBy('stock')->limit(8)->get(),
            'totalStockBajo' => Producto::where('activo', true)->where('unidad_medida', '!=', 'ZZ')->whereColumn('stock', '<=', 'stock_minimo')->count(),
            'pendientesSunat' => Comprobante::whereIn('estado_sunat', ['pendiente', 'error', 'rechazado'])->latest('id')->limit(8)->get(),
        ]);
    }
}
