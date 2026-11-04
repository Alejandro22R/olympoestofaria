<?php

namespace App\Mail;

use App\Models\Inspection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Acta de recepción por correo, con el PDF adjunto desde el disco privado.
 */
class InspectionReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Inspection $inspection) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('inspections.mail.subject', ['plate' => $this->inspection->vehicle->formattedPlate()]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.inspection-report', with: [
            'customer' => $this->inspection->vehicle->customer,
            'vehicle' => $this->inspection->vehicle,
        ]);
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk((string) $this->inspection->report_disk, (string) $this->inspection->report_path)
                ->as('acta-'.$this->inspection->vehicle->plate.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
