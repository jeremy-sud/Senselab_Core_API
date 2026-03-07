<?php

/**
 * Rutas de Ventas y Clientes
 * - Ventas, Clientes, Cuentas por Cobrar, Pagos
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\VentaController;
use App\Http\Controllers\API\ClienteController;
use App\Http\Controllers\API\CuentaPorCobrarController;
use App\Http\Controllers\API\PagoCuentaCobrarController;
use App\Http\Controllers\API\FormaPagoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Ventas y Clientes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // ------------------------------------------------------------------------
    // MÓDULO: CLIENTES
    // ------------------------------------------------------------------------
    Route::get('/clientes', [ClienteController::class, 'index'])
        ->middleware('permission:ver-clientes');
    Route::post('/clientes', [ClienteController::class, 'store'])
        ->middleware('permission:crear-clientes');
    Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])
        ->middleware('permission:ver-clientes');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])
        ->middleware('permission:editar-clientes');
    Route::patch('/clientes/{cliente}', [ClienteController::class, 'update'])
        ->middleware('permission:editar-clientes');
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])
        ->middleware('permission:eliminar-clientes');
    Route::get('/clientes/{cliente}/saldo', [ClienteController::class, 'saldo'])
        ->middleware('permission:ver-clientes');
    Route::get('/clientes/buscar/identificacion', [ClienteController::class, 'buscarPorIdentificacion'])
        ->middleware('permission:ver-clientes');

    // ------------------------------------------------------------------------
    // MÓDULO: VENTAS
    // Rate Limiting: 60 requests/minuto para operaciones de escritura
    // ------------------------------------------------------------------------
    Route::get('/ventas', [VentaController::class, 'index'])
        ->middleware('permission:ver-ventas');
    Route::post('/ventas', [VentaController::class, 'store'])
        ->middleware(['permission:crear-ventas', 'throttle:60,1']);
    Route::post('/ventas/reportes/pdf', [VentaController::class, 'generatePdfReport'])
        ->middleware(['permission:ver-ventas', 'throttle:reports']);
    Route::get('/ventas/{venta}', [VentaController::class, 'show'])
        ->middleware('permission:ver-ventas');
    Route::put('/ventas/{venta}', [VentaController::class, 'update'])
        ->middleware(['permission:editar-ventas', 'throttle:60,1']);
    Route::patch('/ventas/{venta}', [VentaController::class, 'update'])
        ->middleware(['permission:editar-ventas', 'throttle:60,1']);
    Route::delete('/ventas/{venta}', [VentaController::class, 'destroy'])
        ->middleware(['permission:eliminar-ventas', 'throttle:60,1']);

    // ------------------------------------------------------------------------
    // MÓDULO: CUENTAS POR COBRAR
    // ------------------------------------------------------------------------
    Route::apiResource('cuentas-por-cobrar', CuentaPorCobrarController::class)
        ->parameters(['cuentas-por-cobrar' => 'cuentaPorCobrar']);
    Route::get('/cuentas-por-cobrar/cliente/{clienteId}', [CuentaPorCobrarController::class, 'porCliente']);
    Route::get('/cuentas-por-cobrar/vencidas/list', [CuentaPorCobrarController::class, 'vencidas']);
    Route::get('/cuentas-por-cobrar/por-vencer/list', [CuentaPorCobrarController::class, 'vencidas']);
    Route::get('/cuentas-por-cobrar/resumen/por-estado', [CuentaPorCobrarController::class, 'resumen']);

    // Pagos de Cuentas por Cobrar
    Route::apiResource('pagos-cuentas-cobrar', PagoCuentaCobrarController::class)
        ->parameters(['pagos-cuentas-cobrar' => 'pagoCuentaCobrar']);
    Route::get('/pagos-cuentas-cobrar/cuenta/{cuentaId}', [PagoCuentaCobrarController::class, 'porCuenta']);

    // ------------------------------------------------------------------------
    // CATÁLOGO: Formas de Pago
    // ------------------------------------------------------------------------
    Route::apiResource('formas-pago', FormaPagoController::class)
        ->parameters(['formas-pago' => 'formaPago'])
        ->middleware('permission:ver-catalogos');
});
