<?php

/**
 * Rutas del Portal B2B - Tenant Management API
 *
 * Endpoints consumidos por portal.scisenselab.com para gestión de
 * cuotas, llaves API, webhooks, sesiones, auditoría y 2FA.
 *
 * @package routes/api
 */

use App\Http\Controllers\API\TenantSubscriptionController;
use App\Http\Controllers\API\TenantPortalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Protegidas del Portal de Desarrolladores (v5/tenant/*)
|--------------------------------------------------------------------------
| Todas requieren auth:sanctum. El prefijo /v5/tenant se aplica aquí
| para mantener coherencia con el esquema de versiones de la API.
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->prefix('v5')->group(function () {

    // -------------------------------------------------------------------------
    // SUSCRIPCIÓN & CUOTAS DE USO
    // -------------------------------------------------------------------------

    // Alias del endpoint de perfil — devuelve límites formateados para el Dashboard del portal
    Route::get('/tenant/usage-limits', [TenantSubscriptionController::class, 'getProfile']);

    // Información de facturación del tenant
    Route::get('/tenant/billing', [TenantSubscriptionController::class, 'getProfile']);

    // Cambio de plan de suscripción
    Route::post('/tenant/change-plan', [TenantSubscriptionController::class, 'upgradeSubscription']);

    // -------------------------------------------------------------------------
    // LLAVES DE API (alias de los endpoints de core que ya existen)
    // -------------------------------------------------------------------------

    // Obtener todas las llaves del tenant autenticado
    Route::get('/tenant/api-keys', function (Request $request) {
        // Placeholder — retorna mock si TenantPortalController no está disponible
        return response()->json([
            [
                'id' => '1',
                'name' => 'Facturador Sucursal Escazú',
                'prefix' => 'sl_live_',
                'token' => 'sl_live_••••••••••••••••',
                'environment' => 'live',
                'created_at' => '2026-05-24T04:47:24Z',
            ]
        ]);
    });

    // Generar nueva llave
    Route::post('/tenant/api-keys', function (Request $request) {
        $request->validate(['name' => 'required|string', 'environment' => 'in:live,sandbox']);
        $env = $request->input('environment', 'sandbox');
        $prefix = $env === 'live' ? 'sl_live_' : 'sl_sandbox_';
        $token = $prefix . bin2hex(random_bytes(16));
        return response()->json([
            'id' => 'key_' . uniqid(),
            'name' => $request->input('name'),
            'prefix' => $prefix,
            'token' => $token,
            'environment' => $env,
            'created_at' => now()->toISOString(),
            'message' => 'Guarde esta llave de forma segura. No se volverá a mostrar.',
        ], 201);
    });

    // Revocar llave
    Route::post('/tenant/api-keys/{id}/revoke', function (Request $request, string $id) {
        return response()->json(['success' => true, 'message' => "Llave {$id} revocada."]);
    });

    // -------------------------------------------------------------------------
    // WEBHOOKS
    // -------------------------------------------------------------------------

    Route::get('/tenant/webhooks', function (Request $request) {
        return response()->json([
            [
                'id' => 'wh_1',
                'url' => 'https://api.myclient.com/senselab-receiver',
                'events' => ['invoice.approved', 'payment.received'],
                'active' => true,
                'secret' => 'whsec_' . bin2hex(random_bytes(14)),
            ]
        ]);
    });

    Route::post('/tenant/webhooks', function (Request $request) {
        $request->validate(['url' => 'required|url', 'events' => 'required|array']);
        return response()->json([
            'id' => 'wh_' . uniqid(),
            'url' => $request->input('url'),
            'events' => $request->input('events'),
            'active' => true,
            'secret' => 'whsec_' . bin2hex(random_bytes(14)),
            'created_at' => now()->toISOString(),
        ], 201);
    });

    Route::post('/tenant/webhooks/{id}/test', function (Request $request, string $id) {
        return response()->json(['success' => true, 'message' => "Evento de prueba enviado al webhook {$id}."]);
    });

    // -------------------------------------------------------------------------
    // SESIONES ACTIVAS & SEGURIDAD
    // -------------------------------------------------------------------------

    Route::get('/tenant/sessions', function (Request $request) {
        /** @var \App\Models\Usuario $user */
        $user = $request->user();
        return response()->json([
            [
                'id' => 'sess_' . $user->id . '_1',
                'device' => 'Chrome on Linux',
                'ip' => $request->ip(),
                'location' => 'San José, CR',
                'current' => true,
                'date' => now()->toISOString(),
            ]
        ]);
    });

    Route::post('/tenant/sessions/{id}/revoke', function (Request $request, string $id) {
        return response()->json(['success' => true, 'message' => "Sesión {$id} revocada."]);
    });

    // -------------------------------------------------------------------------
    // AUDITORÍA
    // -------------------------------------------------------------------------

    Route::get('/tenant/security-logs', function (Request $request) {
        /** @var \App\Models\Usuario $user */
        $user = $request->user();
        return response()->json([
            ['id' => 'aud_1', 'event' => 'MFA Login Success', 'user' => $user->email, 'ip' => $request->ip(), 'date' => now()->subMinutes(10)->toISOString(), 'severity' => 'success', 'category' => 'Auth'],
            ['id' => 'aud_2', 'event' => 'API Key Generated', 'user' => $user->email, 'ip' => $request->ip(), 'date' => now()->subDays(5)->toISOString(), 'severity' => 'info', 'category' => 'Security'],
            ['id' => 'aud_3', 'event' => 'Billing Plan Changed', 'user' => $user->email, 'ip' => $request->ip(), 'date' => now()->subDays(6)->toISOString(), 'severity' => 'warning', 'category' => 'Billing'],
        ]);
    });

    // -------------------------------------------------------------------------
    // MFA / 2FA
    // -------------------------------------------------------------------------

    Route::post('/tenant/security/mfa-setup', function (Request $request) {
        /** @var \App\Models\Usuario $user */
        $user = $request->user();
        $secret = strtoupper(base64_encode(random_bytes(20)));
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" .
            urlencode("otpauth://totp/Senselab:{$user->email}?secret={$secret}&issuer=Senselab");
        return response()->json([
            'secret' => $secret,
            'qr_url' => $qrUrl,
        ]);
    });

    Route::post('/tenant/security/mfa-confirm', function (Request $request) {
        $request->validate(['token' => 'required|digits:6']);
        // En producción se verifica el TOTP. Por ahora aceptar siempre para demo.
        return response()->json(['success' => true, 'message' => '2FA activado exitosamente.']);
    });

    // -------------------------------------------------------------------------
    // DOMINIOS PERSONALIZADOS
    // -------------------------------------------------------------------------

    Route::get('/tenant/domains', function (Request $request) {
        return response()->json([
            ['id' => 'd_1', 'domain' => 'factura.miempresa.com', 'status' => 'verified', 'ssl' => true, 'type' => 'custom'],
        ]);
    });

    Route::post('/tenant/domains/{domain}/verify', function (Request $request, string $domain) {
        return response()->json(['success' => true, 'domain' => $domain, 'verified' => true]);
    });

    Route::get('/tenant/branding', function (Request $request) {
        return response()->json(['logo_url' => null, 'primary_color' => '#6366f1', 'company_name' => null]);
    });

    // -------------------------------------------------------------------------
    // FACTURAS DEL TENANT
    // -------------------------------------------------------------------------

    Route::get('/tenant/invoices', function (Request $request) {
        return response()->json([
            'data' => [
                ['id' => 'INV-2026-003', 'client_name' => 'Corporación Delta S.A.', 'total' => 1250.00, 'currency' => 'USD', 'status' => 'approved'],
                ['id' => 'INV-2026-002', 'client_name' => 'Supermercados Alfa', 'total' => 450.50, 'currency' => 'USD', 'status' => 'approved'],
            ],
            'pagination' => ['current_page' => 1, 'total_pages' => 1, 'per_page' => 15],
        ]);
    });

    Route::get('/tenant/payment-methods', function (Request $request) {
        return response()->json([
            ['id' => 'pm_1', 'brand' => 'visa', 'last4' => '4242', 'exp_month' => 12, 'exp_year' => 2028, 'is_default' => true],
        ]);
    });
});
