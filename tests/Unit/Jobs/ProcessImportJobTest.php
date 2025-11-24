<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessImportJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessImportJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que el job se despacha correctamente a la queue de imports.
     */
    public function test_job_is_dispatched_to_imports_queue(): void
    {
        Queue::fake();

        ProcessImportJob::dispatch(
            filePath: 'imports/productos.csv',
            importType: 'productos',
            empresaId: 1,
            userId: 1
        );

        Queue::assertPushedOn('imports', ProcessImportJob::class);
    }

    /**
     * Test que el job se despacha con los parámetros correctos.
     */
    public function test_job_is_dispatched_with_correct_parameters(): void
    {
        Queue::fake();

        $filePath = 'imports/clientes_2025.csv';
        $importType = 'clientes';
        $empresaId = 5;
        $userId = 10;

        ProcessImportJob::dispatch($filePath, $importType, $empresaId, $userId);

        Queue::assertPushed(ProcessImportJob::class, function ($job) use ($filePath, $importType, $empresaId, $userId) {
            return $job->filePath === $filePath
                && $job->importType === $importType
                && $job->empresaId === $empresaId
                && $job->userId === $userId;
        });
    }

    /**
     * Test que el job tiene configuración de retries.
     */
    public function test_job_has_retry_configuration(): void
    {
        $job = new ProcessImportJob('file.csv', 'productos', 1, 1);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([120, 300, 600], $job->backoff());
    }

    /**
     * Test que el job tiene timeout extendido para importaciones grandes.
     */
    public function test_job_has_extended_timeout(): void
    {
        $job = new ProcessImportJob('file.csv', 'productos', 1, 1);

        $this->assertEquals(600, $job->timeout);
    }

    /**
     * Test que el job acepta tipos de importación válidos.
     */
    public function test_job_accepts_valid_import_types(): void
    {
        Queue::fake();

        $validTypes = ['productos', 'clientes', 'proveedores'];

        foreach ($validTypes as $type) {
            ProcessImportJob::dispatch('file.csv', $type, 1, 1);

            Queue::assertPushed(ProcessImportJob::class, function ($job) use ($type) {
                return $job->importType === $type;
            });
        }
    }
}
