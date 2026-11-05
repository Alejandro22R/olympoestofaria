<?php

namespace App\Domain\Quotes\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuoteStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Superseded = 'superseded';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Sent, self::Superseded],
            self::Sent => [self::Approved, self::Rejected, self::Expired, self::Superseded],
            self::Expired, self::Rejected => [self::Superseded],
            self::Approved, self::Superseded => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function getLabel(): string
    {
        return __('quotes.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::Approved => 'success',
            self::Rejected, self::Expired => 'danger',
            self::Superseded => 'gray',
        };
    }
}
