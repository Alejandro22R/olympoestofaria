<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Correo al cliente con el texto de la plantilla editable (y enlace de baja si no es transaccional).
 */
class CustomerMessageMail extends Mailable
{
    use Queueable;

    /**
     * @param  array{disk: string, path: string, name: string}|null  $file
     */
    public function __construct(
        public string $mailSubject,
        public string $body,
        public ?string $unsubscribeUrl = null,
        public ?array $file = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.customer-message');
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return $this->file === null ? [] : [
            Attachment::fromStorageDisk($this->file['disk'], $this->file['path'])->as($this->file['name'])->withMime('application/pdf'),
        ];
    }
}
