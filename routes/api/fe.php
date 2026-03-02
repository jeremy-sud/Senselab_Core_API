<?php

/**
 * Rutas de Facturación Electrónica
 * - Comprobantes, Consecutivos FE, Integración Hacienda CR
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\ComprobanteRecibidoElectronicoController;
use App\Http\Controllers\ConsecutivoFeController;
use App\Http\Controllers\ComprobanteElectronicoController;
use App\Http\Controllers\API\MensajeHaciendaController;
use App\Http\Controllers\API\TipoComprobanteFeController;
use App\Http\Controllers\Api\V1\HaciendaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Facturación Electrónica
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // ------------------------------------------------------------------------
    // MÓDULO: COMPROBANTES ELECTRÓNICOS RECIBIDOS
    // Permisos: facturacion_electronica.*
    // ------------------------------------------------------------------------
    Route::apiResource('comprobantes-recibidos-electronicos', ComprobanteRecibidoElectronicoController::class)
        ->parameters(['comprobantes-recibidos-electronicos' => 'comprobante'])
        ->middleware(['permission:ver-facturacion_electronica,facturacion_electronica.crear,facturacion_electronica.actualizar,facturacion_electronica.eliminar']);
    Route::post('/comprobantes-recibidos-electronicos/{id}/confirmar', [ComprobanteRecibidoElectronicoController::class, 'confirmar'])
        ->middleware('permission:editar-facturacion_electronica');
    Route::post('/comprobantes-recibidos-electronicos/{id}/rechazar', [ComprobanteRecibidoElectronicoController::class, 'rechazar'])
        ->middleware('permission:editar-facturacion_electronica');
    Route::get('/comprobantes-recibidos-electronicos/proveedor/{proveedorId}', [ComprobanteRecibidoElectronicoController::class, 'porProveedor'])
        ->middleware('permission:ver-facturacion_electronica');
    Route::get('/comprobantes-recibidos-electronicos/pendientes/list', [ComprobanteRecibidoElectronicoController::class, 'pendientes'])
        ->middleware('permission:ver-facturacion_electronica');
    Route::get('/comprobantes-recibidos-electronicos/resumen/por-estado', [ComprobanteRecibidoElectronicoController::class, 'resumenPorEstado'])
        ->middleware('permission:ver-facturacion_electronica');
    Route::put('/comprobantes-recibidos-electronicos/{id}/actualizar-respuesta-hacienda', [ComprobanteRecibidoElectronicoController::class, 'actualizarRespuestaHacienda'])
        ->middleware('permission:editar-facturacion_electronica');

    // ------------------------------------------------------------------------
    // MÓDULO: CONSECUTIVOS DE FACTURACIÓN ELECTRÓNICA
    // ------------------------------------------------------------------------
    Route::apiResource('consecutivos-fe', ConsecutivoFeController::class)
        ->parameters(['consecutivos-fe' => 'consecutivoFe'])
        ->middleware(['permission:ver-facturacion_electronica,facturacion_electronica.crear,facturacion_electronica.actualizar,facturacion_electronica.eliminar']);
    // Obtener siguiente consecutivo (rate limiting: 30 por minuto)
    Route::post('/consecutivos-fe/obtener-siguiente', [ConsecutivoFeController::class, 'obtenerSiguiente'])
        ->middleware(['permission:ver-facturacion_electronica', 'throttle:30,1']);
    Route::post('/consecutivos-fe/{consecutivoFe}/resetear', [ConsecutivoFeController::class, 'resetear'])
        ->middleware('permission:editar-facturacion_electronica');
    Route::get('/consecutivos-fe/tipo/{tipoDocumentoDgt}', [ConsecutivoFeController::class, 'porTipoDocumento'])
        ->middleware('permission:ver-facturacion_electronica');
    Route::post('/consecutivos-fe/{consecutivoFe}/marcar-agotado', [ConsecutivoFeController::class, 'marcarAgotado'])
        ->middleware('permission:editar-facturacion_electronica');
    Route::post('/consecutivos-fe/{consecutivoFe}/activar', [ConsecutivoFeController::class, 'activar'])
        ->middleware('permission:editar-facturacion_electronica');
    Route::get('/consecutivos-fe/resumen/por-estado', [ConsecutivoFeController::class, 'resumenPorEstado'])
        ->middleware('permission:ver-facturacion_electronica');

    // ------------------------------------------------------------------------
    // MÓDULO: COMPROBANTES ELECTRÓNICOS (EMISIÓN)
    // ------------------------------------------------------------------------
    Route::get('/comprobantes', [ComprobanteElectronicoController::class, 'index'])
        ->middleware('permission:ver-facturacion_electronica');
    Route::post('/comprobantes', [ComprobanteElectronicoController::class, 'store'])
        ->middleware(['permission:crear-facturacion_electronica', 'throttle:30,1']);
    Route::get('/comprobantes/{id}', [ComprobanteElectronicoController::class, 'show'])
        ->middleware('permission:ver-facturacion_electronica');
    Route::get('/comprobantes/{id}/xml', [ComprobanteElectronicoController::class, 'downloadXml'])
        ->middleware('permission:ver-facturacion_electronica');
    Route::post('/comprobantes/{id}/reenviar', [ComprobanteElectronicoController::class, 'reenviar'])
        ->middleware(['permission:editar-facturacion_electronica', 'throttle:20,1']);
    Route::post('/comprobantes/{id}/anular', [ComprobanteElectronicoController::class, 'anular'])
        ->middleware(['permission:crear-facturacion_electronica', 'throttle:20,1']);
    Route::get('/comprobantes/estadisticas/resumen', [ComprobanteElectronicoController::class, 'estadisticas'])
        ->middleware('permission:ver-facturacion_electronica');

    // ------------------------------------------------------------------------
    // MÓDULO: HACIENDA COSTA RICA - FACTURACIÓN ELECTRÓNICA (v4.4)
    // Integración con Ministerio de Hacienda
    // ------------------------------------------------------------------------
    Route::prefix('v1/hacienda')->group(function () {
        // Listar comprobantes con filtrado y paginación
        Route::get('/', [HaciendaController::class, 'index'])
            ->name('api.hacienda.index')
            ->middleware('permission:ver-hacienda');
        
        // Generar nuevo comprobante
        Route::post('/generar', [HaciendaController::class, 'generar'])
            ->name('api.hacienda.generar')
            ->middleware(['permission:crear-hacienda', 'throttle:hacienda']);
        
        // Generar XML del comprobante
        Route::post('/{id}/generar-xml', [HaciendaController::class, 'generarXml'])
            ->name('api.hacienda.generar-xml')
            ->middleware(['permission:editar-hacienda', 'throttle:hacienda']);
        
        // Firmar comprobante con certificado
        Route::post('/{id}/firmar', [HaciendaController::class, 'firmar'])
            ->name('api.hacienda.firmar')
            ->middleware(['permission:editar-hacienda', 'throttle:hacienda']);
        
        // Enviar a Hacienda
        Route::post('/{id}/enviar', [HaciendaController::class, 'enviar'])
            ->name('api.hacienda.enviar')
            ->middleware(['permission:editar-hacienda', 'throttle:hacienda']);
        
        // Obtener estado actual
        Route::get('/{id}/estado', [HaciendaController::class, 'getEstado'])
            ->name('api.hacienda.estado')
            ->middleware('permission:ver-hacienda');
        
        // Estadísticas de comprobantes
        Route::get('/estadisticas/resumen', [HaciendaController::class, 'estadisticas'])
            ->name('api.hacienda.estadisticas')
            ->middleware('permission:ver-hacienda');
        
        // Ver detalle de comprobante
        Route::get('/{id}', [HaciendaController::class, 'show'])
            ->name('api.hacienda.show')
            ->middleware('permission:ver-hacienda');
    });

    // Mensajes Hacienda
    Route::apiResource('mensajes-hacienda', MensajeHaciendaController::class);

    // Tipos Comprobantes FE
    Route::apiResource('tipos-comprobantes-fe', TipoComprobanteFeController::class);
});
