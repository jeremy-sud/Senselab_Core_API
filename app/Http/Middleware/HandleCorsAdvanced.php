<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware Personalizado de CORS con Logging y Auditoría
 *
 * Complementa el middleware nativo HandleCors de Laravel
 * proporcionando logging detallado, validación adicional de orígenes
 * y protección contra ataques CORS
 *
 * FASE 1.2: CORS + Security Headers
 * @see https://owasp.org/www-community/CORS_Misconfiguration
 */
class HandleCorsAdvanced
{
    /**
     * Handle an incoming CORS request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener configuración CORS desde config/cors.php
        /** @var array<int, string> $allowedOrigins */
        $allowedOrigins = config('cors.allowed_origins', []);
        $requestOrigin = $request->header('Origin');

        // Log de solicitud CORS (útil para auditoría)
        if ($requestOrigin) {
            Log::channel('cors')->debug('CORS Request', [
                'origin' => $requestOrigin,
                'method' => $request->method(),
                'path' => $request->path(),
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        // Ejecutar el siguiente middleware (HandleCors nativo)
        $response = $next($request);

        // Validación adicional: Si el origen no está permitido, registrar advertencia
        if ($requestOrigin && !$this->isOriginAllowed($requestOrigin, $allowedOrigins)) {
            Log::channel('cors')->warning('Blocked CORS Request', [
                'origin' => $requestOrigin,
                'method' => $request->method(),
                'path' => $request->path(),
                'reason' => 'Origin not in allowed list',
                'ip' => $request->ip(),
            ]);
        }

        // Agregar header X-Request-ID para correlacionar logs
        if (!$response->headers->has('X-Request-ID')) {
            $response->headers->set('X-Request-ID', (string) \Illuminate\Support\Str::uuid());
        }

        return $response;
    }

    /**
     * Verificar si un origen está permitido
     *
     * @param  string  $origin
     * @param  array<int, string>  $allowedOrigins
     * @return bool
     */
    private function isOriginAllowed(string $origin, array $allowedOrigins): bool
    {
        // Implementar matching básico (no incluye pattern matching)
        return in_array($origin, $allowedOrigins, true);
    }
}
