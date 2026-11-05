<?php

namespace App\Domain\Quotes\Actions;

use App\Domain\Auth\Permission;
use App\Domain\Notifications\StaffNotifier;
use App\Domain\Projects\Actions\ChangeProjectStatus;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aprobación o rechazo de la cotización (por el cliente en el portal o por el taller en su nombre).
 */
class DecideQuote
{
    public function __construct(
        private readonly ChangeProjectStatus $projectStatus,
        private readonly StaffNotifier $notifier,
    ) {}

    public function approve(Quote $quote, string $via = 'portal', ?string $ip = null): Quote
    {
        $this->assertOpen($quote);

        DB::transaction(function () use ($quote, $via, $ip): void {
            $quote->status = QuoteStatus::Approved;
            $quote->approved_at = now();
            $quote->approved_via = $via;
            $quote->approval_ip = $ip;
            $quote->save();

            if ($quote->project !== null && $quote->project->status->canTransitionTo(ProjectStatus::Approved)) {
                $this->projectStatus->execute($quote->project, ProjectStatus::Approved);
            }
        });

        $this->notify($quote, 'quote_approved', 'success');

        return $quote;
    }

    public function reject(Quote $quote, string $reason): Quote
    {
        $this->assertOpen($quote);

        $quote->status = QuoteStatus::Rejected;
        $quote->rejected_at = now();
        $quote->rejection_reason = mb_substr(trim($reason), 0, 255) ?: null;
        $quote->save();

        $this->notify($quote, 'quote_rejected', 'danger');

        return $quote;
    }

    private function assertOpen(Quote $quote): void
    {
        if ($quote->status !== QuoteStatus::Sent) {
            throw ValidationException::withMessages(['quote' => __('quotes.errors.not_open', ['status' => $quote->status->getLabel()])]);
        }
        if ($quote->isExpired()) {
            throw ValidationException::withMessages(['quote' => __('quotes.errors.expired')]);
        }
    }

    private function notify(Quote $quote, string $key, string $color): void
    {
        $this->notifier->notify(Permission::LeadsManage, new StaffAlert(
            type: $key,
            titleKey: 'notifications.'.$key,
            params: ['number' => $quote->number(), 'customer' => $quote->customer->name],
            url: QuoteResource::getUrl('edit', ['record' => $quote]),
            color: $color,
            icon: 'heroicon-o-document-check',
        ));
    }
}
