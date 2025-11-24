<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SyncHaciendaJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SyncHaciendaJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que el job se despacha correctamente a la queue de hacienda.
     */
    public function test_job_is_dispatched_to_hacienda_queue(): void
    {
        Queue::fake();

        SyncHaciendaJob::dispatch(
            empresaId: 1,
            action: 'enviar_factura',
            data: ['clave' => '506180802...']
        );

        Queue::assertPushedOn('hacienda', SyncHaciendaJob::class);
    }

    /**
     * Test que el job se despacha con acción de enviar factura.
     */
    public function test_job_is_dispatched_with_send_invoice_action(): void
    {
        Queue::fake();

        $action = 'enviar_factura';
        $data = [
            'clave' => '50618080200012345678901234567890123456789012',
            'xml' => '<FacturaElectronica>...</FacturaElectronica>',
        ];

        SyncHaciendaJob::dispatch(1, $action, $data);

        Queue::assertPushed(SyncHaciendaJob::class, function ($job) use ($action, $data) {
            return $job->action === $action
                && $job->data === $data;
        });
    }

    /**
     * Test que el job acepta diferentes acciones de Hacienda.
     */
    public function test_job_accepts_valid_hacienda_actions(): void
    {
        Queue::fake();

        $validActions = [
            'enviar_factura',
            'consultar_estado',
            'recibir_comprobante',
            'validar_ced_juridica'
        ];

        foreach ($validActions as $action) {
            SyncHaciendaJob::dispatch(1, $action, []);

            Queue::assertPushed(SyncHaciendaJob::class, function ($job) use ($action) {
                return $job->action === $action;
            });
        }
    }

    /**
     * Test que el job tiene configuración de retries alta.
     */
    public function test_job_has_high_retry_configuration(): void
    {
        $job = new SyncHaciendaJob(1, 'enviar_factura', []);

        $this->assertEquals(5, $job->tries);
        $this->assertEquals([60, 120, 300, 600, 1200], $job->backoff());
    }

    /**
     * Test que el job tiene timeout configurado.
     */
    public function test_job_has_timeout(): void
    {
        $job = new SyncHaciendaJob(1, 'enviar_factura', []);

        $this->assertEquals(120, $job->timeout);
    }

    /**
     * Test que el job se despacha con consulta de estado.
     */
    public function test_job_is_dispatched_with_status_query(): void
    {
        Queue::fake();

        SyncHaciendaJob::dispatch(
            empresaId: 1,
            action: 'consultar_estado',
            data: ['clave' => '506180802...']
        );

        Queue::assertPushed(SyncHaciendaJob::class, function ($job) {
            return $job->action === 'consultar_estado'
                && isset($job->data['clave']);
        });
    }
}
