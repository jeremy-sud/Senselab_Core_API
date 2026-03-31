<?php

/**
 * Rutas de Webhooks
 * FASE 20: Configuración y gestión de webhooks por tenant
 *
 * @package routes/api
 */

use App\Http\Controllers\API\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Webhooks
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // Eventos disponibles (sin middleware de permiso — informativo)
    Route::get('/webhooks/eventos-disponibles', [WebhookController::class, 'eventosDisponibles'])
        ->middleware('permission:ver-webhooks');

    // CRUD de Webhooks
    Route::apiResource('webhooks', WebhookController::class)
        ->middleware(['permission:ver-webhooks,crear-webhooks,editar-webhooks,eliminar-webhooks']);

    // Logs de entrega
    Route::get('/webhooks/{webhook}/logs', [WebhookController::class, 'logs'])
        ->middleware('permission:ver-webhooks');

    // Probar conectividad
    Route::post('/webhooks/{webhook}/test', [WebhookController::class, 'test'])
        ->middleware('permission:editar-webhooks');

    // Regenerar secret
    Route::post('/webhooks/{webhook}/regenerar-secret', [WebhookController::class, 'regenerarSecret'])
        ->middleware('permission:editar-webhooks');
});
