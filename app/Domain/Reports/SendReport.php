<?php

namespace App\Domain\Reports;

use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\Notifications\Customer\CustomerNotifier;
use App\Domain\Notifications\Customer\MessageEvent;
use App\Domain\Settings\CheckinSettings;
use App\Domain\Settings\WhatsAppSettings;
use App\Filament\Support\Money;
use App\Mail\CustomerReportMail;
use App\Models\Contracts\SendableReport;
use App\Models\Delivery;
use App\Models\Quote;
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
    public function __construct(
        private readonly CheckinSettings $settings,
        private readonly WhatsAppSettings $whatsapp,
        private readonly CustomerNotifier $notifier,
    ) {}

    /**
     * Envío automático (actas tras check-in y entrega): WhatsApp si está conectado y hay opt-in; si no, correo.
     *
     * @return array{send: ReportSend, url: ?string}|null
     */
    public function automatic(SendableReport $report): ?array
    {
        $customer = $report->reportCustomer();
        $channel = $this->whatsapp->isReady() && $customer->contact_opt_in_at !== null ? ReportChannel::WhatsApp
            : (filled($customer->email) ? ReportChannel::Email : null);

        return $channel === null ? null : $this->execute($report, $channel);
    }

    /**
     * @return array{0: MessageEvent, 1: array<string, string|int|float|null>}
     */
    private function messageFor(SendableReport $report, string $link): array
    {
        $plate = $report->reportVehicle()->formattedPlate();

        return match (true) {
            $report instanceof Quote => [MessageEvent::QuoteSent, [
                'numero' => $report->number(),
                'veiculo' => (string) $report->vehicle_label,
                'total' => Money::format($report->total_cents),
                'validade' => $report->valid_until?->format('d/m/Y'),
                'link' => $link,
            ]],
            $report instanceof Delivery => [MessageEvent::DeliveryReport, ['placa' => $plate, 'link' => $link]],
            default => [MessageEvent::CheckinReport, ['placa' => $plate, 'link' => $link]],
        };
    }

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
            $link = $report->customerReportUrl($this->settings->report_link_days);

            if ($this->whatsapp->isReady() && $customer->contact_opt_in_at !== null) {
                // WhatsApp Cloud API: el mensaje sale solo (plantilla aprobada) y queda en el registro de envíos.
                [$event, $vars] = $this->messageFor($report, $link);
                $this->notifier->send($customer, $event, $vars, 'report:'.$report->reportKind().':'.$report->getKey().':'.now()->timestamp);
            } else {
                // MVP: enlace wa.me con el mensaje listo para que el personal lo envíe.
                $message = trans("reports.{$report->reportKind()}.whatsapp", [
                    'name' => $customer->name,
                    'plate' => $report->reportVehicle()->formattedPlate(),
                    'url' => $link,
                ], $customer->locale);
                $url = 'https://wa.me/'.ltrim($recipient, '+').'?text='.rawurlencode($message);
            }
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
