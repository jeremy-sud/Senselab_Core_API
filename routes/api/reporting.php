<?php

/**
 * Rutas de Reportes y Dashboard
 * - Reportes financieros (Estado de Resultados, Balance General, Flujo de Caja)
 * - Dashboard KPIs
 * - Reportes programados (CRUD)
 *
 * @package routes/api
 */

use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\ReporteController;
use App\Http\Controllers\API\ReporteProgramadoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Reporting & Dashboard
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // ------------------------------------------------------------------------
    // MÓDULO: DASHBOARD
    // ------------------------------------------------------------------------
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:ver-reportes');
    Route::post('/dashboard/invalidar-cache', [DashboardController::class, 'invalidarCache'])
        ->middleware('permission:editar-reportes');

    // ------------------------------------------------------------------------
    // MÓDULO: REPORTES FINANCIEROS
    // Throttle específico para reportes (más restrictivo)
    // ------------------------------------------------------------------------
    Route::middleware('throttle:reports')->group(function () {
        Route::get('/reportes/financiero', [ReporteController::class, 'financiero'])
            ->middleware('permission:ver-reportes');
        Route::get('/reportes/tipos', [ReporteController::class, 'tiposDisponibles'])
            ->middleware('permission:ver-reportes');
        Route::post('/reportes/invalidar-cache', [ReporteController::class, 'invalidarCache'])
            ->middleware('permission:editar-reportes');
    });

    // ------------------------------------------------------------------------
    // MÓDULO: REPORTES PROGRAMADOS (CRUD)
    // ------------------------------------------------------------------------
    Route::get('/reportes/programados', [ReporteProgramadoController::class, 'index'])
        ->middleware('permission:ver-reportes');
    Route::post('/reportes/programados', [ReporteProgramadoController::class, 'store'])
        ->middleware('permission:crear-reportes');
    Route::get('/reportes/programados/{id}', [ReporteProgramadoController::class, 'show'])
        ->middleware('permission:ver-reportes');
    Route::put('/reportes/programados/{id}', [ReporteProgramadoController::class, 'update'])
        ->middleware('permission:editar-reportes');
    Route::delete('/reportes/programados/{id}', [ReporteProgramadoController::class, 'destroy'])
        ->middleware('permission:eliminar-reportes');
});
