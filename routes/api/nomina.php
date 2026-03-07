<?php

/**
 * Rutas de Nómina y Recursos Humanos
 * - Empleados, Períodos de Nómina, Pagos, Deducciones
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\EmpleadoController;
use App\Http\Controllers\API\PeriodoNominaController;
use App\Http\Controllers\API\PagoNominaController;
use App\Http\Controllers\API\NominaEmpleadoController;
use App\Http\Controllers\API\DeduccionLegalController;
use App\Http\Controllers\API\PlanillaCcssController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Nómina
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // ------------------------------------------------------------------------
    // MÓDULO: EMPLEADOS
    // ------------------------------------------------------------------------
    Route::get('/empleados', [EmpleadoController::class, 'index'])
        ->middleware('permission:ver-empleados');
    Route::post('/empleados', [EmpleadoController::class, 'store'])
        ->middleware('permission:crear-empleados');
    Route::get('/empleados/{empleado}', [EmpleadoController::class, 'show'])
        ->middleware('permission:ver-empleados');
    Route::put('/empleados/{empleado}', [EmpleadoController::class, 'update'])
        ->middleware('permission:editar-empleados');
    Route::patch('/empleados/{empleado}', [EmpleadoController::class, 'update'])
        ->middleware('permission:editar-empleados');
    Route::delete('/empleados/{empleado}', [EmpleadoController::class, 'destroy'])
        ->middleware('permission:eliminar-empleados');
    Route::get('/empleados/activos/list', [EmpleadoController::class, 'activos'])
        ->middleware('permission:ver-empleados');
    Route::get('/empleados/sucursal/{sucursalId}', [EmpleadoController::class, 'porSucursal'])
        ->middleware('permission:ver-empleados');
    Route::get('/empleados/cargo/{cargoId}', [EmpleadoController::class, 'porCargo'])
        ->middleware('permission:ver-empleados');

    // ------------------------------------------------------------------------
    // MÓDULO: PERÍODOS DE NÓMINA
    // Permisos: nomina.leer, nomina.crear, nomina.actualizar, nomina.eliminar
    // ------------------------------------------------------------------------
    Route::apiResource('periodos-nomina', PeriodoNominaController::class)
        ->middleware(['permission:ver-nomina,nomina.crear,nomina.actualizar,nomina.eliminar']);
    Route::post('/periodos-nomina/{id}/cerrar', [PeriodoNominaController::class, 'cerrar'])
        ->middleware('permission:editar-nomina');
    Route::post('/periodos-nomina/{id}/procesar', [PeriodoNominaController::class, 'procesar'])
        ->middleware('permission:editar-nomina');
    Route::get('/periodos-nomina/{id}/resumen', [PeriodoNominaController::class, 'resumen'])
        ->middleware('permission:ver-nomina');
    Route::get('/periodos-nomina/activos/list', [PeriodoNominaController::class, 'activos'])
        ->middleware('permission:ver-nomina');

    // ------------------------------------------------------------------------
    // MÓDULO: PAGOS DE NÓMINA
    // ------------------------------------------------------------------------
    Route::apiResource('pagos-nomina', PagoNominaController::class)
        ->middleware(['permission:ver-nomina,nomina.crear,nomina.actualizar,nomina.eliminar']);
    Route::post('/pagos-nomina/{id}/marcar-pagado', [PagoNominaController::class, 'marcarPagado'])
        ->middleware(['permission:editar-nomina', 'throttle:payment_process']);
    Route::get('/pagos-nomina/empleado/{empleadoId}', [PagoNominaController::class, 'porEmpleado'])
        ->middleware('permission:ver-nomina');
    Route::get('/pagos-nomina/resumen/por-metodo-pago', [PagoNominaController::class, 'resumenPorMetodoPago'])
        ->middleware('permission:ver-nomina');
    Route::get('/pagos-nomina/totales/por-periodo', [PagoNominaController::class, 'totalesPorPeriodo'])
        ->middleware('permission:ver-nomina');

    // ------------------------------------------------------------------------
    // MÓDULO: NÓMINA DE EMPLEADOS
    // ------------------------------------------------------------------------
    Route::apiResource('nomina-empleados', NominaEmpleadoController::class)
        ->parameters(['nomina-empleados' => 'nominaEmpleado'])
        ->middleware(['permission:ver-nomina,nomina.crear,nomina.actualizar,nomina.eliminar']);
    Route::get('/nomina-empleados/periodo/{periodoId}', [NominaEmpleadoController::class, 'porPeriodo'])
        ->middleware('permission:ver-nomina');
    Route::get('/nomina-empleados/empleado/{empleadoId}', [NominaEmpleadoController::class, 'porEmpleado'])
        ->middleware('permission:ver-nomina');
    Route::get('/nomina-empleados/resumen/periodo/{periodoId}', [NominaEmpleadoController::class, 'resumenPorPeriodo'])
        ->middleware('permission:ver-nomina');

    // ------------------------------------------------------------------------
    // MÓDULO: DEDUCCIONES LEGALES (CCSS, etc.)
    // ------------------------------------------------------------------------
    Route::apiResource('deducciones-legales', DeduccionLegalController::class);

    // Planillas CCSS
    Route::apiResource('planillas-ccss', PlanillaCcssController::class);
});
