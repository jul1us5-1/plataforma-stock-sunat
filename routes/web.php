<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store']);
});

Route::post('logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::post('productos/importar', [ProductoController::class, 'importar'])->name('productos.importar');
    Route::get('productos/{producto}/movimientos', [ProductoController::class, 'movimientos'])->name('productos.movimientos');
    Route::post('productos/{producto}/stock', [ProductoController::class, 'ajustarStock'])->name('productos.stock');
    Route::resource('productos', ProductoController::class)->except('show');

    Route::resource('clientes', ClienteController::class)->except(['show', 'destroy']);

    Route::resource('comprobantes', ComprobanteController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('comprobantes/{comprobante}/reenviar', [ComprobanteController::class, 'reenviar'])->name('comprobantes.reenviar');
    Route::get('comprobantes/{comprobante}/{archivo}', [ComprobanteController::class, 'descargar'])
        ->whereIn('archivo', ['xml', 'cdr'])->name('comprobantes.descargar');
});
