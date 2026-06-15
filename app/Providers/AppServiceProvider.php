<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Register custom Carbon macro for microseconds format across all Carbon classes
        $iso8601MicroMacro = function () {
            return $this->format('Y-m-d\TH:i:s.uP');
        };
        \Illuminate\Support\Carbon::macro('iso8601Micro', $iso8601MicroMacro);
        \Carbon\Carbon::macro('iso8601Micro', $iso8601MicroMacro);
        \Carbon\CarbonImmutable::macro('iso8601Micro', $iso8601MicroMacro);

        $this->configureRateLimiting();
        $this->registerWebhookEvents();
        $this->configureStrictness();
    }

    /**
     * Configure model strictness (N+1 detection).
     * DT-10: Previene lazy loading en entornos no productivos.
     */
    protected function configureStrictness(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            Log::channel('performance')->warning('N+1 lazy loading detected', [
                'model' => $model::class,
                'relation' => $relation,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ]);
        });
    }

    /**
     * Register webhook event listeners.
     * FASE 20: Event-driven webhook dispatching.
     */
    protected function registerWebhookEvents(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\WebhookEvent::class,
            \App\Listeners\DispatchWebhookListener::class,
        );
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
