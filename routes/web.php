<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store']);
});

Route::post('logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('productos/exportar', [ProductoController::class, 'exportar'])->name('productos.exportar');
    Route::post('productos/importar', [ProductoController::class, 'importar'])->name('productos.importar');
    Route::get('productos/{producto}/movimientos', [ProductoController::class, 'movimientos'])->name('productos.movimientos');
    Route::post('productos/{producto}/stock', [ProductoController::class, 'ajustarStock'])->name('productos.stock');
    Route::resource('productos', ProductoController::class)->except('show');

    Route::get('caja', [CajaController::class, 'index'])->name('caja.index');
    Route::post('caja/abrir', [CajaController::class, 'abrir'])->name('caja.abrir');
    Route::post('caja/movimiento', [CajaController::class, 'movimiento'])->name('caja.movimiento');
    Route::post('caja/cerrar', [CajaController::class, 'cerrar'])->name('caja.cerrar');
    Route::get('caja/{caja}', [CajaController::class, 'show'])->name('caja.show');

    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/ventas.csv', [ReporteController::class, 'exportarVentas'])->name('reportes.ventas');

    Route::resource('clientes', ClienteController::class)->except(['show', 'destroy']);

    Route::resource('comprobantes', ComprobanteController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('comprobantes/{comprobante}/reenviar', [ComprobanteController::class, 'reenviar'])->name('comprobantes.reenviar');
    Route::get('comprobantes/{comprobante}/{archivo}', [ComprobanteController::class, 'descargar'])
        ->whereIn('archivo', ['xml', 'cdr'])->name('comprobantes.descargar');
});
