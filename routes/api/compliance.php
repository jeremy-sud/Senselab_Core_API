<?php

/**
 * Rutas de Compliance y GDPR
 * - Solicitudes de Eliminación, Auditoría, Políticas de Retención
 * 
 * @package routes/api
 */

use App\Http\Controllers\GdprController;
use App\Http\Controllers\ComplianceDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Compliance y GDPR (Protección de Datos)
|--------------------------------------------------------------------------
*/

// GDPR Deletion Requests (Derecho al Olvido)
Route::middleware('auth:sanctum')->prefix('gdpr/requests')->group(function () {
    // Crear nueva solicitud de eliminación
    Route::post('/', [GdprController::class, 'createRequest'])
        ->middleware('throttle:5,60')
        ->name('gdpr.requests.create');

    // Ver solicitudes del usuario
    Route::get('/', [GdprController::class, 'listRequests'])
        ->name('gdpr.requests.list');

    // Ver detalle de solicitud
    Route::get('/{id}', [GdprController::class, 'getRequest'])
        ->name('gdpr.requests.show');

    // Verificar identidad
    Route::post('/{id}/verify', [GdprController::class, 'verifyIdentity'])
        ->middleware('throttle:3,60')
        ->name('gdpr.requests.verify');

    // Admin: Aprobar solicitud
    Route::post('/{id}/approve', [GdprController::class, 'approveRequest'])
        ->middleware('can:approve gdpr requests')
        ->name('gdpr.requests.approve');

    // Admin: Rechazar solicitud
    Route::post('/{id}/reject', [GdprController::class, 'rejectRequest'])
        ->middleware('can:reject gdpr requests')
        ->name('gdpr.requests.reject');

    // Admin: Ver estadísticas
    Route::get('/admin/stats', [GdprController::class, 'getStats'])
        ->middleware('can:view gdpr stats')
        ->name('gdpr.stats');
});

// Compliance Dashboard y Reportes
Route::middleware('auth:sanctum')->prefix('compliance')->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [ComplianceDashboardController::class, 'getDashboard'])
        ->middleware('can:view compliance dashboard')
        ->name('compliance.dashboard');

    // Audit Logs
    Route::get('/audit-logs', [ComplianceDashboardController::class, 'getAuditLogs'])
        ->middleware('can:view audit logs')
        ->name('compliance.audit-logs');

    Route::get('/audit-logs/{id}', [ComplianceDashboardController::class, 'getAuditLogDetail'])
        ->middleware('can:view audit logs')
        ->name('compliance.audit-logs.detail');

    // Retention Policies
    Route::get('/retention-policies', [ComplianceDashboardController::class, 'getRetentionPolicies'])
        ->middleware('can:view retention policies')
        ->name('compliance.retention-policies');

    Route::get('/retention-policies/{id}', [ComplianceDashboardController::class, 'getRetentionPolicyDetail'])
        ->middleware('can:view retention policies')
        ->name('compliance.retention-policies.detail');

    Route::post('/retention-policies/{id}/execute', [ComplianceDashboardController::class, 'executeRetentionPolicy'])
        ->middleware('can:execute retention policies')
        ->middleware('throttle:5,60')
        ->name('compliance.retention-policies.execute');

    // GDPR Compliance Report
    Route::get('/report/gdpr', [ComplianceDashboardController::class, 'getGdprComplianceReport'])
        ->middleware('can:view compliance reports')
        ->name('compliance.report.gdpr');
});
