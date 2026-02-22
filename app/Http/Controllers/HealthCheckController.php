<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthCheckController extends Controller
{
    /**
     * Liveness probe - ¿Está el servicio vivo?
     * Responde rápidamente si el proceso está corriendo.
     *
     * @return JsonResponse
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
     * Readiness probe - ¿Está listo para recibir traffic?
     * Verifica que todos los servicios dependientes estén disponibles.
     *
     * @return JsonResponse
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
                $checks['database'] = 'error: ' . $e->getMessage();
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
                $checks['cache'] = 'warning: ' . $e->getMessage();
            }

            // ✅ Verificar Storage
            try {
                if (!is_writable(storage_path('logs'))) {
                    throw new \Exception('Storage not writable');
                }
                $checks['storage'] = 'ok';
            } catch (\Exception $e) {
                $checks['storage'] = 'error: ' . $e->getMessage();
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
                'error' => $e->getMessage(),
                'timestamp' => now()->iso8601Micro(),
                'checks' => $checks,
            ], 503);
        }
    }

    /**
     * Detalles de salud del sistema (requiere autenticación)
     * Información sensible sobre el estado del servidor.
     *
     * @param Request $request
     * @return JsonResponse
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
                'message' => $e->getMessage(),
                'timestamp' => now()->iso8601Micro(),
            ], 500);
        }
    }

    /**
     * Métricas rápidas de monitoreo
     * Información simplificada para dashboards.
     *
     * @return JsonResponse
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
                'message' => $e->getMessage(),
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
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows
                $output = shell_exec('wmic os get lastbootuptime | findstr /r [0-9]');
                return $output ? trim($output) : 'unknown';
            } else {
                // Linux/Unix
                $output = shell_exec('uptime -p 2>/dev/null || uptime');
                return $output ? trim(str_replace('up ', '', $output)) : 'unknown';
            }
        } catch (\Exception $e) {
            return 'unknown';
        }
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
