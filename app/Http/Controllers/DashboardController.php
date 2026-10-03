<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\Producto;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $hoy = Comprobante::whereDate('fecha_emision', today())->whereNot('estado_sunat', 'anulado');

        return view('dashboard', [
            'ventasHoy' => (clone $hoy)->sum('total'),
            'comprobantesHoy' => (clone $hoy)->count(),
            'totalProductos' => Producto::where('activo', true)->count(),
            'stockBajo' => Producto::where('activo', true)->where('unidad_medida', '!=', 'ZZ')
                ->whereColumn('stock', '<=', 'stock_minimo')->orderBy('stock')->limit(10)->get(),
            'pendientesSunat' => Comprobante::whereIn('estado_sunat', ['pendiente', 'error', 'rechazado'])->latest('id')->limit(10)->get(),
        ]);
    }
}
