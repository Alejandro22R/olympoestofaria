<?php

namespace App\Domain\Auth;

enum Role: string
{
    case Owner = 'owner';
    case Reception = 'reception';
    case Upholsterer = 'upholsterer';
    case Customer = 'customer';

    /**
     * @return list<self>
     */
    public static function staff(): array
    {
        return [self::Owner, self::Reception, self::Upholsterer];
    }

    public function label(): string
    {
        return __('roles.'.$this->value);
    }
}
