<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate Limiting Service - FASE 1.5
 *
 * Servicio centralizado para manejar límites de rate limiting granulares
 * con diferenciación entre usuarios autenticados y guests.
 *
 * @author Jeremy Arias Solano
 */
class RateLimitingService
{
    /**
     * Obtener el límite de rate limit para una solicitud específica
     */
    public static function getLimit(Request $request, string $key = 'api'): int
    {
        $config = config('rate-limiting.users');
        
        // Valores por defecto si não está la configuración
        if (!$config) {
            $config = [
                'authenticated' => [
                    'api' => 60,
                    'reports' => 15,
                    'imports' => 5,
                    'exports' => 10,
                    'hacienda' => 30,
                    'payment_process' => 5,
                    'login' => 5,
                ],
                'guest' => [
                    'api' => 30,
                    'reports' => 5,
                    'imports' => 1,
                    'exports' => 2,
                    'hacienda' => 10,
                    'payment_process' => 0,
                    'login' => 5,
                ],
            ];
        }

        // Determinar si el solicitante está autenticado y seleccionar
        // la política correspondiente. Esto permite límites más altos
        // para usuarios autenticados frente a invitados.
        $isAuthenticated = $request->user() !== null;
        $userType = $isAuthenticated ? 'authenticated' : 'guest';
        
        return $config[$userType][$key] ?? $config['guest']['api'];
    }

    /**
     * Obtener el identificador único para rate limiting
     *
     * Utiliza user_id para usuarios autenticados, IP para guests
     */
    public static function getIdentifier(Request $request): string|int
    {
        // Identificador único por el que se aplicará el rate limit.
        // Preferimos user_id cuando exista para limitar por cuenta;
        // en caso contrario usamos la IP del cliente.
        return $request->user()->id ?? $request->ip() ?? 'unknown';
    }

    /**
     * Verificar si se ha excedido el límite
     */
    public static function isExceeded(Request $request, string $key = 'api'): bool
    {
        $identifier = self::getIdentifier($request);
        $limit = self::getLimit($request, $key);
        $cacheKey = "rate_limit:{$key}:{$identifier}";

        $attempts = (int) Cache::get($cacheKey, 0);
        return $attempts >= $limit;
    }

    /**
     * Incrementar contador de intentos
     */
    public static function increment(Request $request, string $key = 'api'): int
    {
        $identifier = self::getIdentifier($request);
        $cacheKey = "rate_limit:{$key}:{$identifier}";
        $ttl = 60; // 1 minuto por defecto

        $attempts = (int) Cache::get($cacheKey, 0);
        $attempts++;

        if ($attempts === 1) {
            // Primera vez, establecer TTL
            Cache::set($cacheKey, $attempts, $ttl);
        } else {
            Cache::increment($cacheKey);
        }

        return $attempts;
    }

    /**
     * Obtener intentos restantes
     */
    public static function remaining(Request $request, string $key = 'api'): int
    {
        $identifier = self::getIdentifier($request);
        $limit = self::getLimit($request, $key);
        $cacheKey = "rate_limit:{$key}:{$identifier}";

        $attempts = (int) Cache::get($cacheKey, 0);
        // Devuelve el número de intentos que quedan antes de alcanzar
        // el límite. Útil para cabeceras como `X-RateLimit-Remaining`.
        return max(0, $limit - $attempts);
    }

    /**
     * Obtener tiempo hasta reset (en segundos)
     */
    public static function resetIn(Request $request, string $key = 'api'): int
    {
        $identifier = self::getIdentifier($request);
        $cacheKey = "rate_limit:{$key}:{$identifier}";

        $expiresAt = Cache::get($cacheKey . ':expires_at');
        if (!$expiresAt) {
            return 0;
        }

        $remaining = $expiresAt - time();
        return (int) max(0, $remaining);
    }

    /**
     * Registrar violación de rate limit
     */
    public static function logViolation(Request $request, string $key = 'api'): void
    {
        $identifier = self::getIdentifier($request);
        $user = $request->user();

        $context = [
            'limiter_key' => $key,
            'identifier' => $identifier,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('security')->warning(
            'Rate limit exceeded: ' . $key,
            $context
        );

        // Registrar la violación y evaluar si la IP debe ser bloqueada
        // por múltiples violaciones repetidas.
        self::checkAndBlockIP($request);
    }

    /**
     * Verificar y bloquear IP si excede threshold
     */
    private static function checkAndBlockIP(Request $request): void
    {
        if (!config('rate-limiting.ip_blocking.enabled')) {
            return;
        }

        $ip = $request->ip();
        $violationsCacheKey = "ip_violations:{$ip}";
        $blockedCacheKey = "ip_blocked:{$ip}";

        // Incrementar contador de violaciones
        $violations = (int) Cache::get($violationsCacheKey, 0);
        $violations++;
        Cache::set($violationsCacheKey, $violations, 3600); // 1 hora

        $threshold = config('rate-limiting.ip_blocking.threshold');
        if ($violations >= $threshold) {
            $blockDuration = config('rate-limiting.ip_blocking.block_duration');
            Cache::set($blockedCacheKey, true, $blockDuration);

            Log::channel('security')->critical(
                'IP blocked due to excessive rate limit violations',
                [
                    'ip' => $ip,
                    'violations' => $violations,
                    'block_duration_seconds' => $blockDuration,
                ]
            );
        }
    }

    /**
     * Verificar si una IP está bloqueada
     */
    public static function isIPBlocked(Request $request): bool
    {
        if (!config('rate-limiting.ip_blocking.enabled')) {
            return false;
        }

        $ip = $request->ip();

        // Verificar excepciones
        $exceptions = config('rate-limiting.exceptions.ips', []);
        if (in_array($ip, $exceptions)) {
            return false;
        }

        $blockedCacheKey = "ip_blocked:{$ip}";
        return (bool) Cache::get($blockedCacheKey, false);
    }

    /**
     * Limpiar límites de rate para un usuario (admin only)
     */
    public static function resetUser(int $userId, string $key = 'api'): void
    {
        $cacheKey = "rate_limit:{$key}:{$userId}";
        Cache::forget($cacheKey);

        Log::channel('security')->info(
            'Rate limit reset for user',
            ['user_id' => $userId, 'key' => $key]
        );
    }

    /**
     * Limpiar límites de rate para una IP (admin only)
     */
    public static function resetIP(string $ip, string $key = 'api'): void
    {
        $cacheKey = "rate_limit:{$key}:{$ip}";
        Cache::forget($cacheKey);
        Cache::forget("ip_blocked:{$ip}");
        Cache::forget("ip_violations:{$ip}");

        Log::channel('security')->info(
            'Rate limit reset for IP',
            ['ip' => $ip, 'key' => $key]
        );
    }

    /**
     * Obtener estadísticas de rate limiting
     * 
     * @return array<string, mixed>
     */
    public static function getStats(string $key = 'api'): array
    {
        $violations = Cache::get("rate_limit_violations:{$key}", []);
        $blockedIPs = Cache::get("blocked_ips", []);

        return [
            'key' => $key,
            'total_violations' => count($violations),
            'blocked_ips' => count($blockedIPs),
            'violations' => $violations,
            'blocked_ips_list' => $blockedIPs,
        ];
    }
}
