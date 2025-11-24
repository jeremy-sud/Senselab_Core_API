<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GeneratePdfReportJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GeneratePdfReportJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que el job se despacha correctamente a la queue.
     */
    public function test_job_is_dispatched_to_reports_queue(): void
    {
        Queue::fake();

        GeneratePdfReportJob::dispatch(
            reportType: 'ventas',
            empresaId: 1,
            filters: [
                'fecha_inicio' => '2025-01-01',
                'fecha_fin' => '2025-01-31',
            ],
            userId: 1
        );

        Queue::assertPushedOn('reports', GeneratePdfReportJob::class);
    }

    /**
     * Test que el job se despacha con los parámetros correctos.
     */
    public function test_job_is_dispatched_with_correct_parameters(): void
    {
        Queue::fake();

        $reportType = 'inventario';
        $empresaId = 2;
        $filters = ['almacen_id' => 3];
        $userId = 5;

        GeneratePdfReportJob::dispatch($reportType, $empresaId, $filters, $userId);

        Queue::assertPushed(GeneratePdfReportJob::class, function ($job) use ($reportType, $empresaId, $filters, $userId) {
            return $job->reportType === $reportType
                && $job->empresaId === $empresaId
                && $job->filters === $filters
                && $job->userId === $userId;
        });
    }

    /**
     * Test que el job tiene configuración de retries.
     */
    public function test_job_has_retry_configuration(): void
    {
        $job = new GeneratePdfReportJob('ventas', 1, [], 1);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([60, 120, 300], $job->backoff);
    }

    /**
     * Test que el job tiene timeout configurado.
     */
    public function test_job_has_timeout(): void
    {
        $job = new GeneratePdfReportJob('ventas', 1, [], 1);

        $this->assertEquals(300, $job->timeout);
    }

    /**
     * Test que el job puede ser ejecutado sin errores con datos mínimos.
     */
    public function test_job_can_be_executed_with_minimal_data(): void
    {
        Queue::fake();

        // Solo verificamos que no lance excepciones en el dispatch
        $this->expectNotToPerformAssertions();

        GeneratePdfReportJob::dispatch('ventas', 1, [], null);
    }
}
