<?php

namespace App\Domain\Privacy\Actions;

use App\Models\Appointment;
use App\Models\ConsentEvent;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;

/**
 * Portabilidad (LGPD art. 18, V): todos los datos del cliente en JSON legible.
 * El número de documento va descifrado (es del propio titular); las fotos y PDF se listan, no se incluyen.
 */
class ExportCustomerData
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Customer $customer, ?User $by = null): array
    {
        $customer->loadMissing(['vehicles', 'projects', 'quotes.items', 'payments', 'consents', 'document']);
        $vehicleIds = $customer->vehicles->pluck('id');
        $date = fn (?\DateTimeInterface $d) => $d?->format(DATE_ATOM);

        $data = [
            'generated_at' => now()->toIso8601String(),
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'document_type' => $customer->document_type,
                'document_number' => $customer->document_number,
                'locale' => $customer->locale,
                'created_at' => $date($customer->created_at),
                'privacy_consent_at' => $date($customer->lgpd_consent_at),
                'contact_opt_in_at' => $date($customer->contact_opt_in_at),
                'unsubscribed_at' => $date($customer->unsubscribed_at),
                'referral_code' => $customer->referral_code,
                'has_document_file' => $customer->document !== null,
            ],
            'consents' => $customer->consents->map(fn (ConsentEvent $c) => [
                'type' => $c->type, 'granted' => $c->granted, 'source' => $c->source, 'at' => $date($c->created_at),
            ])->all(),
            'vehicles' => $customer->vehicles->map(fn (Vehicle $v) => [
                'plate' => $v->plate, 'brand' => $v->brand, 'model' => $v->model, 'year' => $v->year, 'color' => $v->color, 'mileage_km' => $v->mileage_km,
            ])->all(),
            'projects' => $customer->projects->map(fn ($p) => [
                'number' => $p->number(), 'status' => $p->status->value, 'submitted_at' => $date($p->submitted_at), 'configuration' => $p->configuration,
            ])->all(),
            'quotes' => $customer->quotes->map(fn ($q) => [
                'number' => $q->number(), 'status' => $q->status->value, 'total_cents' => $q->total_cents, 'currency' => 'BRL',
                'valid_until' => $q->valid_until?->toDateString(),
                'items' => $q->items->map(fn ($i) => ['description' => $i->description, 'quantity' => (float) $i->quantity, 'total_cents' => $i->total_cents])->all(),
            ])->all(),
            'appointments' => Appointment::query()->whereIn('vehicle_id', $vehicleIds)->get()->map(fn (Appointment $a) => [
                'scheduled_at' => $date($a->scheduled_at), 'status' => $a->status->value,
            ])->all(),
            'work_orders' => WorkOrder::query()->whereIn('vehicle_id', $vehicleIds)->with(['items', 'warranty'])->get()->map(fn (WorkOrder $o) => [
                'number' => $o->number(), 'status' => $o->status->value, 'delivered_at' => $date($o->delivered_at),
                'items' => $o->items->map(fn ($i) => ['description' => $i->description, 'price_cents' => $i->price_cents])->all(),
                'warranty_until' => $o->warranty?->ends_at->toDateString(),
            ])->all(),
            'payments' => $customer->payments->map(fn ($p) => [
                'type' => $p->type->value, 'method' => $p->method->value, 'status' => $p->status->value, 'amount_cents' => $p->amount_cents, 'paid_at' => $date($p->paid_at),
            ])->all(),
            'messages' => NotificationLog::query()->where('notifiable_type', $customer->getMorphClass())->where('notifiable_id', $customer->id)
                ->where('status', 'sent')->latest('id')->limit(500)->get()
                ->map(fn (NotificationLog $l) => ['event' => $l->event, 'channel' => $l->channel, 'at' => $date($l->created_at)])->all(),
        ];

        activity('privacy')->causedBy($by)->performedOn($customer)->event('exported')->log('privacy.exported');

        return $data;
    }
}
