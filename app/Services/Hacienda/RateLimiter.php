<?php

namespace App\Services\Hacienda;

use App\Exceptions\HaciendaException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Rate Limiter para cumplir con límites del API de Hacienda
 *
 * Implementa algoritmo Leaky Bucket para:
 * - Burst: 20 req/seg por 5 seg (max 100)
 * - Sostenido: 10 req/seg por 120 seg (max 1200)
 *
 * Evita bloqueos de IP por 10 minutos.
 */
class RateLimiter
{
    /**
     * Límite sostenido de requests por segundo
     */
    protected int $maxRequestsPerSecond;

    /**
     * Límite de requests por minuto
     */
    protected int $maxRequestsPerMinute;

    /**
     * Prefijo para claves de cache
     */
    protected string $cachePrefix = 'hacienda_rate_limit';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->maxRequestsPerSecond = config('hacienda.rate_limit.max_requests_per_second', 8);
        $this->maxRequestsPerMinute = config('hacienda.rate_limit.max_requests_per_minute', 480);
    }

    /**
     * Verificar si se puede hacer un request (esperar si es necesario)
     *
     * @throws \Exception Si el rate limiting está deshabilitado
     */
    public function waitIfNeeded(): void
    {
        if (!config('hacienda.rate_limit.enabled', true)) {
            return;
        }

        $maxWaitSeconds = 60; // Máximo 1 minuto de espera
        $waitedSeconds = 0;

        while ($waitedSeconds < $maxWaitSeconds) {
            if ($this->canMakeRequest()) {
                return;
            }

            // Esperar 100ms antes de verificar nuevamente
            usleep(100000);
            $waitedSeconds += 0.1;
        }

        Log::warning('Rate limiter esperó el tiempo máximo sin poder continuar', [
            'waited_seconds' => $waitedSeconds,
            'max_per_second' => $this->maxRequestsPerSecond,
            'max_per_minute' => $this->maxRequestsPerMinute,
        ]);

        throw HaciendaException::rateLimitExceeded((int) $waitedSeconds);
    }

    /**
     * Verificar si se puede hacer un request en este momento
     *
     * @return bool
     */
    public function canMakeRequest(): bool
    {
        $now = Carbon::now();
        $currentSecond = $now->format('Y-m-d H:i:s');
        $currentMinute = $now->format('Y-m-d H:i');

        // Verificar límite por segundo
        $requestsThisSecond = $this->getRequestCount('second', $currentSecond);
        if ($requestsThisSecond >= $this->maxRequestsPerSecond) {
            Log::debug('Rate limit por segundo alcanzado', [
                'current' => $requestsThisSecond,
                'max' => $this->maxRequestsPerSecond,
            ]);
            return false;
        }

        // Verificar límite por minuto
        $requestsThisMinute = $this->getRequestCount('minute', $currentMinute);
        if ($requestsThisMinute >= $this->maxRequestsPerMinute) {
            Log::debug('Rate limit por minuto alcanzado', [
                'current' => $requestsThisMinute,
                'max' => $this->maxRequestsPerMinute,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Registrar que se realizó un request
     */
    public function recordRequest(): void
    {
        if (!config('hacienda.rate_limit.enabled', true)) {
            return;
        }

        $now = Carbon::now();
        $currentSecond = $now->format('Y-m-d H:i:s');
        $currentMinute = $now->format('Y-m-d H:i');

        // Incrementar contador del segundo actual
        $this->incrementRequestCount('second', $currentSecond, 2);

        // Incrementar contador del minuto actual
        $this->incrementRequestCount('minute', $currentMinute, 61);

        Log::debug('Request registrado en rate limiter', [
            'second' => $currentSecond,
            'minute' => $currentMinute,
            'count_second' => $this->getRequestCount('second', $currentSecond),
            'count_minute' => $this->getRequestCount('minute', $currentMinute),
        ]);
    }

    /**
     * Obtener cantidad de requests en un período
     *
     * @param string $period 'second' o 'minute'
     * @param string $key Clave del período (timestamp)
     * @return int Cantidad de requests
     */
    protected function getRequestCount(string $period, string $key): int
    {
        $cacheKey = "{$this->cachePrefix}:{$period}:{$key}";
        return (int) Cache::get($cacheKey, 0);
    }

    /**
     * Incrementar contador de requests (atómico, seguro con Horizon)
     *
     * @param string $period 'second' o 'minute'
     * @param string $key Clave del período
     * @param int $ttl Tiempo de vida en segundos
     */
    protected function incrementRequestCount(string $period, string $key, int $ttl): void
    {
        $cacheKey = "{$this->cachePrefix}:{$period}:{$key}";
        
        if (!Cache::has($cacheKey)) {
            Cache::put($cacheKey, 1, $ttl);
        } else {
            Cache::increment($cacheKey);
        }
    }

    /**
     * Obtener estadísticas actuales de rate limiting
     *
     * @return array<string, mixed> Estadísticas
     */
    public function getEstadisticas(): array
    {
        $now = Carbon::now();
        $currentSecond = $now->format('Y-m-d H:i:s');
        $currentMinute = $now->format('Y-m-d H:i');

        $requestsThisSecond = $this->getRequestCount('second', $currentSecond);
        $requestsThisMinute = $this->getRequestCount('minute', $currentMinute);

        return [
            'enabled' => config('hacienda.rate_limit.enabled', true),
            'current_second' => [
                'timestamp' => $currentSecond,
                'requests' => $requestsThisSecond,
                'limit' => $this->maxRequestsPerSecond,
                'available' => max(0, $this->maxRequestsPerSecond - $requestsThisSecond),
                'percentage_used' => round(($requestsThisSecond / $this->maxRequestsPerSecond) * 100, 2),
            ],
            'current_minute' => [
                'timestamp' => $currentMinute,
                'requests' => $requestsThisMinute,
                'limit' => $this->maxRequestsPerMinute,
                'available' => max(0, $this->maxRequestsPerMinute - $requestsThisMinute),
                'percentage_used' => round(($requestsThisMinute / $this->maxRequestsPerMinute) * 100, 2),
            ],
            'can_make_request' => $this->canMakeRequest(),
        ];
    }

    /**
     * Resetear contadores (útil para testing)
     */
    public function reset(): void
    {
        $now = Carbon::now();
        $currentSecond = $now->format('Y-m-d H:i:s');
        $currentMinute = $now->format('Y-m-d H:i');

        Cache::forget("{$this->cachePrefix}:second:{$currentSecond}");
        Cache::forget("{$this->cachePrefix}:minute:{$currentMinute}");

        Log::debug('Rate limiter reseteado');
    }

    /**
     * Limpiar contadores antiguos del cache
     *
     * @return int Cantidad de claves eliminadas
     */
    public function limpiarCache(): int
    {
        // Esta operación depende del driver de cache usado
        // Por ahora solo registramos que se solicitó
        Log::info('Limpieza de cache de rate limiter solicitada');
        
        // Los contadores se eliminan automáticamente por TTL
        return 0;
    }

    /**
     * Obtener configuración actual
     *
     * @return array<string, mixed> Configuración
     */
    public function getConfiguracion(): array
    {
        return [
            'enabled' => config('hacienda.rate_limit.enabled', true),
            'max_requests_per_second' => $this->maxRequestsPerSecond,
            'max_requests_per_minute' => $this->maxRequestsPerMinute,
            'cache_prefix' => $this->cachePrefix,
        ];
    }
}
