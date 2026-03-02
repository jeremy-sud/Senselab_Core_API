<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider — Configuración general de la aplicación
 *
 * Responsabilidades:
 * - Rate Limiting granular (FASE 1.5)
 *
 * Policies y Observers se registran en:
 * - AuthServiceProvider (policies RBAC)
 * - ObserverServiceProvider (observers de modelos)
 *
 * @package App\Providers
 * @version 2.3.0
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiters for the application.
     * FASE 1.5: Rate Limiting Granular
     */
    protected function configureRateLimiting(): void
    {
        $userLimits = config('rate-limiting.users');

        // Rate limiter para API general
        RateLimiter::for('api', function (Request $request) use ($userLimits) {
            $isAuthenticated = $request->user() !== null;
            $userType = $isAuthenticated ? 'authenticated' : 'guest';
            $limit = $userLimits[$userType]['api'] ?? 60;

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Demasiadas solicitudes. Por favor, intenta más tarde.',
                        'error' => 'rate_limit_exceeded',
                        'retry_after' => $headers['Retry-After'] ?? null,
                    ], 429, $headers);
                });
        });

        // Rate limiter para reportes pesados
        RateLimiter::for('reports', function (Request $request) use ($userLimits) {
            $isAuthenticated = $request->user() !== null;
            $userType = $isAuthenticated ? 'authenticated' : 'guest';
            $limit = $userLimits[$userType]['reports'] ?? 10;

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter para importaciones
        RateLimiter::for('imports', function (Request $request) use ($userLimits) {
            $isAuthenticated = $request->user() !== null;
            $userType = $isAuthenticated ? 'authenticated' : 'guest';
            $limit = $userLimits[$userType]['imports'] ?? 5;

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter para Hacienda API
        RateLimiter::for('hacienda', function (Request $request) use ($userLimits) {
            $isAuthenticated = $request->user() !== null;
            $userType = $isAuthenticated ? 'authenticated' : 'guest';
            $limit = $userLimits[$userType]['hacienda'] ?? 20;

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter para login (muy restrictivo)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip());
        });

        // Rate limiter para pagos (crítico, muy restrictivo)
        RateLimiter::for('payment_process', function (Request $request) {
            $isAuthenticated = $request->user() !== null;
            if (!$isAuthenticated) {
                return Limit::perMinute(0);
            }

            return Limit::perMinute(5)
                ->by($request->user()->id);
        });

        // Rate limiter para exportaciones
        RateLimiter::for('exports', function (Request $request) use ($userLimits) {
            $isAuthenticated = $request->user() !== null;
            $userType = $isAuthenticated ? 'authenticated' : 'guest';
            $limit = $userLimits[$userType]['exports'] ?? 10;

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
