<?php

namespace App\Domain\Projects\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Seguimiento del lead: enviado → cotizado → aprobado, o perdido.
 */
enum ProjectStatus: string implements HasColor, HasLabel
{
    case Submitted = 'submitted';
    case Contacted = 'contacted';
    case Quoted = 'quoted';
    case Approved = 'approved';
    case Lost = 'lost';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Submitted => [self::Contacted, self::Quoted, self::Lost],
            self::Contacted => [self::Quoted, self::Lost],
            self::Quoted => [self::Approved, self::Lost, self::Quoted],
            self::Approved, self::Lost => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function getLabel(): string
    {
        return __('projects.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Submitted => 'warning',
            self::Contacted => 'info',
            self::Quoted => 'primary',
            self::Approved => 'success',
            self::Lost => 'gray',
        };
    }
}
