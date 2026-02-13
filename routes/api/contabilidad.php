<?php

/**
 * Rutas de Contabilidad
 * - Cuentas Contables, Asientos, Presupuestos, Tipos de Cambio
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\CuentaContableController;
use App\Http\Controllers\API\TipoCuentaController;
use App\Http\Controllers\API\AsientoContableController;
use App\Http\Controllers\API\DetalleAsientoController;
use App\Http\Controllers\API\PresupuestoController;
use App\Http\Controllers\API\DetallePresupuestoController;
use App\Http\Controllers\API\TipoCambioHistorialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Contabilidad
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // ------------------------------------------------------------------------
    // MÓDULO: CUENTAS CONTABLES
    // Permisos: cuentas_contables.leer, cuentas_contables.crear, etc.
    // ------------------------------------------------------------------------
    Route::apiResource('cuentas-contables', CuentaContableController::class)
        ->middleware(['permission:ver-cuentas_contables,cuentas_contables.crear,cuentas_contables.actualizar,cuentas_contables.eliminar']);
    Route::get('/cuentas-contables/arbol/completo', [CuentaContableController::class, 'arbol'])
        ->middleware('permission:ver-cuentas_contables');
    Route::get('/cuentas-contables/tipo/{tipoCuentaId}', [CuentaContableController::class, 'porTipo'])
        ->middleware('permission:ver-cuentas_contables');
    Route::get('/cuentas-contables/codigo/{codigo}', [CuentaContableController::class, 'porCodigo'])
        ->middleware('permission:ver-cuentas_contables');
    Route::get('/cuentas-contables/naturaleza/{naturaleza}', [CuentaContableController::class, 'porNaturaleza'])
        ->middleware('permission:ver-cuentas_contables');
    Route::get('/cuentas-contables/movimientos/list', [CuentaContableController::class, 'paraMovimientos'])
        ->middleware('permission:ver-cuentas_contables');

    // Tipos de Cuentas Contables
    Route::apiResource('tipos-cuentas', TipoCuentaController::class);
    Route::get('/tipos-cuentas/naturaleza/{naturaleza}', [TipoCuentaController::class, 'porNaturaleza']);
    Route::get('/tipos-cuentas/activos/list', [TipoCuentaController::class, 'activos']);

    // ------------------------------------------------------------------------
    // MÓDULO: ASIENTOS CONTABLES
    // Permisos: asientos_contables.*
    // ------------------------------------------------------------------------
    Route::apiResource('asientos-contables', AsientoContableController::class)
        ->middleware(['permission:ver-asientos_contables,asientos_contables.crear,asientos_contables.actualizar,asientos_contables.eliminar']);
    Route::post('/asientos-contables/{id}/mayorizar', [AsientoContableController::class, 'mayorizar'])
        ->middleware('permission:editar-asientos_contables');
    Route::get('/asientos-contables/{id}/validar', [AsientoContableController::class, 'validar'])
        ->middleware('permission:ver-asientos_contables');

    // Detalle de Asientos Contables
    Route::get('/detalle-asientos', [DetalleAsientoController::class, 'index'])
        ->middleware('permission:ver-asientos_contables');
    Route::get('/detalle-asientos/{id}', [DetalleAsientoController::class, 'show'])
        ->middleware('permission:ver-asientos_contables');
    Route::get('/detalle-asientos/cuenta/{cuentaContableId}', [DetalleAsientoController::class, 'porCuenta'])
        ->middleware('permission:ver-asientos_contables');
    Route::get('/detalle-asientos/reportes/libro-mayor', [DetalleAsientoController::class, 'libroMayor'])
        ->middleware('permission:ver-asientos_contables');
    Route::get('/detalle-asientos/reportes/balance-comprobacion', [DetalleAsientoController::class, 'balanceComprobacion'])
        ->middleware('permission:ver-asientos_contables');

    // ------------------------------------------------------------------------
    // MÓDULO: PRESUPUESTOS FINANCIEROS
    // ------------------------------------------------------------------------
    Route::apiResource('presupuestos', PresupuestoController::class);
    Route::post('/presupuestos/{id}/activar', [PresupuestoController::class, 'activar']);
    Route::post('/presupuestos/{id}/finalizar', [PresupuestoController::class, 'finalizar']);
    Route::get('/presupuestos/activos/list', [PresupuestoController::class, 'activos']);
    Route::get('/presupuestos/{id}/resumen', [PresupuestoController::class, 'resumen']);

    // Detalle de Presupuestos
    Route::get('/presupuestos/{presupuestoId}/detalles', [DetallePresupuestoController::class, 'index']);
    Route::post('/detalles-presupuestos', [DetallePresupuestoController::class, 'store']);
    Route::get('/detalles-presupuestos/{id}', [DetallePresupuestoController::class, 'show']);
    Route::put('/detalles-presupuestos/{id}', [DetallePresupuestoController::class, 'update']);
    Route::delete('/detalles-presupuestos/{id}', [DetallePresupuestoController::class, 'destroy']);

    // ------------------------------------------------------------------------
    // MÓDULO: TIPOS DE CAMBIO
    // ------------------------------------------------------------------------
    Route::get('/tipos-cambio-historial', [TipoCambioHistorialController::class, 'index'])
        ->middleware('permission:ver-tipos_cambio');
    Route::post('/tipos-cambio-historial', [TipoCambioHistorialController::class, 'store'])
        ->middleware('permission:crear-tipos_cambio');
    Route::get('/tipos-cambio-historial/{tipoCambioHistorial}', [TipoCambioHistorialController::class, 'show'])
        ->middleware('permission:ver-tipos_cambio');
    Route::put('/tipos-cambio-historial/{tipoCambioHistorial}', [TipoCambioHistorialController::class, 'update'])
        ->middleware('permission:editar-tipos_cambio');
    Route::patch('/tipos-cambio-historial/{tipoCambioHistorial}', [TipoCambioHistorialController::class, 'update'])
        ->middleware('permission:editar-tipos_cambio');
    Route::delete('/tipos-cambio-historial/{tipoCambioHistorial}', [TipoCambioHistorialController::class, 'destroy'])
        ->middleware('permission:eliminar-tipos_cambio');
    Route::get('/tipos-cambio/vigente', [TipoCambioHistorialController::class, 'vigente'])
        ->middleware('permission:ver-tipos_cambio');
    Route::post('/tipos-cambio/convertir', [TipoCambioHistorialController::class, 'convertir'])
        ->middleware('permission:ver-tipos_cambio');
    Route::get('/tipos-cambio/moneda', [TipoCambioHistorialController::class, 'porMoneda'])
        ->middleware('permission:ver-tipos_cambio');
    Route::get('/tipos-cambio/fecha/{fecha}', [TipoCambioHistorialController::class, 'porFecha'])
        ->middleware('permission:ver-tipos_cambio');
    Route::get('/tipos-cambio/tendencia', [TipoCambioHistorialController::class, 'tendencia'])
        ->middleware('permission:ver-tipos_cambio');
});
