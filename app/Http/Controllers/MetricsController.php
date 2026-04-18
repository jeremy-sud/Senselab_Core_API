<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

/**
 * Controller: MetricsController - Métricas de la aplicación en formato Prometheus
 *
 * Proporciona endpoints de métricas y health check para monitoreo.
 * Genera output compatible con Prometheus sin dependencias externas.
 *
 * @OA\Tag(
 *     name="Metrics",
 *     description="Endpoints de métricas en formato Prometheus para monitoreo y alertas"
 * )
 *
 * @package App\Http\Controllers
 * @version 2.3.0
 */
class MetricsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/metrics",
     *     summary="Métricas Prometheus",
     *     description="Expone métricas del sistema en formato Prometheus text/plain 0.0.4",
     *     operationId="getPrometheusMetrics",
     *     tags={"Metrics"},
     *     @OA\Response(
     *         response=200,
     *         description="Métricas en formato Prometheus (text/plain 0.0.4)",
     *         @OA\MediaType(
     *             mediaType="text/plain",
     *             @OA\Schema(type="string", example="# HELP app_health_check gauge\n# TYPE app_health_check gauge\napp_health_check 1")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error generando métricas")
     * )
     */
    public function index(): Response
    {
        try {
            $metrics = $this->collectAllMetrics();
            $output = $this->formatPrometheus($metrics);

            return response($output)
                ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('Error generando métricas', ['error' => $e->getMessage()]);
            return response(config('app.debug') ? 'Error generando métricas: ' . $e->getMessage() : 'Error generando métricas', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/metrics/health",
     *     summary="Health check simplificado",
     *     description="Endpoint de health check rápido para monitoreo externo",
     *     operationId="getMetricsHealth",
     *     tags={"Metrics"},
     *     @OA\Response(
     *         response=200,
     *         description="Sistema saludable",
     *         @OA\JsonContent(
     *             @OA\Property(property="healthy", type="boolean", example=true),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="Sistema no saludable",
     *         @OA\JsonContent(
     *             @OA\Property(property="healthy", type="boolean", example=false),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function health(): Response
    {
        try {
            $isHealthy = $this->checkHealth();
            $status = $isHealthy ? 200 : 503;

            return response((string) json_encode([
                'healthy' => $isHealthy,
                'timestamp' => now()->iso8601Micro(),
            ]))->header('Content-Type', 'application/json')
              ->setStatusCode($status);
        } catch (\Exception $e) {
            return response('0', 500);
        }
    }

    /**
     * Recolectar todas las métricas de la aplicación.
     *
     * @return array<int, array{name: string, help: string, type: string, value: float|int, labels: array<string, string>}>
     */
    private function collectAllMetrics(): array
    {
        $hostname = gethostname() ?: 'unknown';
        $instanceLabels = ['instance' => $hostname];

        return [
            [
                'name' => 'app_health_check',
                'help' => 'Sistema está saludable (1=sí, 0=no)',
                'type' => 'gauge',
                'value' => $this->checkHealth() ? 1 : 0,
                'labels' => $instanceLabels,
            ],
            [
                'name' => 'app_memory_usage_bytes',
                'help' => 'Memoria usada por la aplicación en bytes',
                'type' => 'gauge',
                'value' => memory_get_usage(true),
                'labels' => $instanceLabels,
            ],
            [
                'name' => 'app_memory_peak_bytes',
                'help' => 'Pico de memoria usada en bytes',
                'type' => 'gauge',
                'value' => memory_get_peak_usage(true),
                'labels' => $instanceLabels,
            ],
            [
                'name' => 'app_uptime_seconds',
                'help' => 'Uptime del servidor en segundos',
                'type' => 'gauge',
                'value' => $this->getServerUptimeSeconds(),
                'labels' => $instanceLabels,
            ],
            [
                'name' => 'database_connections_active',
                'help' => 'Número de conexiones activas a la base de datos',
                'type' => 'gauge',
                'value' => $this->getActiveConnectionCount(),
                'labels' => array_merge($instanceLabels, ['driver' => (string) config('database.default')]),
            ],
            [
                'name' => 'cache_hit_rate_percent',
                'help' => 'Porcentaje de hits en cache',
                'type' => 'gauge',
                'value' => $this->getCacheHitRate(),
                'labels' => ['driver' => (string) config('cache.default')],
            ],
            [
                'name' => 'database_query_count',
                'help' => 'Número de queries ejecutados en el request actual',
                'type' => 'gauge',
                'value' => count(DB::getQueryLog()),
                'labels' => $instanceLabels,
            ],
            [
                'name' => 'php_info',
                'help' => 'Versión de PHP',
                'type' => 'gauge',
                'value' => 1,
                'labels' => array_merge($instanceLabels, ['version' => PHP_VERSION]),
            ],
        ];
    }

    /**
     * Formatear métricas al formato de exposición Prometheus.
     *
     * @param array<int, array{name: string, help: string, type: string, value: float|int, labels: array<string, string>}> $metrics
     */
    private function formatPrometheus(array $metrics): string
    {
        $output = '';

        foreach ($metrics as $metric) {
            $output .= "# HELP {$metric['name']} {$metric['help']}\n";
            $output .= "# TYPE {$metric['name']} {$metric['type']}\n";

            $labelParts = [];
            foreach ($metric['labels'] as $key => $value) {
                $labelParts[] = "{$key}=\"{$value}\"";
            }
            $labelString = implode(',', $labelParts);

            $output .= "{$metric['name']}{{$labelString}} {$metric['value']}\n\n";
        }

        return $output;
    }

    /**
     * Verificar salud del sistema (BD, cache, storage).
     */
    private function checkHealth(): bool
    {
        try {
            DB::connection()->getPdo();

            $cacheDriver = config('cache.default', 'file');
            if ($cacheDriver === 'redis') {
                \Illuminate\Support\Facades\Redis::ping();
            }

            if (!is_writable(storage_path('logs'))) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener número de conexiones activas a BD.
     * Usa SHOW STATUS de MySQL o pg_stat_activity de PostgreSQL.
     */
    private function getActiveConnectionCount(): int
    {
        try {
            $driver = config('database.default');

            if ($driver === 'mysql') {
                $result = DB::select("SHOW STATUS WHERE Variable_name = 'Threads_connected'");
                return (int) ($result[0]->Value ?? 1);
            }

            if ($driver === 'pgsql') {
                $result = DB::select('SELECT count(*) as total FROM pg_stat_activity WHERE state IS NOT NULL');
                return (int) ($result[0]->total ?? 1);
            }
        } catch (\Exception $e) {
            Log::debug('No se pudo obtener conexiones activas de BD', ['error' => $e->getMessage()]);
        }

        return 1;
    }

    /**
     * Obtener cache hit rate real desde Redis INFO stats o devolver N/A.
     */
    private function getCacheHitRate(): float
    {
        try {
            $cacheDriver = config('cache.default');

            if ($cacheDriver === 'redis') {
                /** @var \Illuminate\Redis\Connections\Connection $connection */
                $connection = \Illuminate\Support\Facades\Redis::connection();
                /** @var array<string, string>|string $info */
                $info = $connection->command('info', ['stats']);

                if (is_array($info)) {
                    $hits = (int) ($info['keyspace_hits'] ?? 0);
                    $misses = (int) ($info['keyspace_misses'] ?? 0);
                    $total = $hits + $misses;

                    return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
                }
            }
        } catch (\Exception $e) {
            Log::debug('No se pudo obtener cache hit rate', ['error' => $e->getMessage()]);
        }

        return 0.0;
    }

    /**
     * Obtener uptime del servidor en segundos.
     */
    private function getServerUptimeSeconds(): int
    {
        try {
            if (PHP_OS_FAMILY === 'Linux') {
                $uptime = file_get_contents('/proc/uptime');
                if ($uptime !== false) {
                    $parts = explode(' ', trim($uptime));
                    return (int) $parts[0];
                }
            }
        } catch (\Exception $e) {
            // Sistema no soporta /proc/uptime
        }

        return 0;
    }
}
