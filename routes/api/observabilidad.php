<?php

/**
 * Rutas de Observabilidad
 * - Health Checks, Métricas, Prometheus
 * 
 * @package routes/api
 */

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Observabilidad (Health Check y Métricas)
|--------------------------------------------------------------------------
| Estas rutas NO requieren autenticación para permitir que los balanceadores
| de carga y sistemas de monitoreo puedan verificar el estado del servicio.
*/

Route::prefix('health')->group(function () {
    // Liveness probe - ¿Está el servicio vivo?
    Route::get('/live', [HealthCheckController::class, 'liveness'])
        ->name('health.liveness');

    // Readiness probe - ¿Está listo para recibir tráfico?
    Route::get('/ready', [HealthCheckController::class, 'readiness'])
        ->name('health.readiness');

    // Métricas rápidas sin auth
    Route::get('/metrics', [HealthCheckController::class, 'metrics'])
        ->name('health.metrics');

    // Detalles del sistema (requiere autenticación)
    Route::get('/details', [HealthCheckController::class, 'details'])
        ->middleware('auth:sanctum')
        ->name('health.details');
});

// Metrics endpoint (Prometheus format)
Route::get('/metrics', [MetricsController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('metrics.prometheus');

Route::get('/metrics/health', [MetricsController::class, 'health'])
    ->name('metrics.health');
