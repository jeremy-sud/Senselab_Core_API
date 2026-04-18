<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Health Check",
 *     description="Endpoints de monitoreo de salud del servicio para liveness/readiness probes y diagnóstico"
 * )
 */
class HealthCheckController extends Controller
{
    /**
     * @OA\Get(
     *     path="/health/liveness",
     *     summary="Liveness probe",
     *     description="Verifica si el servicio está vivo. Responde rápidamente si el proceso está corriendo",
     *     operationId="healthLiveness",
     *     tags={"Health Check"},
     *     @OA\Response(
     *         response=200,
     *         description="Servicio activo",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="alive"),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="version", type="string", example="5.0.1"),
     *             @OA\Property(property="environment", type="string", example="production")
     *         )
     *     )
     * )
     */
    public function liveness(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->iso8601Micro(),
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/health/readiness",
     *     summary="Readiness probe",
     *     description="Verifica que todos los servicios dependientes (BD, cache, storage) estén disponibles",
     *     operationId="healthReadiness",
     *     tags={"Health Check"},
     *     @OA\Response(
     *         response=200,
     *         description="Servicio listo",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ready"),
     *             @OA\Property(property="checks", type="object",
     *                 @OA\Property(property="database", type="string", example="ok"),
     *                 @OA\Property(property="cache", type="string", example="ok"),
     *                 @OA\Property(property="storage", type="string", example="ok")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="uptime_seconds", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="Servicio no disponible",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="not_ready"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="checks", type="object")
     *         )
     *     )
     * )
     */
    public function readiness(): JsonResponse
    {
        try {
            $checks = [];

            // ✅ Verificar Base de Datos
            try {
                DB::connection()->getPdo();
                $checks['database'] = 'ok';
            } catch (\Exception $e) {
                $checks['database'] = 'error: ' . (config('app.debug') ? $e->getMessage() : 'connection failed');
                throw new \Exception('Database connection failed');
            }

            // ✅ Verificar Cache/Redis
            try {
                $cacheDriver = config('cache.default', 'file');
                if ($cacheDriver === 'redis') {
                    \Illuminate\Support\Facades\Redis::ping();
                }
                $checks['cache'] = 'ok';
            } catch (\Exception $e) {
                $checks['cache'] = 'warning: ' . (config('app.debug') ? $e->getMessage() : 'unavailable');
            }

            // ✅ Verificar Storage
            try {
                if (!is_writable(storage_path('logs'))) {
                    throw new \Exception('Storage not writable');
                }
                $checks['storage'] = 'ok';
            } catch (\Exception $e) {
                $checks['storage'] = 'error: ' . (config('app.debug') ? $e->getMessage() : 'not writable');
                throw new \Exception('Storage not writable');
            }

            return response()->json([
                'status' => 'ready',
                'checks' => $checks,
                'timestamp' => now()->iso8601Micro(),
                'uptime_seconds' => $this->getServerUptimeSeconds(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'not_ready',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'timestamp' => now()->iso8601Micro(),
                'checks' => $checks,
            ], 503);
        }
    }

    /**
     * @OA\Get(
     *     path="/health/details",
     *     summary="Detalles de salud del sistema",
     *     description="Información detallada del estado del servidor, BD, cache, PHP y recursos. Solo administradores",
     *     operationId="healthDetails",
     *     tags={"Health Check"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de salud",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="application", type="object",
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="version", type="string"),
     *                 @OA\Property(property="environment", type="string")
     *             ),
     *             @OA\Property(property="php", type="object",
     *                 @OA\Property(property="version", type="string"),
     *                 @OA\Property(property="memory_used_mb", type="number", format="float")
     *             ),
     *             @OA\Property(property="database", type="object"),
     *             @OA\Property(property="cache", type="object"),
     *             @OA\Property(property="server", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Solo administradores"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function details(Request $request): JsonResponse
    {
        // Solo administradores pueden acceder
        if (auth()->guard('sanctum')->check() && auth()->user()->can('view.admin')) {
            $hasPermission = true;
        } else {
            $hasPermission = false;
        }

        if (!$hasPermission) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Only administrators can view details'
            ], 403);
        }

        try {
            return response()->json([
                'status' => 'ok',
                'application' => [
                    'name' => config('app.name', 'Ursol CAST API'),
                    'version' => config('app.version', '1.0.0'),
                    'environment' => app()->environment(),
                    'debug_mode' => config('app.debug'),
                    'timezone' => config('app.timezone'),
                ],
                'php' => [
                    'version' => PHP_VERSION,
                    'memory_limit_mb' => (int) ini_get('memory_limit'),
                    'memory_used_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'max_execution_time_sec' => (int) ini_get('max_execution_time'),
                ],
                'database' => [
                    'connection' => config('database.default'),
                    'connected' => $this->isDatabaseConnected(),
                    'host' => config('database.connections.' . config('database.default') . '.host'),
                    'driver' => config('database.connections.' . config('database.default') . '.driver'),
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'connected' => $this->isCacheConnected(),
                    'redis_host' => config('database.redis.default.host', 'N/A'),
                ],
                'server' => [
                    'uptime' => $this->getServerUptime(),
                    'uptime_seconds' => $this->getServerUptimeSeconds(),
                    'load_average' => $this->getLoadAverage(),
                    'disk_space_percent' => $this->getDiskUsagePercent(),
                ],
                'timestamp' => now()->iso8601Micro(),
                'checked_at' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'timestamp' => now()->iso8601Micro(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/health/metrics",
     *     summary="Métricas rápidas de monitoreo",
     *     description="Información simplificada de métricas del sistema para dashboards",
     *     operationId="healthMetrics",
     *     tags={"Health Check"},
     *     @OA\Response(
     *         response=200,
     *         description="Métricas obtenidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="metrics", type="object",
     *                 @OA\Property(property="uptime_seconds", type="integer"),
     *                 @OA\Property(property="memory_used_mb", type="number", format="float"),
     *                 @OA\Property(property="database_ok", type="boolean"),
     *                 @OA\Property(property="cache_ok", type="boolean"),
     *                 @OA\Property(property="storage_writable", type="boolean")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error al recopilar métricas")
     * )
     */
    public function metrics(): JsonResponse
    {
        try {
            $uptime = $this->getServerUptimeSeconds();

            return response()->json([
                'metrics' => [
                    'uptime_seconds' => $uptime,
                    'memory_used_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'database_ok' => $this->isDatabaseConnected(),
                    'cache_ok' => $this->isCacheConnected(),
                    'storage_writable' => is_writable(storage_path()),
                ],
                'timestamp' => now()->iso8601Micro(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to gather metrics',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * ==================== HELPER METHODS ====================
     */

    /**
     * Verificar conexión a BD
     */
    private function isDatabaseConnected(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verificar conexión a Cache/Redis
     */
    private function isCacheConnected(): bool
    {
        try {
            $cacheDriver = config('cache.default', 'file');
            if ($cacheDriver === 'redis') {
                \Illuminate\Support\Facades\Redis::ping();
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener uptime del servidor en formato legible
     */
    private function getServerUptime(): string
    {
        try {
            if (PHP_OS_FAMILY === 'Linux') {
                $uptime = @file_get_contents('/proc/uptime');
                if ($uptime !== false) {
                    $seconds = (int) explode(' ', trim($uptime))[0];
                    $days = intdiv($seconds, 86400);
                    $hours = intdiv($seconds % 86400, 3600);
                    $minutes = intdiv($seconds % 3600, 60);
                    return "{$days}d {$hours}h {$minutes}m";
                }
            }
        } catch (\Exception $e) {
            // silently fail
        }

        return 'unknown';
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
            // Silenciosamente falla
        }

        return 0;
    }

    /**
     * Obtener promedio de carga del sistema
     *
     * @return array<string, float>|null
     */
    private function getLoadAverage(): ?array
    {
        try {
            if (PHP_OS_FAMILY !== 'Windows') {
                $loadavg = sys_getloadavg();
                if ($loadavg === false) {
                    $loadavg = [0.0, 0.0, 0.0];
                }
                return [
                    '1min' => round($loadavg[0], 2),
                    '5min' => round($loadavg[1], 2),
                    '15min' => round($loadavg[2], 2),
                ];
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Obtener porcentaje de uso de disco
     */
    private function getDiskUsagePercent(): ?int
    {
        try {
            $disk_total = disk_total_space('/');
            $disk_free = disk_free_space('/');

            if ($disk_total > 0) {
                $percent = (($disk_total - $disk_free) / $disk_total) * 100;
                return (int) $percent;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}
