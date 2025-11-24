<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendEmailJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendEmailJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que el job se despacha correctamente a la queue de emails.
     */
    public function test_job_is_dispatched_to_emails_queue(): void
    {
        Queue::fake();

        SendEmailJob::dispatch(
            to: 'test@example.com',
            subject: 'Test Subject',
            view: 'emails.test',
            data: []
        );

        Queue::assertPushedOn('emails', SendEmailJob::class);
    }

    /**
     * Test que el job se despacha con destinatario único.
     */
    public function test_job_is_dispatched_with_single_recipient(): void
    {
        Queue::fake();

        $to = 'cliente@example.com';
        $subject = 'Reporte de Ventas';
        $view = 'emails.reporte_ventas';
        $data = ['mes' => 'Enero', 'total' => 50000];

        SendEmailJob::dispatch($to, $subject, $view, $data);

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($to, $subject) {
            return $job->to === $to && $job->subject === $subject;
        });
    }

    /**
     * Test que el job se despacha con múltiples destinatarios.
     */
    public function test_job_is_dispatched_with_multiple_recipients(): void
    {
        Queue::fake();

        $recipients = ['admin@example.com', 'gerente@example.com'];

        SendEmailJob::dispatch(
            to: $recipients,
            subject: 'Notificación Múltiple',
            view: 'emails.notificacion',
            data: []
        );

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($recipients) {
            return $job->to === $recipients;
        });
    }

    /**
     * Test que el job tiene configuración de retries.
     */
    public function test_job_has_retry_configuration(): void
    {
        $job = new SendEmailJob('test@example.com', 'Subject', 'view', []);

        $this->assertEquals(5, $job->tries);
        $this->assertEquals([30, 60, 120, 300, 600], $job->backoff);
    }

    /**
     * Test que el job tiene timeout.
     */
    public function test_job_has_timeout(): void
    {
        $job = new SendEmailJob('test@example.com', 'Subject', 'view', []);

        $this->assertEquals(60, $job->timeout);
    }

    /**
     * Test que el job acepta adjuntos.
     */
    public function test_job_accepts_attachments(): void
    {
        Queue::fake();

        $attachments = [
            [
                'path' => '/tmp/report.pdf',
                'name' => 'Reporte.pdf',
                'mime' => 'application/pdf',
            ]
        ];

        SendEmailJob::dispatch(
            to: 'test@example.com',
            subject: 'Con Adjunto',
            view: 'emails.test',
            data: [],
            attachments: $attachments
        );

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($attachments) {
            return $job->attachments === $attachments;
        });
    }
}
