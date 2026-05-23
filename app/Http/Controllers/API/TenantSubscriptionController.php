<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\TenantUsage;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class TenantSubscriptionController extends Controller
{
    /**
     * Obtener el perfil del usuario autenticado con sus límites y uso de cuotas.
     */
    #[OA\Get(
        path: '/api/v5/user/profile',
        operationId: 'getTenantProfile',
        summary: 'Obtener perfil y límites del plan ERP',
        description: 'Retorna la información del usuario junto con su suscripción activa, límites del plan y consumos del mes actual.',
        security: [['sanctum' => []]],
        tags: ['Suscripciones'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil y límites obtenidos exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'user',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: '1'),
                                new OA\Property(property: 'name', type: 'string', example: 'Jeremy Arias Solano'),
                                new OA\Property(property: 'email', type: 'string', example: 'admin@scisenselab.com'),
                                new OA\Property(property: 'company_name', type: 'string', example: 'Senselab Partners S.A.'),
                                new OA\Property(property: 'twofa_enabled', type: 'boolean', example: true),
                                new OA\Property(
                                    property: 'linked_platforms',
                                    properties: [
                                        new OA\Property(property: 'google', type: 'boolean', example: true),
                                        new OA\Property(property: 'apple', type: 'boolean', example: false),
                                        new OA\Property(property: 'github', type: 'boolean', example: true),
                                        new OA\Property(property: 'microsoft', type: 'boolean', example: false)
                                    ]
                                ),
                                new OA\Property(
                                    property: 'subscription',
                                    properties: [
                                        new OA\Property(property: 'plan', type: 'string', example: 'business'),
                                        new OA\Property(property: 'status', type: 'string', example: 'active'),
                                        new OA\Property(property: 'price', type: 'number', example: 149),
                                        new OA\Property(property: 'billing_period', type: 'string', example: 'annual'),
                                        new OA\Property(
                                            property: 'usage',
                                            properties: [
                                                new OA\Property(
                                                    property: 'active_users',
                                                    properties: [
                                                        new OA\Property(property: 'used', type: 'integer', example: 1),
                                                        new OA\Property(property: 'max', type: 'mixed', example: 'unlimited')
                                                    ]
                                                ),
                                                new OA\Property(
                                                    property: 'invoices_month',
                                                    properties: [
                                                        new OA\Property(property: 'used', type: 'integer', example: 74),
                                                        new OA\Property(property: 'max', type: 'mixed', example: 'unlimited')
                                                    ]
                                                ),
                                                new OA\Property(
                                                    property: 'ai_queries_month',
                                                    properties: [
                                                        new OA\Property(property: 'used', type: 'integer', example: 42),
                                                        new OA\Property(property: 'max', type: 'mixed', example: 'unlimited')
                                                    ]
                                                )
                                            ]
                                        )
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            )
        ]
    )]
    public function getProfile(Request $request): JsonResponse
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        
        // Resolver tenant_id a partir de la cabecera o de la empresa del usuario
        $tenantHeader = $request->header('X-Senselab-Tenant-Id');
        $tenantId = $tenantHeader ?: 'sl_tenant_' . str_pad((string)$user->empresa_id, 6, '0', STR_PAD_LEFT);

        // Cargar o inicializar la suscripción de este inquilino
        $subscription = Subscription::where('tenant_id', $tenantId)->first();
        if (!$subscription) {
            $isGlobalAdmin = ($user->email === 'admin@scisenselab.com' || $user->email === 'admin@senselab.com' || $user->empresa_id == 1);
            $plan = $isGlobalAdmin ? 'business' : 'starter';

            $subscription = Subscription::create([
                'tenant_id' => $tenantId,
                'empresa_id' => $user->empresa_id,
                'usuario_id' => $user->id,
                'plan' => $plan,
                'status' => 'active',
                'max_users' => $plan === 'business' ? 999999 : 1,
                'max_invoices_month' => $plan === 'business' ? 999999 : 50,
                'max_ai_queries_month' => $plan === 'business' ? 999999 : 10,
                'current_period_end' => now()->addYear(),
            ]);
        }

        // Cargar o inicializar el consumo actual
        $usage = TenantUsage::where('tenant_id', $tenantId)->first();
        if (!$usage) {
            $usage = TenantUsage::create([
                'tenant_id' => $tenantId,
                'active_users_count' => 1,
                'invoices_count_current_month' => 0,
                'ai_queries_count_current_month' => 0,
            ]);
        }

        // Calcular consumos reales del mes en curso en base de datos
        $actualUsers = Usuario::where('empresa_id', $user->empresa_id)
            ->where('activo', true)
            ->where('eliminado', false)
            ->count();

        $actualInvoices = Venta::where('empresa_id', $user->empresa_id)
            ->where('creado_en', '>=', now()->startOfMonth())
            ->count();

        // Sincronizar en la tabla de uso para consistencia
        $usage->update([
            'active_users_count' => $actualUsers,
            'invoices_count_current_month' => $actualInvoices,
        ]);

        // Determinar límites del plan para retornar
        $isUnlimited = ($subscription->plan === 'business' || $subscription->plan === 'enterprise');
        
        $maxUsers = $isUnlimited ? 'unlimited' : $subscription->max_users;
        $maxInvoices = $isUnlimited ? 'unlimited' : $subscription->max_invoices_month;
        $maxAi = $isUnlimited ? 'unlimited' : $subscription->max_ai_queries_month;

        $planPrice = match($subscription->plan) {
            'free' => 0,
            'starter' => 29,
            'pro' => 79,
            'business' => 149,
            'enterprise' => 299,
            default => 29
        };

        // Formatear respuesta idéntica a las interfaces useTenantLimits.ts
        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => (string)$user->id,
                'name' => $user->nombre . ' ' . $user->apellidos,
                'email' => $user->email,
                'company_name' => $user->empresa ? $user->empresa->nombre : 'Senselab Partners S.A.',
                'twofa_enabled' => true, // Para demostración/seguridad
                'linked_platforms' => [
                    'google' => true,
                    'apple' => false,
                    'github' => $subscription->plan === 'business',
                    'microsoft' => false
                ],
                'subscription' => [
                    'plan' => $subscription->plan,
                    'status' => $subscription->status,
                    'price' => $planPrice,
                    'billing_period' => 'annual',
                    'usage' => [
                        'active_users' => [
                            'used' => $actualUsers,
                            'max' => $maxUsers,
                        ],
                        'invoices_month' => [
                            'used' => $actualInvoices,
                            'max' => $maxInvoices,
                        ],
                        'ai_queries_month' => [
                            'used' => $usage->ai_queries_count_current_month,
                            'max' => $maxAi,
                        ]
                    ]
                ]
            ]
        ]);
    }

    /**
     * Actualizar la suscripción de un inquilino (Simulación de Checkout / Upgrade).
     */
    #[OA\Post(
        path: '/api/v5/billing/subscription/upgrade',
        operationId: 'upgradeTenantSubscription',
        summary: 'Actualizar plan de facturación del Tenant',
        description: 'Realiza un upgrade o cambio de plan del inquilino actual modificando sus capacidades operativas (límites de facturación, usuarios y consultas IA).',
        security: [['sanctum' => []]],
        tags: ['Suscripciones'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['plan'],
                properties: [
                    new OA\Property(property: 'plan', type: 'string', enum: ['free', 'starter', 'pro', 'business', 'enterprise'], example: 'pro')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Plan de suscripción actualizado con éxito'
            ),
            new OA\Response(
                response: 422,
                description: 'Plan inválido o no soportado'
            )
        ]
    )]
    public function upgradeSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|in:free,starter,pro,business,enterprise'
        ]);

        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        
        $tenantHeader = $request->header('X-Senselab-Tenant-Id');
        $tenantId = $tenantHeader ?: 'sl_tenant_' . str_pad((string)$user->empresa_id, 6, '0', STR_PAD_LEFT);

        $plan = $request->input('plan');

        // Configurar los límites correspondientes al plan seleccionado
        $maxUsers = match($plan) {
            'free' => 1,
            'starter' => 1,
            'pro' => 3,
            'business' => 10,
            'enterprise' => 999999
        };

        $maxInvoices = match($plan) {
            'free' => 10,
            'starter' => 50,
            'pro' => 250,
            'business' => 999999,
            'enterprise' => 999999
        };

        $maxAi = match($plan) {
            'free' => 5,
            'starter' => 10,
            'pro' => 100,
            'business' => 999999,
            'enterprise' => 999999
        };

        // Actualizar o crear la suscripción
        $subscription = Subscription::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'empresa_id' => $user->empresa_id,
                'usuario_id' => $user->id,
                'plan' => $plan,
                'status' => 'active',
                'max_users' => $maxUsers,
                'max_invoices_month' => $maxInvoices,
                'max_ai_queries_month' => $maxAi,
                'current_period_end' => now()->addYear()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Suscripción actualizada exitosamente al plan {$plan}.",
            'subscription' => $subscription
        ]);
    }
}
