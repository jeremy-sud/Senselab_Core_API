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
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class TenantSubscriptionController extends Controller
{
    /**
     * Obtener el cliente de Stripe configurado.
     */
    protected function getStripeClient(): ?\Stripe\StripeClient
    {
        $secret = config('services.stripe.secret');
        if (empty($secret) || str_starts_with($secret, 'sk_test_51Px')) {
            return null; // Modo simulado
        }
        return new \Stripe\StripeClient($secret);
    }

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
            $isGlobalAdmin = ($user->email === 'admin@scisenselab.com' || $user->empresa_id == 1);
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

        // Si tenemos Stripe activo y el Tenant tiene una suscripción registrada en Stripe, sincronizar estado
        $stripe = $this->getStripeClient();
        if ($stripe && !empty($subscription->stripe_subscription_id)) {
            try {
                $stripeSub = $stripe->subscriptions->retrieve($subscription->stripe_subscription_id);
                $status = $stripeSub->status === 'active' || $stripeSub->status === 'trialing' ? 'active' : 'inactive';
                if ($subscription->status !== $status) {
                    $subscription->update([
                        'status' => $status,
                        'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSub->current_period_end),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('[Stripe Profile Sync] Failed to retrieve subscription ' . $subscription->stripe_subscription_id . ': ' . $e->getMessage());
            }
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
            'enterprise' => 499,
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
                'empresa_id' => $user->empresa_id,
                'tenant_id' => $tenantId,
                'twofa_enabled' => true,
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
     * Obtener los métodos de pago registrados en Stripe.
     */
    public function getPaymentMethods(Request $request): JsonResponse
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        $tenantId = 'sl_tenant_' . str_pad((string)$user->empresa_id, 6, '0', STR_PAD_LEFT);
        $subscription = Subscription::where('tenant_id', $tenantId)->first();

        // Si no hay Stripe activo o el cliente no existe, retornar mock para compatibilidad local
        $stripe = $this->getStripeClient();
        if (!$stripe || !$subscription || empty($subscription->stripe_customer_id)) {
            return response()->json([
                [
                    'id' => 'pm_mock_visa',
                    'brand' => 'visa',
                    'last4' => '4242',
                    'exp_month' => 12,
                    'exp_year' => 2028,
                    'is_default' => true
                ]
            ]);
        }

        try {
            $customer = $stripe->customers->retrieve($subscription->stripe_customer_id, [
                'expand' => ['invoice_settings.default_payment_method']
            ]);
            $defaultPmId = $customer->invoice_settings->default_payment_method ? $customer->invoice_settings->default_payment_method->id : null;

            $pms = $stripe->paymentMethods->all([
                'customer' => $subscription->stripe_customer_id,
                'type' => 'card'
            ]);

            $formatted = collect($pms->data)->map(function ($pm) use ($defaultPmId) {
                return [
                    'id' => $pm->id,
                    'brand' => $pm->card->brand,
                    'last4' => $pm->card->last4,
                    'exp_month' => $pm->card->exp_month,
                    'exp_year' => $pm->card->exp_year,
                    'is_default' => $pm->id === $defaultPmId
                ];
            });

            return response()->json($formatted);
        } catch (\Exception $e) {
            Log::error('[Stripe PaymentMethods] Failed to retrieve cards: ' . $e->getMessage());
            return response()->json([
                'error' => 'No se pudieron recuperar las tarjetas desde Stripe.'
            ], 500);
        }
    }

    /**
     * Asociar un PaymentMethod (tarjeta tokenizada) al cliente en Stripe.
     */
    public function savePaymentMethod(Request $request): JsonResponse
    {
        $request->validate([
            'payment_method_id' => 'required_without:card_number|string',
            'card_number' => 'nullable|string',
            'card_name' => 'nullable|string',
            'card_expiry' => 'nullable|string',
            'card_cvv' => 'nullable|string'
        ]);

        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        $tenantId = 'sl_tenant_' . str_pad((string)$user->empresa_id, 6, '0', STR_PAD_LEFT);

        $subscription = Subscription::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'empresa_id' => $user->empresa_id,
                'usuario_id' => $user->id,
                'plan' => 'starter',
                'status' => 'active',
                'max_users' => 1,
                'max_invoices_month' => 50,
                'max_ai_queries_month' => 10,
                'current_period_end' => now()->addYear()
            ]
        );

        $stripe = $this->getStripeClient();
        if (!$stripe) {
            // ── Modo Simulado (Fallback) ──
            Log::info("[Stripe Mock] Tarjeta registrada localmente para tenant " . $tenantId);
            return response()->json([
                'success' => true,
                'message' => 'Método de pago simulado guardado exitosamente.'
            ]);
        }

        try {
            $stripeCustomerId = $subscription->stripe_customer_id;
            
            // 1. Crear el Customer en Stripe si no existe
            if (empty($stripeCustomerId)) {
                $customer = $stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->nombre . ' ' . $user->apellidos,
                    'metadata' => [
                        'tenant_id' => $tenantId,
                        'company_name' => $user->empresa ? $user->empresa->nombre : 'Senselab B2B Tenant'
                    ]
                ]);
                $stripeCustomerId = $customer->id;
                $subscription->update(['stripe_customer_id' => $stripeCustomerId]);
            }

            // 2. Asociar la tarjeta (PaymentMethod) al Customer
            $paymentMethodId = $request->input('payment_method_id');
            $stripe->paymentMethods->attach($paymentMethodId, [
                'customer' => $stripeCustomerId
            ]);

            // 3. Establecer como método de pago predeterminado
            $stripe->customers->update($stripeCustomerId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Método de pago registrado exitosamente en Stripe.'
            ]);
        } catch (\Exception $e) {
            Log::error('[Stripe PaymentMethod Attachment] Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la tarjeta: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Crear o actualizar la suscripción del inquilino en Stripe.
     */
    public function upgradeSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|in:free,starter,pro,business,enterprise'
        ]);

        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        $tenantId = 'sl_tenant_' . str_pad((string)$user->empresa_id, 6, '0', STR_PAD_LEFT);
        $plan = $request->input('plan');

        // Configurar los límites correspondientes al plan seleccionado
        $maxUsers = match($plan) {
            'free' => 1,
            'starter' => 1,
            'pro' => 3,
            'business' => 999999,
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

        $stripe = $this->getStripeClient();
        if (!$stripe || app()->environment('testing')) {
            // ── Modo Simulado (Bypass para tests y local sandbox) ──
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
                'message' => "Suscripción simulada actualizada exitosamente al plan {$plan}.",
                'subscription' => $subscription
            ]);
        }

        try {
            $subscription = Subscription::where('tenant_id', $tenantId)->first();
            if (!$subscription || empty($subscription->stripe_customer_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe registrar un método de pago antes de adquirir un plan.'
                ], 402);
            }

            // Obtener el ID del precio en Stripe
            $priceId = config("services.stripe.prices.{$plan}");
            if (empty($priceId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración de precio de Stripe faltante para el plan seleccionado.'
                ], 422);
            }

            $stripeSubscriptionId = $subscription->stripe_subscription_id;

            if (empty($stripeSubscriptionId)) {
                // 1. Crear nueva suscripción en Stripe
                $stripeSub = $stripe->subscriptions->create([
                    'customer' => $subscription->stripe_customer_id,
                    'items' => [['price' => $priceId]],
                    'payment_behavior' => 'default_incomplete',
                    'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
                    'expand' => ['latest_invoice.payment_intent']
                ]);
            } else {
                // 2. Actualizar suscripción existente
                $stripeSub = $stripe->subscriptions->retrieve($stripeSubscriptionId);
                $stripeSub = $stripe->subscriptions->update($stripeSubscriptionId, [
                    'items' => [
                        [
                            'id' => $stripeSub->items->data[0]->id,
                            'price' => $priceId
                        ]
                    ],
                    'proration_behavior' => 'create_prorations',
                    'payment_behavior' => 'pending_if_incomplete',
                    'expand' => ['latest_invoice.payment_intent']
                ]);
            }

            // 3. Verificar si el pago requiere autenticación adicional (3D Secure / SCA)
            $latestInvoice = $stripeSub->latest_invoice;
            $paymentIntent = $latestInvoice ? $latestInvoice->payment_intent : null;

            if ($paymentIntent && $paymentIntent->status === 'requires_action') {
                $subscription->update([
                    'stripe_subscription_id' => $stripeSub->id,
                    'stripe_price_id' => $priceId,
                    'status' => 'inactive'
                ]);

                return response()->json([
                    'requires_action' => true,
                    'payment_intent_client_secret' => $paymentIntent->client_secret,
                    'message' => 'Autenticación 3D Secure requerida por el banco emisor.'
                ]);
            }

            // 4. Actualizar estado local si el pago se procesó inmediatamente
            $subscription->update([
                'stripe_subscription_id' => $stripeSub->id,
                'stripe_price_id' => $priceId,
                'plan' => $plan,
                'status' => 'active',
                'max_users' => $maxUsers,
                'max_invoices_month' => $maxInvoices,
                'max_ai_queries_month' => $maxAi,
                'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSub->current_period_end)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Suscripción actualizada exitosamente al plan {$plan}.",
                'subscription' => $subscription
            ]);
        } catch (\Exception $e) {
            Log::error('[Stripe Upgrade] Subscription update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la suscripción: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Procesar Webhooks de Stripe de manera asíncrona.
     */
    public function handleStripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');
        
        $stripe = $this->getStripeClient();
        if (!$stripe) {
            return response()->json(['status' => 'mocked', 'message' => 'Stripe is disabled or in sandbox mode.']);
        }

        try {
            if (!empty($endpointSecret)) {
                $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } else {
                $data = json_decode($payload, true);
                $event = \Stripe\Event::constructFrom($data);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Webhook signature verification failed: ' . $e->getMessage()], 400);
        }

        Log::info('[Stripe Webhook] Received event: ' . $event->type);

        switch ($event->type) {
            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                if (!empty($invoice->subscription)) {
                    $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();
                    if ($subscription) {
                        $stripeSub = $stripe->subscriptions->retrieve($invoice->subscription);
                        $subscription->update([
                            'status' => 'active',
                            'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSub->current_period_end)
                        ]);
                        Log::info("[Stripe Webhook] Suscripción renovada exitosamente para tenant: " . $subscription->tenant_id);
                    }
                }
                break;

            case 'customer.subscription.deleted':
                $stripeSub = $event->data->object;
                $subscription = Subscription::where('stripe_subscription_id', $stripeSub->id)->first();
                if ($subscription) {
                    $subscription->update([
                        'status' => 'inactive',
                        'plan' => 'starter', // Resetear al plan básico gratuito
                        'max_users' => 1,
                        'max_invoices_month' => 50,
                        'max_ai_queries_month' => 10
                    ]);
                    Log::warn("[Stripe Webhook] Suscripción cancelada de Stripe para tenant: " . $subscription->tenant_id);
                }
                break;
        }

        return response()->json(['status' => 'success']);
    }
}
