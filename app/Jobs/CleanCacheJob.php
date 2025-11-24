<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Job para limpiar cache y datos obsoletos de forma asíncrona
 * Sprint 8.4 - Queue Jobs
 */
class CleanCacheJob implements ShouldQueue
{
    use Queueable;

    public $tries = 2;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $cleanType = 'all',
        public array $tags = []
    ) {
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('CleanCacheJob: Iniciando limpieza de cache', [
                'clean_type' => $this->cleanType,
                'tags' => $this->tags,
            ]);

            $result = match($this->cleanType) {
                'all' => $this->cleanAll(),
                'tags' => $this->cleanByTags(),
                'expired' => $this->cleanExpired(),
                'sessions' => $this->cleanSessions(),
                'logs' => $this->cleanOldLogs(),
                default => throw new \InvalidArgumentException("Tipo de limpieza no soportado: {$this->cleanType}")
            };

            Log::info('CleanCacheJob: Limpieza completada', [
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('CleanCacheJob: Error en limpieza de cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function cleanAll(): array
    {
        Cache::flush();
        
        return [
            'action' => 'flush_all',
            'success' => true,
            'message' => 'Todo el cache fue limpiado',
        ];
    }

    protected function cleanByTags(): array
    {
        if (empty($this->tags)) {
            return ['action' => 'clean_tags', 'success' => false, 'message' => 'No tags provided'];
        }

        foreach ($this->tags as $tag) {
            Cache::tags($tag)->flush();
        }

        return [
            'action' => 'clean_tags',
            'success' => true,
            'tags_cleaned' => $this->tags,
            'message' => 'Cache de tags limpiado: ' . implode(', ', $this->tags),
        ];
    }

    protected function cleanExpired(): array
    {
        // Laravel limpia automáticamente las entradas expiradas
        // Aquí podemos forzar la limpieza de cache driver específico
        
        if (config('cache.default') === 'redis') {
            // Limpiar keys expiradas en Redis
            $redis = Cache::getRedis();
            $cleaned = 0;
            
            // Scan por keys con patrón laravel_cache:*
            $cursor = 0;
            do {
                $result = $redis->scan($cursor, 'MATCH', 'laravel_cache:*', 'COUNT', 100);
                $cursor = $result[0];
                $keys = $result[1] ?? [];
                
                foreach ($keys as $key) {
                    $ttl = $redis->ttl($key);
                    if ($ttl === -2) { // Key expirada
                        $redis->del($key);
                        $cleaned++;
                    }
                }
            } while ($cursor != 0);

            return [
                'action' => 'clean_expired',
                'success' => true,
                'keys_cleaned' => $cleaned,
            ];
        }

        return [
            'action' => 'clean_expired',
            'success' => true,
            'message' => 'Laravel auto-limpia entradas expiradas',
        ];
    }

    protected function cleanSessions(): array
    {
        // Limpiar sesiones expiradas de la tabla sessions
        $deleted = DB::table('sessions')
            ->where('last_activity', '<', now()->subHours(24)->timestamp)
            ->delete();

        return [
            'action' => 'clean_sessions',
            'success' => true,
            'sessions_deleted' => $deleted,
        ];
    }

    protected function cleanOldLogs(): array
    {
        // Limpiar logs de auditoría antiguos (>90 días)
        $deletedAudit = DB::table('auditoria_actividad')
            ->where('fecha_hora', '<', now()->subDays(90))
            ->delete();

        $deletedAccess = DB::table('log_acceso_sistema')
            ->where('fecha_hora', '<', now()->subDays(90))
            ->delete();

        return [
            'action' => 'clean_logs',
            'success' => true,
            'audit_deleted' => $deletedAudit,
            'access_deleted' => $deletedAccess,
            'total_deleted' => $deletedAudit + $deletedAccess,
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CleanCacheJob: Job failed', [
            'clean_type' => $this->cleanType,
            'error' => $exception->getMessage(),
        ]);
    }
}
