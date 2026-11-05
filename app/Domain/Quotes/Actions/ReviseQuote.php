<?php

namespace App\Domain\Quotes\Actions;

use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\Settings\PricingSettings;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Revisión de una cotización ya enviada: crea una versión nueva en borrador con los mismos ítems
 * y marca la anterior como reemplazada (queda en el historial).
 */
class ReviseQuote
{
    public function __construct(private readonly PricingSettings $pricing) {}

    public function execute(Quote $quote, ?User $user = null): Quote
    {
        if (! $quote->status->canTransitionTo(QuoteStatus::Superseded) || $quote->status === QuoteStatus::Draft) {
            throw ValidationException::withMessages(['status' => __('quotes.errors.not_revisable', ['status' => $quote->status->getLabel()])]);
        }

        return DB::transaction(function () use ($quote, $user): Quote {
            $new = $quote->replicate(['status', 'version', 'previous_quote_id', 'report_disk', 'report_path', 'sent_at', 'approved_at', 'approved_via', 'approval_ip', 'rejected_at', 'rejection_reason', 'deposit_paid_at', 'created_by']);
            $new->fill([
                'status' => QuoteStatus::Draft,
                'version' => $quote->version + 1,
                'previous_quote_id' => $quote->id,
                'valid_until' => now()->addDays($this->pricing->quote_validity_days)->toDateString(),
                'created_by' => $user?->id,
            ]);
            $new->save();

            foreach ($quote->items as $item) {
                $new->items()->create($item->only(['service_type_id', 'material_variant_id', 'description', 'quantity', 'unit_price_cents', 'total_cents', 'sort']));
            }

            $quote->status = QuoteStatus::Superseded;
            $quote->save();

            return $new;
        });
    }
}
