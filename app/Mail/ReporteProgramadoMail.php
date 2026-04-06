<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class ReporteProgramadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nombreReporte,
        public readonly string $tipoReporte,
        public readonly string $filePath,
        public readonly string $fileName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reporte Programado: {$this->nombreReporte}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reporte-programado',
            with: [
                'nombreReporte' => $this->nombreReporte,
                'tipoReporte' => str_replace('_', ' ', ucfirst($this->tipoReporte)),
                'fechaGeneracion' => now()->format('d/m/Y H:i'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->filePath)->as($this->fileName),
        ];
    }
}
