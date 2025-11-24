<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Usuario;

/**
 * Job para enviar emails de forma asíncrona
 * Sprint 8.4 - Queue Jobs
 */
class SendEmailJob implements ShouldQueue
{
    use Queueable;

    public $tries = 5;
    public $timeout = 60;
    public $backoff = [30, 60, 120, 300, 600];

    /**
     * Create a new job instance.
     */
    /**
     * @param string|array<int,string> $to
     * @param array<string,mixed> $data
     * @param array<int,array<string,string>> $attachments
     */
    public function __construct(
        public string|array $to,
        public string $subject,
        public string $view,
        public array $data = [],
        public array $attachments = []
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('SendEmailJob: Enviando email', [
                'to' => $this->to,
                'subject' => $this->subject,
                'view' => $this->view,
            ]);

            Mail::send($this->view, $this->data, function ($message): void {
                $message->to($this->to)
                    ->subject($this->subject);

                // Adjuntar archivos si existen
                foreach ($this->attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path']),
                            'mime' => $attachment['mime'] ?? 'application/octet-stream',
                        ]);
                    }
                }
            });

            Log::info('SendEmailJob: Email enviado exitosamente', [
                'to' => $this->to,
            ]);

        } catch (\Exception $e) {
            Log::error('SendEmailJob: Error enviando email', [
                'to' => $this->to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendEmailJob: Job failed permanently', [
            'to' => $this->to,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }
}
