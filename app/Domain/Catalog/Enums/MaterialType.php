<?php

namespace App\Domain\Catalog\Enums;

use Filament\Support\Contracts\HasLabel;

enum MaterialType: string implements HasLabel
{
    case Leather = 'leather';
    case Fabric = 'fabric';
    case Vinyl = 'vinyl';
    case Ink = 'ink';

    public function getLabel(): string
    {
        return __('catalog.material_types.'.$this->value);
    }
}
