<?php

/**
 * Rutas de Configuración y Utilidades del Sistema
 * - Configuraciones, Cajas, Caja Chica, Cuentas Bancarias, Retenciones
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\ConfiguracionController;
use App\Http\Controllers\API\CajaController;
use App\Http\Controllers\API\CajaChicaController;
use App\Http\Controllers\API\MovimientoCajaChicaController;
use App\Http\Controllers\API\CuentaBancariaController;
use App\Http\Controllers\API\MovimientoBancarioController;
use App\Http\Controllers\API\RetencionImpuestoController;
use App\Http\Controllers\API\DeclaracionTributariaController;
use App\Http\Controllers\API\LogAccesoSistemaController;
use App\Http\Controllers\API\UrlShortenerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Configuración del Sistema
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // ------------------------------------------------------------------------
    // CONFIGURACIONES DEL SISTEMA (Solo Admin)
    // ------------------------------------------------------------------------
    Route::get('/configuraciones', [ConfiguracionController::class, 'index'])
        ->middleware('permission:ver-configuraciones');
    Route::post('/configuraciones', [ConfiguracionController::class, 'store'])
        ->middleware('permission:crear-configuraciones');
    Route::get('/configuraciones/{configuracion}', [ConfiguracionController::class, 'show'])
        ->middleware('permission:ver-configuraciones');
    Route::put('/configuraciones/{configuracion}', [ConfiguracionController::class, 'update'])
        ->middleware('permission:editar-configuraciones');
    Route::patch('/configuraciones/{configuracion}', [ConfiguracionController::class, 'update'])
        ->middleware('permission:editar-configuraciones');
    Route::delete('/configuraciones/{configuracion}', [ConfiguracionController::class, 'destroy'])
        ->middleware('permission:eliminar-configuraciones');
    Route::get('/configuraciones/clave/{clave}', [ConfiguracionController::class, 'porClave'])
        ->middleware('permission:ver-configuraciones');
    Route::get('/configuraciones/valor/{clave}', [ConfiguracionController::class, 'obtenerValor'])
        ->middleware('permission:ver-configuraciones');
    Route::put('/configuraciones/actualizar-multiples', [ConfiguracionController::class, 'actualizarMultiples'])
        ->middleware('permission:editar-configuraciones');

    // ------------------------------------------------------------------------
    // CAJAS REGISTRADORAS
    // ------------------------------------------------------------------------
    Route::get('/cajas', [CajaController::class, 'index'])
        ->middleware('permission:ver-cajas');
    Route::post('/cajas', [CajaController::class, 'store'])
        ->middleware('permission:crear-cajas');
    Route::get('/cajas/{caja}', [CajaController::class, 'show'])
        ->middleware('permission:ver-cajas');
    Route::put('/cajas/{caja}', [CajaController::class, 'update'])
        ->middleware('permission:editar-cajas');
    Route::patch('/cajas/{caja}', [CajaController::class, 'update'])
        ->middleware('permission:editar-cajas');
    Route::delete('/cajas/{caja}', [CajaController::class, 'destroy'])
        ->middleware('permission:eliminar-cajas');
    Route::get('/cajas/sucursal/{sucursalId}', [CajaController::class, 'porSucursal'])
        ->middleware('permission:ver-cajas');
    Route::get('/cajas/activas/list', [CajaController::class, 'activas'])
        ->middleware('permission:ver-cajas');
    Route::post('/cajas/{caja}/toggle-activo', [CajaController::class, 'toggleActivo'])
        ->middleware('permission:editar-cajas');

    // ------------------------------------------------------------------------
    // CAJA CHICA (FONDO DE CAJA MENOR)
    // ------------------------------------------------------------------------
    Route::get('/caja-chica', [CajaChicaController::class, 'index'])
        ->middleware('permission:ver-caja_chica');
    Route::post('/caja-chica', [CajaChicaController::class, 'store'])
        ->middleware('permission:crear-caja_chica');
    Route::get('/caja-chica/{cajaChica}', [CajaChicaController::class, 'show'])
        ->middleware('permission:ver-caja_chica');
    Route::put('/caja-chica/{cajaChica}', [CajaChicaController::class, 'update'])
        ->middleware('permission:editar-caja_chica');
    Route::patch('/caja-chica/{cajaChica}', [CajaChicaController::class, 'update'])
        ->middleware('permission:editar-caja_chica');
    Route::delete('/caja-chica/{cajaChica}', [CajaChicaController::class, 'destroy'])
        ->middleware('permission:eliminar-caja_chica');
    Route::get('/caja-chica/abiertas/list', [CajaChicaController::class, 'abiertas'])
        ->middleware('permission:ver-caja_chica');
    Route::get('/caja-chica/responsable/{responsableId}', [CajaChicaController::class, 'porResponsable'])
        ->middleware('permission:ver-caja_chica');
    Route::post('/caja-chica/{cajaChica}/cerrar', [CajaChicaController::class, 'cerrar'])
        ->middleware('permission:editar-caja_chica');
    Route::post('/caja-chica/{cajaChica}/liquidar', [CajaChicaController::class, 'liquidar'])
        ->middleware('permission:editar-caja_chica');
    Route::post('/caja-chica/{cajaChica}/reabrir', [CajaChicaController::class, 'reabrir'])
        ->middleware('permission:editar-caja_chica');
    Route::get('/caja-chica/resumen/por-estado', [CajaChicaController::class, 'resumenPorEstado'])
        ->middleware('permission:ver-caja_chica');

    // Movimientos de Caja Chica
    Route::get('/movimientos-caja-chica', [MovimientoCajaChicaController::class, 'index'])
        ->middleware('permission:ver-caja_chica');
    Route::post('/movimientos-caja-chica', [MovimientoCajaChicaController::class, 'store'])
        ->middleware('permission:crear-caja_chica');
    Route::get('/movimientos-caja-chica/{movimientoCajaChica}', [MovimientoCajaChicaController::class, 'show'])
        ->middleware('permission:ver-caja_chica');
    Route::put('/movimientos-caja-chica/{movimientoCajaChica}', [MovimientoCajaChicaController::class, 'update'])
        ->middleware('permission:editar-caja_chica');
    Route::patch('/movimientos-caja-chica/{movimientoCajaChica}', [MovimientoCajaChicaController::class, 'update'])
        ->middleware('permission:editar-caja_chica');
    Route::delete('/movimientos-caja-chica/{movimientoCajaChica}', [MovimientoCajaChicaController::class, 'destroy'])
        ->middleware('permission:eliminar-caja_chica');
    Route::get('/movimientos-caja-chica/caja/{cajaChicaId}', [MovimientoCajaChicaController::class, 'porCaja'])
        ->middleware('permission:ver-caja_chica');
    Route::get('/movimientos-caja-chica/tipo/{tipo}', [MovimientoCajaChicaController::class, 'porTipo'])
        ->middleware('permission:ver-caja_chica');
    Route::get('/movimientos-caja-chica/resumen/totales', [MovimientoCajaChicaController::class, 'totalPorTipo'])
        ->middleware('permission:ver-caja_chica');

    // ------------------------------------------------------------------------
    // CUENTAS BANCARIAS Y MOVIMIENTOS
    // ------------------------------------------------------------------------
    Route::apiResource('cuentas-bancarias', CuentaBancariaController::class);

    // Movimientos Bancarios - Rate limiting en operaciones de escritura (60/min)
    Route::get('movimientos-bancarios', [MovimientoBancarioController::class, 'index']);
    Route::post('movimientos-bancarios', [MovimientoBancarioController::class, 'store'])
        ->middleware('throttle:60,1');
    Route::get('movimientos-bancarios/{movimientoBancario}', [MovimientoBancarioController::class, 'show']);
    Route::put('movimientos-bancarios/{movimientoBancario}', [MovimientoBancarioController::class, 'update'])
        ->middleware('throttle:60,1');
    Route::patch('movimientos-bancarios/{movimientoBancario}', [MovimientoBancarioController::class, 'update'])
        ->middleware('throttle:60,1');
    Route::delete('movimientos-bancarios/{movimientoBancario}', [MovimientoBancarioController::class, 'destroy'])
        ->middleware('throttle:60,1');

    // ------------------------------------------------------------------------
    // RETENCIONES Y DECLARACIONES TRIBUTARIAS
    // ------------------------------------------------------------------------
    Route::apiResource('retenciones-impuesto', RetencionImpuestoController::class);
    Route::apiResource('declaraciones-tributarias', DeclaracionTributariaController::class);

    // ------------------------------------------------------------------------
    // LOGS DE ACCESO AL SISTEMA
    // ------------------------------------------------------------------------
    Route::apiResource('logs-acceso-sistema', LogAccesoSistemaController::class);

    // ------------------------------------------------------------------------
    // URL SHORTENER (UTILIDAD - SOLO ADMIN)
    // ------------------------------------------------------------------------
    Route::get('/url-shortener', [UrlShortenerController::class, 'index'])
        ->middleware('permission:ver-configuraciones');
    Route::post('/url-shortener', [UrlShortenerController::class, 'store'])
        ->middleware('permission:crear-configuraciones');
    Route::get('/url-shortener/{id}', [UrlShortenerController::class, 'show'])
        ->middleware('permission:ver-configuraciones');
    Route::put('/url-shortener/{id}', [UrlShortenerController::class, 'update'])
        ->middleware('permission:editar-configuraciones');
    Route::patch('/url-shortener/{id}', [UrlShortenerController::class, 'update'])
        ->middleware('permission:editar-configuraciones');
    Route::delete('/url-shortener/{id}', [UrlShortenerController::class, 'destroy'])
        ->middleware('permission:eliminar-configuraciones');
    Route::get('/url-shortener/{id}/stats', [UrlShortenerController::class, 'stats'])
        ->middleware('permission:ver-configuraciones');
});
