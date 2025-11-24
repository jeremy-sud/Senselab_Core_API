<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CleanCacheJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CleanCacheJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que el job se despacha correctamente a la queue de maintenance.
     */
    public function test_job_is_dispatched_to_maintenance_queue(): void
    {
        Queue::fake();

        CleanCacheJob::dispatch('all');

        Queue::assertPushedOn('maintenance', CleanCacheJob::class);
    }

    /**
     * Test que el job se despacha con tipo 'all'.
     */
    public function test_job_is_dispatched_with_all_type(): void
    {
        Queue::fake();

        CleanCacheJob::dispatch('all');

        Queue::assertPushed(CleanCacheJob::class, function ($job) {
            return $job->cacheType === 'all';
        });
    }

    /**
     * Test que el job acepta diferentes tipos de limpieza.
     */
    public function test_job_accepts_valid_cache_types(): void
    {
        Queue::fake();

        $validTypes = ['all', 'tags', 'expired', 'sessions', 'logs'];

        foreach ($validTypes as $type) {
            CleanCacheJob::dispatch($type);

            Queue::assertPushed(CleanCacheJob::class, function ($job) use ($type) {
                return $job->cacheType === $type;
            });
        }
    }

    /**
     * Test que el job se despacha con tags específicos.
     */
    public function test_job_is_dispatched_with_specific_tags(): void
    {
        Queue::fake();

        $tags = ['productos', 'ventas', 'clientes'];

        CleanCacheJob::dispatch('tags', $tags);

        Queue::assertPushed(CleanCacheJob::class, function ($job) use ($tags) {
            return $job->cacheType === 'tags'
                && $job->tags === $tags;
        });
    }

    /**
     * Test que el job tiene configuración de retries.
     */
    public function test_job_has_retry_configuration(): void
    {
        $job = new CleanCacheJob('all');

        $this->assertEquals(2, $job->tries);
    }

    /**
     * Test que el job tiene timeout configurado.
     */
    public function test_job_has_timeout(): void
    {
        $job = new CleanCacheJob('all');

        $this->assertEquals(300, $job->timeout);
    }

    /**
     * Test que el job se despacha para limpiar cache expirado.
     */
    public function test_job_is_dispatched_for_expired_cache(): void
    {
        Queue::fake();

        CleanCacheJob::dispatch('expired');

        Queue::assertPushed(CleanCacheJob::class, function ($job) {
            return $job->cacheType === 'expired';
        });
    }

    /**
     * Test que el job se despacha para limpiar sesiones.
     */
    public function test_job_is_dispatched_for_sessions_cleanup(): void
    {
        Queue::fake();

        CleanCacheJob::dispatch('sessions');

        Queue::assertPushed(CleanCacheJob::class, function ($job) {
            return $job->cacheType === 'sessions';
        });
    }

    /**
     * Test que el job se despacha para limpiar logs.
     */
    public function test_job_is_dispatched_for_logs_cleanup(): void
    {
        Queue::fake();

        CleanCacheJob::dispatch('logs');

        Queue::assertPushed(CleanCacheJob::class, function ($job) {
            return $job->cacheType === 'logs';
        });
    }
}
