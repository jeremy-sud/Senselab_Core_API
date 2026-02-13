<?php

/**
 * Rutas de Compras y Proveedores
 * - Proveedores, Órdenes de Compra, Cuentas por Pagar
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\ProveedorController;
use App\Http\Controllers\API\OrdenCompraController;
use App\Http\Controllers\API\DetalleOrdenCompraController;
use App\Http\Controllers\API\CuentaPorPagarController;
use App\Http\Controllers\API\PagoCuentaPagarController;
use App\Http\Controllers\API\PagoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Compras y Proveedores
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // ------------------------------------------------------------------------
    // MÓDULO: PROVEEDORES
    // ------------------------------------------------------------------------
    Route::get('/proveedores', [ProveedorController::class, 'index'])
        ->middleware('permission:ver-proveedores');
    Route::post('/proveedores', [ProveedorController::class, 'store'])
        ->middleware('permission:crear-proveedores');
    Route::get('/proveedores/{proveedor}', [ProveedorController::class, 'show'])
        ->middleware('permission:ver-proveedores');
    Route::put('/proveedores/{proveedor}', [ProveedorController::class, 'update'])
        ->middleware('permission:editar-proveedores');
    Route::patch('/proveedores/{proveedor}', [ProveedorController::class, 'update'])
        ->middleware('permission:editar-proveedores');
    Route::delete('/proveedores/{proveedor}', [ProveedorController::class, 'destroy'])
        ->middleware('permission:eliminar-proveedores');
    Route::get('/proveedores/{proveedor}/compras-historico', [ProveedorController::class, 'comprasHistorico'])
        ->middleware('permission:ver-proveedores');
    Route::get('/proveedores/buscar/identificacion', [ProveedorController::class, 'buscarPorIdentificacion'])
        ->middleware('permission:ver-proveedores');

    // ------------------------------------------------------------------------
    // MÓDULO: ÓRDENES DE COMPRA
    // ------------------------------------------------------------------------
    Route::apiResource('ordenes-compra', OrdenCompraController::class)
        ->parameters(['ordenes-compra' => 'ordenCompra']);
    Route::post('/ordenes-compra/{ordenCompra}/aprobar', [OrdenCompraController::class, 'aprobar']);
    Route::post('/ordenes-compra/{ordenCompra}/recibir', [OrdenCompraController::class, 'recibir']);
    Route::post('/ordenes-compra/{ordenCompra}/cancelar', [OrdenCompraController::class, 'cancelar']);
    Route::get('/ordenes-compra/proveedor/{proveedorId}', [OrdenCompraController::class, 'porProveedor']);
    Route::get('/ordenes-compra/pendientes/list', [OrdenCompraController::class, 'pendientes']);

    // Detalles de Órdenes de Compra
    Route::get('/ordenes-compra/{ordenCompraId}/detalles', [DetalleOrdenCompraController::class, 'index']);
    Route::post('/detalles-orden-compra', [DetalleOrdenCompraController::class, 'store']);
    Route::get('/detalles-orden-compra/{id}', [DetalleOrdenCompraController::class, 'show']);
    Route::put('/detalles-orden-compra/{id}', [DetalleOrdenCompraController::class, 'update']);
    Route::delete('/detalles-orden-compra/{id}', [DetalleOrdenCompraController::class, 'destroy']);

    // ------------------------------------------------------------------------
    // MÓDULO: CUENTAS POR PAGAR
    // ------------------------------------------------------------------------
    Route::apiResource('cuentas-por-pagar', CuentaPorPagarController::class);
    Route::get('/cuentas-por-pagar/vencidas/list', [CuentaPorPagarController::class, 'vencidas']);
    Route::get('/cuentas-por-pagar/resumen/general', [CuentaPorPagarController::class, 'resumen']);
    Route::get('/cuentas-por-pagar/proveedor/{proveedorId}', [CuentaPorPagarController::class, 'porProveedor']);
    Route::get('/cuentas-por-pagar/por-vencer/list', [CuentaPorPagarController::class, 'porVencer']);

    // Pagos de Cuentas por Pagar
    Route::apiResource('pagos-cuentas-pagar', PagoCuentaPagarController::class)
        ->parameters(['pagos-cuentas-pagar' => 'pagoCuentaPagar']);
    Route::get('/pagos-cuentas-pagar/cuenta/{cuentaId}', [PagoCuentaPagarController::class, 'porCuenta']);
    Route::get('/pagos-cuentas-pagar/forma-pago/{formaPagoId}', [PagoCuentaPagarController::class, 'porFormaPago']);
    Route::get('/pagos-cuentas-pagar/resumen/por-fecha', [PagoCuentaPagarController::class, 'resumenPorFecha']);

    // ------------------------------------------------------------------------
    // MÓDULO: PAGOS GENERAL
    // ------------------------------------------------------------------------
    Route::apiResource('pagos', PagoController::class);
    Route::get('/pagos/resumen/por-forma-pago', [PagoController::class, 'resumenPorFormaPago']);
});
