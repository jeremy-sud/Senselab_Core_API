<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RequestMetricsMiddleware
{
    /**
     * Rastrear métricas de requests HTTP
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Inicio del request
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Procesar request
        $response = $next($request);

        // Calcular métricas
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $duration = ($endTime - $startTime) * 1000; // MS
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB

        // Obtener información del request
        $method = $request->getMethod();
        $endpoint = $request->path();
        $statusCode = $response->status();

        // Registrar métrica
        $this->recordMetric($method, $endpoint, $statusCode, $duration, $memoryUsed);

        // Agregar headers de timing
        $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', round($memoryUsed, 2) . 'MB');

        return $response;
    }

    /**
     * Registrar métrica de request
     */
    private function recordMetric(
        string $method,
        string $endpoint,
        int $statusCode,
        float $duration,
        float $memory
    ): void {
        try {
            // Clave para las métricas en cache
            $cacheKey = "metrics_request_{$method}_{$endpoint}";

            // Obtener o inicializar estadísticas
            $stats = Cache::get($cacheKey, [
                'count' => 0,
                'total_duration' => 0,
                'total_memory' => 0,
                'min_duration' => PHP_INT_MAX,
                'max_duration' => 0,
                'status_codes' => [],
                'last_recorded' => null,
            ]);

            // Actualizar estadísticas
            $stats['count']++;
            $stats['total_duration'] += $duration;
            $stats['total_memory'] += $memory;
            $stats['min_duration'] = min($stats['min_duration'], $duration);
            $stats['max_duration'] = max($stats['max_duration'], $duration);
            $stats['status_codes'][$statusCode] = ($stats['status_codes'][$statusCode] ?? 0) + 1;
            $stats['last_recorded'] = now()->iso8601Micro();

            // Guardar en cache (24 horas)
            Cache::put($cacheKey, $stats, 86400);

            // Log si es lento (> 1 segundo)
            if ($duration > 1000) {
                Log::warning('Slow request detected', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'duration_ms' => round($duration, 2),
                    'memory_mb' => round($memory, 2),
                    'status_code' => $statusCode,
                    'timestamp' => now()->iso8601Micro(),
                ]);
            }

            // Log si error (5xx)
            if ($statusCode >= 500) {
                Log::error('Server error in request', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'duration_ms' => round($duration, 2),
                    'status_code' => $statusCode,
                    'timestamp' => now()->iso8601Micro(),
                ]);
            }
        } catch (\Exception $e) {
            // Silenciosamente falla si hay problema al guardar métrica
            Log::debug('Error recording request metric: ' . $e->getMessage());
        }
    }

    /**
     * Obtener todas las métricas registradas
     *
     * @return array<string, mixed>
     */
    public static function getMetricsSnapshot(): array
    {
        try {
            $metrics = [
                'endpoints' => [],
                'summary' => [
                    'total_requests' => 0,
                    'average_duration_ms' => 0,
                    'average_memory_mb' => 0,
                    'error_rate_percent' => 0,
                ],
            ];

            // Buscar todas las métricas en cache
            $cacheKeys = Cache::tags(['metrics'])->get('metric_keys', []);

            foreach ($cacheKeys as $key) {
                $stats = Cache::get($key);
                if ($stats) {
                    $metrics['endpoints'][$key] = [
                        'requests' => $stats['count'],
                        'avg_duration_ms' => $stats['count'] > 0
                            ? round($stats['total_duration'] / $stats['count'], 2)
                            : 0,
                        'avg_memory_mb' => $stats['count'] > 0
                            ? round($stats['total_memory'] / $stats['count'], 2)
                            : 0,
                        'min_duration_ms' => $stats['min_duration'],
                        'max_duration_ms' => $stats['max_duration'],
                        'status_codes' => $stats['status_codes'],
                        'last_recorded' => $stats['last_recorded'],
                    ];

                    $metrics['summary']['total_requests'] += $stats['count'];
                }
            }

            // Calcular resumen
            if ($metrics['summary']['total_requests'] > 0) {
                $totalDuration = 0;
                $totalMemory = 0;
                $errorCount = 0;

                foreach ($metrics['endpoints'] as $endpoint) {
                    $totalDuration += $endpoint['avg_duration_ms'] * $endpoint['requests'];
                    $totalMemory += $endpoint['avg_memory_mb'] * $endpoint['requests'];

                    // Contar errores (5xx)
                    foreach ($endpoint['status_codes'] as $code => $count) {
                        if ($code >= 500) {
                            $errorCount += $count;
                        }
                    }
                }

                $metrics['summary']['average_duration_ms'] = round(
                    $totalDuration / $metrics['summary']['total_requests'],
                    2
                );
                $metrics['summary']['average_memory_mb'] = round(
                    $totalMemory / $metrics['summary']['total_requests'],
                    2
                );
                $metrics['summary']['error_rate_percent'] = round(
                    ($errorCount / $metrics['summary']['total_requests']) * 100,
                    2
                );
            }

            return $metrics;
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to get metrics snapshot',
                'message' => $e->getMessage(),
            ];
        }
    }
}
