<?php

/**
 * Rutas de Catálogos del Sistema
 * - CAByS, Tipos de Impuesto, Zonas Geográficas, Tipos de Cliente
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\TipoImpuestoController;
use App\Http\Controllers\API\TasaImpuestoController;
use App\Http\Controllers\API\TipoClienteController;
use App\Http\Controllers\API\ZonaGeograficaController;
use App\Http\Controllers\API\CodigoActividadEconomicaController;
use App\Http\Controllers\API\EtiquetaController;
use App\Http\Controllers\EntidadEtiquetaController;
use App\Http\Controllers\API\CabyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Catálogos del Sistema
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // ------------------------------------------------------------------------
    // CATÁLOGO: CABYS - Códigos de Bienes y Servicios Costa Rica
    // Nota: El controlador es CabyController (sin 's')
    // ------------------------------------------------------------------------
    Route::get('/cabys', [CabyController::class, 'index'])
        ->middleware('permission:ver-catalogos');
    Route::get('/cabys/buscar', [CabyController::class, 'buscar'])
        ->middleware('permission:ver-catalogos');
    Route::get('/cabys/{codigo}', [CabyController::class, 'show'])
        ->middleware('permission:ver-catalogos');
    Route::get('/cabys/categoria/{categoriaId}', [CabyController::class, 'porCategoria'])
        ->middleware('permission:ver-catalogos');

    // ------------------------------------------------------------------------
    // CATÁLOGO: TIPOS DE IMPUESTO Y TASAS
    // ------------------------------------------------------------------------
    Route::get('/tipos-impuesto', [TipoImpuestoController::class, 'index'])
        ->middleware('permission:ver-catalogos');
    Route::post('/tipos-impuesto', [TipoImpuestoController::class, 'store'])
        ->middleware('permission:crear-catalogos');
    Route::get('/tipos-impuesto/{tipoImpuesto}', [TipoImpuestoController::class, 'show'])
        ->middleware('permission:ver-catalogos');
    Route::put('/tipos-impuesto/{tipoImpuesto}', [TipoImpuestoController::class, 'update'])
        ->middleware('permission:editar-catalogos');
    Route::patch('/tipos-impuesto/{tipoImpuesto}', [TipoImpuestoController::class, 'update'])
        ->middleware('permission:editar-catalogos');
    Route::delete('/tipos-impuesto/{tipoImpuesto}', [TipoImpuestoController::class, 'destroy'])
        ->middleware('permission:eliminar-catalogos');
    Route::get('/tipos-impuesto/activos/list', [TipoImpuestoController::class, 'activos'])
        ->middleware('permission:ver-catalogos');

    // Tasas de Impuesto
    Route::apiResource('tasas-impuesto', TasaImpuestoController::class)
        ->parameters(['tasas-impuesto' => 'tasaImpuesto'])
        ->middleware('permission:ver-catalogos');

    // ------------------------------------------------------------------------
    // CATÁLOGO: TIPOS DE CLIENTE
    // ------------------------------------------------------------------------
    Route::apiResource('tipos-clientes', TipoClienteController::class);

    // ------------------------------------------------------------------------
    // CATÁLOGO: ZONAS GEOGRÁFICAS
    // ------------------------------------------------------------------------
    Route::apiResource('zonas-geograficas', ZonaGeograficaController::class);

    // ------------------------------------------------------------------------
    // CATÁLOGO: CÓDIGOS DE ACTIVIDAD ECONÓMICA
    // ------------------------------------------------------------------------
    Route::apiResource('codigos-actividad-economica', CodigoActividadEconomicaController::class);

    // ------------------------------------------------------------------------
    // CATÁLOGO: ETIQUETAS (Sistema de Tags)
    // ------------------------------------------------------------------------
    Route::get('/etiquetas', [EtiquetaController::class, 'index'])
        ->middleware('permission:ver-etiquetas');
    Route::post('/etiquetas', [EtiquetaController::class, 'store'])
        ->middleware('permission:crear-etiquetas');
    Route::get('/etiquetas/{etiqueta}', [EtiquetaController::class, 'show'])
        ->middleware('permission:ver-etiquetas');
    Route::put('/etiquetas/{etiqueta}', [EtiquetaController::class, 'update'])
        ->middleware('permission:editar-etiquetas');
    Route::patch('/etiquetas/{etiqueta}', [EtiquetaController::class, 'update'])
        ->middleware('permission:editar-etiquetas');
    Route::delete('/etiquetas/{etiqueta}', [EtiquetaController::class, 'destroy'])
        ->middleware('permission:eliminar-etiquetas');
    Route::get('/etiquetas/todas/list', [EtiquetaController::class, 'todas'])
        ->middleware('permission:ver-etiquetas');
    Route::get('/etiquetas/estadisticas/uso', [EtiquetaController::class, 'estadisticas'])
        ->middleware('permission:ver-etiquetas');
    Route::get('/etiquetas/buscar', [EtiquetaController::class, 'buscar'])
        ->middleware('permission:ver-etiquetas');

    // Entidades-Etiquetas (Relación Polimórfica)
    Route::get('/entidad-etiquetas', [EntidadEtiquetaController::class, 'index'])
        ->middleware('permission:ver-etiquetas');
    Route::post('/entidad-etiquetas', [EntidadEtiquetaController::class, 'store'])
        ->middleware('permission:crear-etiquetas');
    Route::get('/entidad-etiquetas/{entidadEtiqueta}', [EntidadEtiquetaController::class, 'show'])
        ->middleware('permission:ver-etiquetas');
    Route::put('/entidad-etiquetas/{entidadEtiqueta}', [EntidadEtiquetaController::class, 'update'])
        ->middleware('permission:editar-etiquetas');
    Route::patch('/entidad-etiquetas/{entidadEtiqueta}', [EntidadEtiquetaController::class, 'update'])
        ->middleware('permission:editar-etiquetas');
    Route::delete('/entidad-etiquetas/{entidadEtiqueta}', [EntidadEtiquetaController::class, 'destroy'])
        ->middleware('permission:eliminar-etiquetas');
    Route::post('/entidad-etiquetas/asignar-multiples', [EntidadEtiquetaController::class, 'asignarMultiples'])
        ->middleware('permission:crear-etiquetas');
    Route::post('/entidad-etiquetas/remover-multiples', [EntidadEtiquetaController::class, 'removerMultiples'])
        ->middleware('permission:eliminar-etiquetas');
    Route::get('/entidad-etiquetas/por-entidad', [EntidadEtiquetaController::class, 'porEntidad'])
        ->middleware('permission:ver-etiquetas');
    Route::get('/entidad-etiquetas/por-etiqueta/{etiquetaId}', [EntidadEtiquetaController::class, 'porEtiqueta'])
        ->middleware('permission:ver-etiquetas');
    Route::post('/entidad-etiquetas/sincronizar', [EntidadEtiquetaController::class, 'sincronizar'])
        ->middleware('permission:editar-etiquetas');
});
