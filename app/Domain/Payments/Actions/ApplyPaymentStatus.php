<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Appointments\Actions\ChangeAppointmentStatus;
use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Auth\Permission;
use App\Domain\Notifications\StaffNotifier;
use App\Domain\Payments\BillingSummary;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\PaymentType;
use App\Domain\Payments\Events\PaymentUpdated;
use App\Domain\Settings\PaymentSettings;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Support\Money;
use App\Models\Payment;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\DB;

/**
 * Aplica un nuevo estado a un pago respetando la máquina de estados (idempotente: repetir el mismo
 * estado no hace nada). Al aprobarse, marca el anticipo pagado y confirma la cita si corresponde.
 */
class ApplyPaymentStatus
{
    public function __construct(
        private readonly PaymentSettings $settings,
        private readonly ChangeAppointmentStatus $appointmentStatus,
        private readonly StaffNotifier $notifier,
    ) {}

    public function execute(Payment $payment, PaymentStatus $to, ?string $detail = null): bool
    {
        $changed = DB::transaction(function () use ($payment, $to, $detail): bool {
            /** @var Payment $locked */
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($locked->status === $to || ! $locked->status->canTransitionTo($to)) {
                return false;
            }

            $locked->status = $to;
            $locked->status_detail = $detail;
            if ($to === PaymentStatus::Approved) {
                $locked->paid_at ??= now();
            }
            $locked->save();
            $payment->setRawAttributes($locked->getAttributes(), true);

            if ($to === PaymentStatus::Approved) {
                $this->afterApproved($locked);
            }

            return true;
        });

        if ($changed) {
            PaymentUpdated::dispatch($payment->id, $payment->customer_id, $to->value);
            $this->notifyStaff($payment, $to);
        }

        return $changed;
    }

    private function afterApproved(Payment $payment): void
    {
        $quote = $payment->quote;
        if ($quote === null || $payment->type !== PaymentType::Deposit) {
            return;
        }

        if ($quote->deposit_paid_at === null && BillingSummary::forQuote($quote)->paidCents >= $quote->deposit_cents) {
            $quote->forceFill(['deposit_paid_at' => now()])->save();

            $appointment = $quote->appointment;
            if ($appointment !== null && $this->settings->deposit_required_to_confirm && $appointment->status === AppointmentStatus::Scheduled) {
                $this->appointmentStatus->execute($appointment, AppointmentStatus::Confirmed);
            }
        }
    }

    private function notifyStaff(Payment $payment, PaymentStatus $status): void
    {
        if (! in_array($status, [PaymentStatus::Approved, PaymentStatus::Rejected], true)) {
            return;
        }

        $this->notifier->notify(Permission::LeadsManage, new StaffAlert(
            type: 'payment_'.$status->value,
            titleKey: 'notifications.payment_'.$status->value,
            params: ['amount' => Money::format($payment->amount_cents), 'customer' => $payment->customer->name, 'type' => $payment->type->getLabel()],
            url: PaymentResource::getUrl('index'),
            color: $status === PaymentStatus::Approved ? 'success' : 'danger',
            icon: 'heroicon-o-banknotes',
        ));
    }
}
