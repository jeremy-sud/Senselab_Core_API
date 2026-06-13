<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Models\TenantUsage;
use App\Models\Usuario;
use App\Models\Venta;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantPlanLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $resource  El recurso a proteger: 'user', 'invoice' o 'ai'
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        /** @var \App\Models\Usuario|null $user */
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // Bypassear límites si es administrador global o el tenant central de Senselab
        if ($user->email === 'admin@scisenselab.com' || $user->empresa_id == 1) {
            return $next($request);
        }

        // Obtener el tenant id
        $tenantHeader = $request->header('X-Senselab-Tenant-Id');
        $tenantId = $tenantHeader ?: 'sl_tenant_' . str_pad((string)$user->empresa_id, 6, '0', STR_PAD_LEFT);

        // Buscar o crear suscripción starter
        $subscription = Subscription::where('tenant_id', $tenantId)->first();
        if (!$subscription) {
            $subscription = Subscription::create([
                'tenant_id' => $tenantId,
                'empresa_id' => $user->empresa_id,
                'usuario_id' => $user->id,
                'plan' => 'starter',
                'status' => 'active',
                'max_users' => 1,
                'max_invoices_month' => 50,
                'max_ai_queries_month' => 10,
                'current_period_end' => now()->addYear(),
            ]);
        }

        // Si es plan business o enterprise, tiene cuotas ilimitadas
        if ($subscription->plan === 'business' || $subscription->plan === 'enterprise') {
            // Si es AI, registramos de todas formas el consumo para métricas
            if ($resource === 'ai') {
                $usage = TenantUsage::firstOrCreate(['tenant_id' => $tenantId]);
                $usage->increment('ai_queries_count_current_month');
            }
            return $next($request);
        }

        $usage = TenantUsage::firstOrCreate(['tenant_id' => $tenantId]);

        // 1. Validar límite de usuarios activos
        if ($resource === 'user') {
            $activeUsers = Usuario::where('empresa_id', $user->empresa_id)
                ->where('activo', true)
                ->where('eliminado', false)
                ->count();

            if ($activeUsers >= $subscription->max_users) {
                return response()->json([
                    'success' => false,
                    'message' => 'Límite de usuarios de tu plan alcanzado. Por favor, sube de nivel tu plan en el perfil.',
                    'error_code' => 'PLAN_LIMIT_USERS_EXCEEDED',
                    'plan' => $subscription->plan,
                    'used' => $activeUsers,
                    'max' => $subscription->max_users
                ], 402);
            }
        }

        // 2. Validar límite de facturas mensuales
        if ($resource === 'invoice') {
            $actualInvoices = Venta::where('empresa_id', $user->empresa_id)
                ->where('creado_en', '>=', now()->startOfMonth())
                ->count();

            if ($actualInvoices >= $subscription->max_invoices_month) {
                return response()->json([
                    'success' => false,
                    'message' => 'Límite mensual de facturación alcanzado. Por favor, actualiza tu suscripción.',
                    'error_code' => 'PLAN_LIMIT_INVOICES_EXCEEDED',
                    'plan' => $subscription->plan,
                    'used' => $actualInvoices,
                    'max' => $subscription->max_invoices_month
                ], 402);
            }
        }

        // 3. Validar límite de consultas de IA
        if ($resource === 'ai') {
            if ($usage->ai_queries_count_current_month >= $subscription->max_ai_queries_month) {
                return response()->json([
                    'success' => false,
                    'message' => 'Límite de consultas de IA de tu plan alcanzado. Por favor, actualiza tu suscripción.',
                    'error_code' => 'PLAN_LIMIT_AI_EXCEEDED',
                    'plan' => $subscription->plan,
                    'used' => $usage->ai_queries_count_current_month,
                    'max' => $subscription->max_ai_queries_month
                ], 402);
            }

            // Incrementar consumo de IA
            $usage->increment('ai_queries_count_current_month');
        }

        return $next($request);
    }
}
