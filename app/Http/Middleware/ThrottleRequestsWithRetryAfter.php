<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\RateLimitingService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Throttle Requests with Retry-After - FASE 1.5
 *
 * Middleware personalizado para manejar rate limiting granular con
 * respuestas 429 y headers Retry-After.
 *
 * @author Jeremy Arias Solano
 */
class ThrottleRequestsWithRetryAfter
{
    /**
     * Mapa de rutas a claves de rate limiting
     * @var array<string, string>
     */
    protected array $routeMap = [
        '/api/*/auth/login' => 'login',
        '/api/*/auth/register' => 'login',
        '/api/*/auth/forgot-password' => 'login',
        '/api/*/pagos' => 'payment_process',
        '/api/*/reportes' => 'reports',
        '/api/*/importar' => 'imports',
        '/api/*/exportar' => 'exports',
        '/api/*/hacienda' => 'hacienda',
    ];

    /**
     * Manejar la solicitud
     */
    public function handle(Request $request, Closure $next, string $limiter = 'api'): SymfonyResponse
    {
        // Verificar si la IP está bloqueada
        if (RateLimitingService::isIPBlocked($request)) {
            return $this->tooManyRequestsResponse($request, 3600);
        }

        // Determinar la clave de rate limiting basada en la ruta
        $limiter = $this->determineLimiter($request, $limiter);

        // Verificar si se ha excedido el límite
        if (RateLimitingService::isExceeded($request, $limiter)) {
            RateLimitingService::logViolation($request, $limiter);
            return $this->tooManyRequestsResponse($request, $limiter);
        }

        // Incrementar contador
        RateLimitingService::increment($request, $limiter);

        // Proceder con la solicitud
        $response = $next($request);

        // Agregar headers de rate limiting
        return $this->addRateLimitHeaders($response, $request, $limiter);
    }

    /**
     * Determinar la clave de rate limiting basada en la ruta
     */
    protected function determineLimiter(Request $request, string $default): string
    {
        $path = $request->path();

        foreach ($this->routeMap as $pattern => $limiter) {
            if ($this->pathMatches($path, $pattern)) {
                return $limiter;
            }
        }

        return $default;
    }

    /**
     * Verificar si una ruta coincide con un patrón
     */
    protected function pathMatches(string $path, string $pattern): bool
    {
        // Convertir patrón a regex simple
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = str_replace('/', '\/', $pattern);

        return (bool) preg_match('/^' . $pattern . '.*/', $path);
    }

    /**
     * Respuesta de demasiadas solicitudes (429)
     */
    protected function tooManyRequestsResponse(Request $request, string|int $limiterOrSeconds = 'api'): JsonResponse
    {
        $retryAfter = is_string($limiterOrSeconds)
            ? RateLimitingService::resetIn($request, $limiterOrSeconds)
            : $limiterOrSeconds;

        $message = config('rate-limiting.messages.limit_exceeded');
        if ($retryAfter > 0) {
            $message = str_replace(
                ':seconds',
                (string) $retryAfter,
                config('rate-limiting.messages.retry_after')
            );
        }

        return response()->json(
            [
                'success' => false,
                'message' => $message,
                'error' => 'rate_limit_exceeded',
                'retry_after' => $retryAfter,
            ],
            429,
            [
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => 'Unknown',
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            ]
        );
    }

    /**
     * Agregar headers de rate limiting a la respuesta
     */
    protected function addRateLimitHeaders(
        SymfonyResponse $response,
        Request $request,
        string $limiter
    ): SymfonyResponse {
        $remaining = RateLimitingService::remaining($request, $limiter);
        $limit = RateLimitingService::getLimit($request, $limiter);
        $resetIn = RateLimitingService::resetIn($request, $limiter);

        $response->headers->set(
            config('rate-limiting.headers.remaining'),
            (string) $remaining
        );
        $response->headers->set(
            config('rate-limiting.headers.limit'),
            (string) $limit
        );
        $response->headers->set(
            config('rate-limiting.headers.reset'),
            (string) now()->addSeconds($resetIn)->timestamp
        );

        return $response;
    }
}
