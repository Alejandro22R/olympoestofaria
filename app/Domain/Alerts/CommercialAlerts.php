<?php

namespace App\Domain\Alerts;

use App\Domain\Auth\Permission;
use App\Domain\Notifications\Customer\CustomerEvents;
use App\Domain\Notifications\StaffNotifier;
use App\Domain\Payments\BillingSummary;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\Settings\AlertSettings;
use App\Domain\Settings\NotificationSettings;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Filament\Support\Money;
use App\Models\AlertLog;
use App\Models\Project;
use App\Models\Quote;
use App\Models\WorkOrder;
use App\Notifications\StaffAlert;

/**
 * Alertas comerciales (PLAN.md §2.5): lead sin respuesta, cotización sin respuesta (también recuerda
 * al cliente), anticipo vencido y saldo vencido. Umbrales en Ajustes > Alertas automáticas.
 */
class CommercialAlerts
{
    public function __construct(
        private readonly AlertSettings $alerts,
        private readonly NotificationSettings $notifications,
        private readonly StaffNotifier $notifier,
        private readonly CustomerEvents $customerEvents,
    ) {}

    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        return [
            'lead_unanswered' => $this->leads(),
            'quote_unanswered' => $this->quotes(),
            'deposit_overdue' => $this->deposits(),
            'balance_overdue' => $this->balances(),
        ];
    }

    private function leads(): int
    {
        $count = 0;
        $projects = Project::query()->with('customer')->where('status', ProjectStatus::Submitted)
            ->where('submitted_at', '<', now()->subHours($this->alerts->lead_unanswered_hours))->get();

        foreach ($projects as $project) {
            $key = "lead_unanswered:{$project->id}";
            if (AlertLog::claim($key, 'lead_unanswered')) {
                $this->notifier->notify(Permission::LeadsManage, new StaffAlert(
                    type: 'lead_unanswered', titleKey: 'notifications.lead_unanswered',
                    params: ['number' => $project->number(), 'customer' => $project->customer->name, 'hours' => $this->alerts->lead_unanswered_hours],
                    bodyKey: 'notifications.lead_unanswered_body',
                    url: ProjectResource::getUrl('view', ['record' => $project]), color: 'warning', icon: 'heroicon-o-sparkles', alertKey: $key,
                ));
                $count++;
            }
        }

        return $count;
    }

    private function quotes(): int
    {
        $count = 0;
        $quotes = Quote::query()->with('customer')->where('status', QuoteStatus::Sent)->whereDate('valid_until', '>=', now()->toDateString())->get();

        foreach ($quotes as $quote) {
            $sentAt = $quote->sent_at;
            if ($sentAt === null) {
                continue;
            }
            if ($this->notifications->quote_reminder_days > 0 && $sentAt->lt(now()->subDays($this->notifications->quote_reminder_days))) {
                $this->customerEvents->quoteReminder($quote); // idempotente: una vez por cotización
            }
            $key = "quote_unanswered:{$quote->id}";
            if ($sentAt->lt(now()->subDays($this->alerts->quote_unanswered_days)) && AlertLog::claim($key, 'quote_unanswered')) {
                $this->notifier->notify(Permission::LeadsManage, new StaffAlert(
                    type: 'quote_unanswered', titleKey: 'notifications.quote_unanswered',
                    params: ['number' => $quote->number(), 'customer' => $quote->customer->name, 'days' => $this->alerts->quote_unanswered_days],
                    bodyKey: 'notifications.quote_unanswered_body',
                    url: QuoteResource::getUrl('edit', ['record' => $quote]), color: 'warning', icon: 'heroicon-o-document-currency-dollar', alertKey: $key,
                ));
                $count++;
            }
        }

        return $count;
    }

    private function deposits(): int
    {
        $count = 0;
        $quotes = Quote::query()->with('customer')->where('status', QuoteStatus::Approved)
            ->where('deposit_cents', '>', 0)->whereNull('deposit_paid_at')
            ->where('approved_at', '<', now()->subHours($this->alerts->deposit_overdue_hours))->get();

        foreach ($quotes as $quote) {
            $key = "deposit_overdue:{$quote->id}";
            if (AlertLog::claim($key, 'deposit_overdue')) {
                $this->notifier->notify(Permission::LeadsManage, new StaffAlert(
                    type: 'deposit_overdue', titleKey: 'notifications.deposit_overdue',
                    params: ['number' => $quote->number(), 'customer' => $quote->customer->name, 'amount' => Money::format(BillingSummary::forQuote($quote)->depositOutstandingCents())],
                    bodyKey: 'notifications.deposit_overdue_body',
                    url: QuoteResource::getUrl('edit', ['record' => $quote]), color: 'danger', icon: 'heroicon-o-banknotes', alertKey: $key,
                ));
                $count++;
            }
        }

        return $count;
    }

    private function balances(): int
    {
        $count = 0;
        $orders = WorkOrder::query()->with('vehicle.customer')->where('status', WorkOrderStatus::Delivered)
            ->where('delivered_at', '<', now()->subDays($this->alerts->balance_overdue_days))
            ->where('delivered_at', '>', now()->subDays(120))->get();

        foreach ($orders as $order) {
            $due = BillingSummary::forWorkOrder($order)->outstandingCents();
            $key = "balance_overdue:{$order->id}";
            if ($due > 0 && AlertLog::claim($key, 'balance_overdue')) {
                $this->notifier->notify(Permission::DeliveriesManage, new StaffAlert(
                    type: 'balance_overdue', titleKey: 'notifications.balance_overdue',
                    params: ['number' => $order->number(), 'customer' => $order->vehicle->customer->name, 'amount' => Money::format($due)],
                    bodyKey: 'notifications.balance_overdue_body',
                    url: WorkOrderResource::getUrl('edit', ['record' => $order]), color: 'danger', icon: 'heroicon-o-banknotes', alertKey: $key,
                ));
                $count++;
            }
        }

        return $count;
    }
}
