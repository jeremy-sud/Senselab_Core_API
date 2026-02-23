<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleApiRequests
{
    public function __construct(
        protected RateLimiter $limiter,
    ) {}

    /**
     * Handle an incoming request.
     *
     * Aplica rate limiting por usuario autenticado o IP.
     * Límite configurable via env: API_RATE_LIMIT (default: 120 req/min).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 0, int $decayMinutes = 1): Response
    {
        $maxAttempts = $maxAttempts ?: (int) config('app.api_rate_limit', 120);
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'message' => 'Demasiadas solicitudes. Intente de nuevo en ' . $retryAfter . ' segundos.',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $this->limiter->remaining($key, $maxAttempts),
        ]);
    }

    /**
     * Resolver la firma única del request para rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $userId = $request->user()?->id;

        return 'api_throttle:' . ($userId ? 'user:' . $userId : 'ip:' . $request->ip());
    }
}
