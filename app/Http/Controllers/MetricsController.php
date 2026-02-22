<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;

class MetricsController extends Controller
{
    /**
     * Récupere ou crée le registre de collectors
     */
    protected function getRegistry(): CollectorRegistry
    {
        // En production, usar Redis storage en lugar de InMemory
        static $registry = null;

        if ($registry === null) {
            // Intentar usar Redis si está disponible
            try {
                $storageClass = config('cache.default') === 'redis'
                    ? 'Prometheus\Storage\APC'
                    : 'Prometheus\Storage\InMemory';

                $registry = new CollectorRegistry(new InMemory());
            } catch (\Exception $e) {
                // Fallback a InMemory
                $registry = new CollectorRegistry(new InMemory());
            }
        }

        return $registry;
    }

    /**
     * Render métricas en formato Prometheus
     *
     * GET /metrics
     */
    public function index(): Response
    {
        try {
            // Registrar métricas de la aplicación
            $this->registerApplicationMetrics();

            // Obtener render en formato Prometheus
            $metrics = $this->renderMetrics();

            return response($metrics)
                ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            return response('Error generando métricas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Health check simplificado para monitoreo
     *
     * GET /metrics/health
     */
    public function health(): Response
    {
        try {
            $isHealthy = $this->checkHealth();
            $status = $isHealthy ? 200 : 503;

            return response(json_encode([
                'healthy' => $isHealthy,
                'timestamp' => now()->iso8601Micro(),
            ]))->header('Content-Type', 'application/json')
              ->setStatusCode($status);
        } catch (\Exception $e) {
            return response('0', 500);
        }
    }

    /**
     * Render métricas en formato Prometheus
     */
    private function renderMetrics(): string
    {
        $registry = $this->getRegistry();

        // Crear métricas simples y registrarlas
        try {
            // Gauge: Memoria usada
            $memoryGauge = $registry->getOrRegisterGauge(
                'app_memory_usage_bytes',
                'Memoria usada por la aplicación en bytes',
                ['instance']
            );
            $memoryGauge->set(memory_get_usage(true), ['instance' => gethostname()]);

            // Gauge: Pico de memoria
            $peakMemoryGauge = $registry->getOrRegisterGauge(
                'app_memory_peak_bytes',
                'Pico de memoria usada',
                ['instance']
            );
            $peakMemoryGauge->set(memory_get_peak_usage(true), ['instance' => gethostname()]);

            // Counter: Requests totales
            $requestCounter = $registry->getOrRegisterCounter(
                'http_requests_total',
                'Número total de requests HTTP',
                ['method', 'endpoint', 'status']
            );

            // Gauge: DB Connections
            $dbConnectionsGauge = $registry->getOrRegisterGauge(
                'database_connections',
                'Número de conexiones activas a BD',
                ['instance']
            );
            $dbConnectionsGauge->set($this->getActiveConnectionCount(), ['instance' => gethostname()]);

            // Gauge: Cache Hit Rate
            $cacheHitRateGauge = $registry->getOrRegisterGauge(
                'cache_hit_rate_percent',
                'Porcentaje de hits en cache',
                ['driver']
            );
            $cacheHitRateGauge->set(
                $this->getCacheHitRate(),
                ['driver' => config('cache.default')]
            );

            // Gauge: Uptime en segundos
            $uptimeGauge = $registry->getOrRegisterGauge(
                'app_uptime_seconds',
                'Uptime de la aplicación',
                ['instance']
            );
            $uptimeGauge->set($this->getServerUptimeSeconds(), ['instance' => gethostname()]);

        } catch (\Exception $e) {
            // Log pero continuar
            \Log::warning('Error registrando métricas Prometheus: ' . $e->getMessage());
        }

        // Render formato Prometheus
        return $this->formatPrometheus($registry);
    }

    /**
     * Formato simple de Prometheus (sin librería externa)
     */
    private function formatPrometheus(CollectorRegistry $registry): string
    {
        $output = "# HELP app_health_check Sistema está saludable\n";
        $output .= "# TYPE app_health_check gauge\n";
        $output .= "app_health_check{instance=\"" . gethostname() . "\"} " . ($this->checkHealth() ? '1' : '0') . "\n\n";

        // Memoria
        $output .= "# HELP app_memory_usage_bytes Memoria usada en bytes\n";
        $output .= "# TYPE app_memory_usage_bytes gauge\n";
        $output .= "app_memory_usage_bytes{instance=\"" . gethostname() . "\"} " . memory_get_usage(true) . "\n\n";

        // Peak Memory
        $output .= "# HELP app_memory_peak_bytes Pico de memoria\n";
        $output .= "# TYPE app_memory_peak_bytes gauge\n";
        $output .= "app_memory_peak_bytes{instance=\"" . gethostname() . "\"} " . memory_get_peak_usage(true) . "\n\n";

        // Uptime
        $output .= "# HELP app_uptime_seconds Uptime de la aplicación\n";
        $output .= "# TYPE app_uptime_seconds gauge\n";
        $output .= "app_uptime_seconds{instance=\"" . gethostname() . "\"} " . $this->getServerUptimeSeconds() . "\n\n";

        // Database
        $output .= "# HELP database_connections Conexiones activas a BD\n";
        $output .= "# TYPE database_connections gauge\n";
        $output .= "database_connections{instance=\"" . gethostname() . "\",driver=\"" . config('database.default') . "\"} " . $this->getActiveConnectionCount() . "\n\n";

        // Cache Hit Rate
        $output .= "# HELP cache_hit_rate_percent Porcentaje de hits en cache\n";
        $output .= "# TYPE cache_hit_rate_percent gauge\n";
        $output .= "cache_hit_rate_percent{driver=\"" . config('cache.default') . "\"} " . $this->getCacheHitRate() . "\n\n";

        return $output;
    }

    /**
     * Verificar salud del sistema
     */
    private function checkHealth(): bool
    {
        try {
            // BD
            DB::connection()->getPdo();

            // Cache
            $cacheDriver = config('cache.default', 'file');
            if ($cacheDriver === 'redis') {
                \Illuminate\Support\Facades\Redis::ping();
            }

            // Storage
            if (!is_writable(storage_path('logs'))) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener número de conexiones activas a BD
     */
    private function getActiveConnectionCount(): int
    {
        // Esto es simplificado, en producción depende del driver DB
        return 1; // Placeholder
    }

    /**
     * Simular cache hit rate (en producción usar redis info)
     */
    private function getCacheHitRate(): float
    {
        // Placeholder: en producción usar stats de Redis
        return 75.5;
    }

    /**
     * Obtener uptime en segundos
     */
    private function getServerUptimeSeconds(): int
    {
        try {
            if (PHP_OS_FAMILY === 'Linux') {
                $uptime = file_get_contents('/proc/uptime');
                if ($uptime) {
                    $parts = explode(' ', trim($uptime));
                    return (int) $parts[0];
                }
            }
        } catch (\Exception $e) {
            //
        }

        return 0;
    }
}
