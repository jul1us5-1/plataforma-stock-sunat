<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoPrevioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Services\ComprobantePdf;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store']);
});

// Enlace firmado para que el cliente vea su comprobante sin iniciar sesión (WhatsApp, correo)
Route::get('cpe/{comprobante}/{formato}', [ComprobanteController::class, 'pdfPublico'])
    ->whereIn('formato', ComprobantePdf::FORMATOS)->middleware('signed')->name('comprobantes.publico');

Route::post('logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('productos/exportar', [ProductoController::class, 'exportar'])->name('productos.exportar');
    Route::post('productos/importar', [ProductoController::class, 'importar'])->name('productos.importar');
    Route::get('productos/{producto}/movimientos', [ProductoController::class, 'movimientos'])->name('productos.movimientos');
    Route::post('productos/{producto}/stock', [ProductoController::class, 'ajustarStock'])->name('productos.stock');
    Route::resource('productos', ProductoController::class)->except('show');

    Route::prefix('{ruta}')->whereIn('ruta', ['cotizaciones', 'pedidos'])->name('previos.')->group(function () {
        Route::get('/', [DocumentoPrevioController::class, 'index'])->name('index');
        Route::get('nuevo', [DocumentoPrevioController::class, 'create'])->name('create');
        Route::post('/', [DocumentoPrevioController::class, 'store'])->name('store');
        Route::get('{documento}', [DocumentoPrevioController::class, 'show'])->name('show');
        Route::post('{documento}/anular', [DocumentoPrevioController::class, 'anular'])->name('anular');
        Route::get('{documento}/pdf', [DocumentoPrevioController::class, 'pdf'])->name('pdf');
    });

    Route::get('caja', [CajaController::class, 'index'])->name('caja.index');
    Route::post('caja/abrir', [CajaController::class, 'abrir'])->name('caja.abrir');
    Route::post('caja/movimiento', [CajaController::class, 'movimiento'])->name('caja.movimiento');
    Route::post('caja/cerrar', [CajaController::class, 'cerrar'])->name('caja.cerrar');
    Route::get('caja/{caja}', [CajaController::class, 'show'])->name('caja.show');

    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/ventas.csv', [ReporteController::class, 'exportarVentas'])->name('reportes.ventas');

    Route::resource('proveedores', ProveedorController::class)->except(['show', 'destroy'])->parameters(['proveedores' => 'proveedor']);
    Route::resource('compras', CompraController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('compras/{compra}/anular', [CompraController::class, 'anular'])->name('compras.anular');

    Route::resource('clientes', ClienteController::class)->except(['show', 'destroy']);

    Route::resource('comprobantes', ComprobanteController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('comprobantes/{comprobante}/nota-credito', [ComprobanteController::class, 'notaCredito'])->name('comprobantes.nota-credito');
    Route::post('comprobantes/{comprobante}/nota-credito', [ComprobanteController::class, 'emitirNotaCredito']);
    Route::post('comprobantes/{comprobante}/anular', [ComprobanteController::class, 'anular'])->name('comprobantes.anular');
    Route::post('comprobantes/{comprobante}/reenviar', [ComprobanteController::class, 'reenviar'])->name('comprobantes.reenviar');
    Route::get('comprobantes/{comprobante}/pdf/{formato}', [ComprobanteController::class, 'pdf'])
        ->whereIn('formato', ComprobantePdf::FORMATOS)->name('comprobantes.pdf');
    Route::get('comprobantes/{comprobante}/{archivo}', [ComprobanteController::class, 'descargar'])
        ->whereIn('archivo', ['xml', 'cdr'])->name('comprobantes.descargar');
});
