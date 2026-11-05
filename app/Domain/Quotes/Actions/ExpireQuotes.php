<?php

namespace App\Domain\Quotes\Actions;

use App\Domain\Quotes\Enums\QuoteStatus;
use App\Models\Quote;

/**
 * Marca como vencidas las cotizaciones enviadas cuya vigencia terminó.
 */
class ExpireQuotes
{
    public function execute(): int
    {
        return Quote::query()
            ->where('status', QuoteStatus::Sent)
            ->whereDate('valid_until', '<', now()->toDateString())
            ->update(['status' => QuoteStatus::Expired->value, 'updated_at' => now()]);
    }
}
