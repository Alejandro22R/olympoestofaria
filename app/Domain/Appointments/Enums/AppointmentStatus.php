<?php

namespace App\Domain\Appointments\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AppointmentStatus: string implements HasColor, HasLabel
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    /**
     * Estados que ocupan capacidad en la agenda.
     *
     * @return list<self>
     */
    public static function occupying(): array
    {
        return [self::Scheduled, self::Confirmed, self::CheckedIn];
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Scheduled => [self::Confirmed, self::Cancelled, self::NoShow, self::CheckedIn],
            self::Confirmed => [self::CheckedIn, self::Cancelled, self::NoShow],
            self::CheckedIn, self::Cancelled, self::NoShow => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function getLabel(): string
    {
        return __('appointments.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Scheduled => 'gray',
            self::Confirmed => 'primary',
            self::CheckedIn => 'success',
            self::Cancelled => 'danger',
            self::NoShow => 'warning',
        };
    }
}
