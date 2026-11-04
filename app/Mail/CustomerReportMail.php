<?php

namespace App\Mail;

use App\Models\Contracts\SendableReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Acta (recepción o entrega) por correo, con el PDF adjunto desde el disco privado.
 */
class CustomerReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SendableReport $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __("reports.{$this->report->reportKind()}.subject", ['plate' => $this->report->reportVehicle()->formattedPlate()]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.customer-report', with: [
            'kind' => $this->report->reportKind(),
            'customer' => $this->report->reportCustomer(),
            'vehicle' => $this->report->reportVehicle(),
        ]);
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk((string) $this->report->reportDisk(), (string) $this->report->reportPath())
                ->as($this->filename())
                ->withMime('application/pdf'),
        ];
    }

    public function filename(): string
    {
        $prefix = $this->report->reportKind() === 'delivery' ? 'entrega' : 'acta';

        return $prefix.'-'.$this->report->reportVehicle()->plate.'.pdf';
    }
}
