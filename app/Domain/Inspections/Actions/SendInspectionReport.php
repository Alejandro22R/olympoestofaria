<?php

namespace App\Domain\Inspections\Actions;

use App\Domain\Inspections\Enums\ReportChannel;
use App\Mail\InspectionReportMail;
use App\Models\Inspection;
use App\Models\InspectionReportSend;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Envía el acta al cliente por correo (PDF adjunto) o prepara el mensaje de WhatsApp
 * con el enlace firmado (MVP con wa.me; la Fase 6 usa WhatsApp Cloud API).
 * Siempre registra quién lo envió, cuándo, por qué canal y a quién.
 */
class SendInspectionReport
{
    public function __construct(
        private readonly InspectionReportLink $links,
        private readonly GenerateInspectionReport $generate,
    ) {}

    /**
     * @return array{send: InspectionReportSend, url: ?string} url = enlace de wa.me a abrir (solo WhatsApp).
     */
    public function execute(Inspection $inspection, ReportChannel $channel, ?User $sender = null): array
    {
        $inspection->loadMissing('vehicle.customer');
        $customer = $inspection->vehicle->customer;

        if (! $inspection->hasReport()) {
            $this->generate->execute($inspection);
        }

        $url = null;

        if ($channel === ReportChannel::Email) {
            if (blank($customer->email)) {
                throw ValidationException::withMessages(['channel' => __('inspections.errors.no_email')]);
            }
            $recipient = (string) $customer->email;
            Mail::to($recipient)->locale($customer->locale)->send(new InspectionReportMail($inspection));
        } else {
            $recipient = $customer->phone;
            $message = trans('inspections.whatsapp_message', [
                'name' => $customer->name,
                'plate' => $inspection->vehicle->formattedPlate(),
                'url' => $this->links->forCustomer($inspection),
            ], $customer->locale);
            $url = 'https://wa.me/'.ltrim($recipient, '+').'?text='.rawurlencode($message);
        }

        $send = $inspection->sends()->create([
            'channel' => $channel,
            'recipient' => $recipient,
            'sent_by' => $sender?->id,
            'sent_at' => now(),
        ]);

        return ['send' => $send, 'url' => $url];
    }
}
