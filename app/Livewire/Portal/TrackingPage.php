<?php

namespace App\Livewire\Portal;

use App\Domain\Payments\Actions\RequestPixPayment;
use App\Domain\Payments\BillingSummary;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Settings\PaymentSettings;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\Payment;
use App\Models\WorkflowStage;
use App\Models\WorkOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Seguimiento del trabajo para el cliente: etapas visibles, avances con fotos y pagos.
 * Se actualiza en vivo (canal privado del cliente) con sondeo de respaldo.
 */
#[Layout('layouts.portal')]
class TrackingPage extends Component
{
    public WorkOrder $workOrder;

    public ?string $email = null;

    public function mount(WorkOrder $workOrder): void
    {
        Gate::authorize('track', $workOrder);
        $this->workOrder = $workOrder;
        $this->email = $workOrder->vehicle->customer->email;
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        $customer = $this->workOrder->vehicle->customer_id;

        return [
            "echo-private:customers.{$customer},.work-order.moved" => '$refresh',
            "echo-private:customers.{$customer},.payment.updated" => '$refresh',
        ];
    }

    public function payBalance(RequestPixPayment $pix): void
    {
        Gate::authorize('track', $this->workOrder);
        abort_unless($this->balanceOpen(), 403);
        $this->validate(['email' => ['required', 'email', 'max:160']]);

        $pix->balance($this->workOrder, $this->email);
    }

    private function balanceOpen(): bool
    {
        $settings = app(PaymentSettings::class);

        return $settings->onlinePixAvailable()
            && ($settings->balance_moment === 'before_delivery' || $this->workOrder->status === WorkOrderStatus::Ready);
    }

    public function render(): View
    {
        $this->workOrder->refresh()->loadMissing(['vehicle.customer', 'stage']);
        $stages = WorkflowStage::query()->ordered()->where('visible_to_customer', true)->get();
        $currentIndex = $stages->search(fn (WorkflowStage $s) => $s->id === $this->workOrder->workflow_stage_id);
        $pending = Payment::query()->where('work_order_id', $this->workOrder->id)->where('status', PaymentStatus::Pending)->latest('id')->first();

        return view('livewire.portal.tracking-page', [
            'stages' => $stages,
            'currentIndex' => $currentIndex === false ? ($this->workOrder->status === WorkOrderStatus::Delivered ? $stages->count() : -1) : $currentIndex,
            'updates' => $this->workOrder->updates()->where('visible_to_customer', true)->with('stage')->limit(30)->get(),
            'billing' => BillingSummary::forWorkOrder($this->workOrder),
            'pendingPix' => $pending?->isPayableOnline() ? $pending : null,
            'balanceOpen' => $this->balanceOpen(),
        ])->title($this->workOrder->number().' · Olympo');
    }
}
