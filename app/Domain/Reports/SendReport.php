<?php

namespace App\Domain\Reports;

use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\Settings\CheckinSettings;
use App\Mail\CustomerReportMail;
use App\Models\Contracts\SendableReport;
use App\Models\ReportSend;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Envía un acta al cliente por correo (PDF adjunto) o prepara el mensaje de WhatsApp
 * con el enlace firmado (MVP con wa.me; la Fase 6 usa WhatsApp Cloud API).
 * Siempre registra quién lo envió, cuándo, por qué canal y a quién.
 */
class SendReport
{
    public function __construct(private readonly CheckinSettings $settings) {}

    /**
     * @return array{send: ReportSend, url: ?string} url = enlace de wa.me a abrir (solo WhatsApp).
     */
    public function execute(SendableReport $report, ReportChannel $channel, ?User $sender = null): array
    {
        $customer = $report->reportCustomer();
        $url = null;

        if ($report->reportPath() === null) {
            throw ValidationException::withMessages(['channel' => __('reports.errors.no_pdf')]);
        }

        if ($channel === ReportChannel::Email) {
            if (blank($customer->email)) {
                throw ValidationException::withMessages(['channel' => __('inspections.errors.no_email')]);
            }
            $recipient = (string) $customer->email;
            Mail::to($recipient)->locale($customer->locale)->send(new CustomerReportMail($report));
        } else {
            $recipient = $customer->phone;
            $message = trans("reports.{$report->reportKind()}.whatsapp", [
                'name' => $customer->name,
                'plate' => $report->reportVehicle()->formattedPlate(),
                'url' => $report->customerReportUrl($this->settings->report_link_days),
            ], $customer->locale);
            $url = 'https://wa.me/'.ltrim($recipient, '+').'?text='.rawurlencode($message);
        }

        $send = $report->recordSend([
            'channel' => $channel,
            'recipient' => $recipient,
            'sent_by' => $sender?->id,
            'sent_at' => now(),
        ]);

        return ['send' => $send, 'url' => $url];
    }
}
