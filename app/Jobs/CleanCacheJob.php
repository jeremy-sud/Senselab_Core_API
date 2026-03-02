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

    public int $tries = 2;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    /**
     * @param array<int,string> $tags
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

    /**
     * @return array{action:string,success:bool,message:string}
     */
    protected function cleanAll(): array
    {
        Cache::flush();
        
        return [
            'action' => 'flush_all',
            'success' => true,
            'message' => 'Todo el cache fue limpiado',
        ];
    }

    /**
     * @return array{action:string,success:bool,message?:string,tags_cleaned?:array<int,string>}
     */
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

    /**
     * @return array{action:string,success:bool,keys_cleaned?:int,message?:string}
     */
    protected function cleanExpired(): array
    {
        // Laravel limpia automáticamente las entradas expiradas
        // Aquí podemos forzar la limpieza de cache driver específico
        
        if (config('cache.default') === 'redis') {
            $cleaned = 0;

            try {
                /** @var \Illuminate\Redis\Connections\Connection $connection */
                $connection = \Illuminate\Support\Facades\Redis::connection();
                /** @var \Redis|object $redis */
                $redis = $connection->client();

                if ($redis instanceof \Redis) {
                    $cursor = null;
                    do {
                        /** @var array<int, string>|false $keys */
                        $keys = $redis->scan($cursor, 'laravel_cache:*', 100);

                        if (is_array($keys)) {
                            foreach ($keys as $key) {
                                /** @var int $ttl */
                                $ttl = $redis->ttl($key);
                                if ($ttl === -2) {
                                    $redis->del($key);
                                    $cleaned++;
                                }
                            }
                        }
                    } while ($cursor > 0);
                }
            } catch (\Throwable $e) {
                Log::debug('CleanCacheJob: Redis scan no disponible', ['error' => $e->getMessage()]);
            }

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

    /**
     * @return array{action:string,success:bool,sessions_deleted:int}
     */
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

    /**
     * @return array{action:string,success:bool,audit_deleted:int,access_deleted:int,total_deleted:int}
     */
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
