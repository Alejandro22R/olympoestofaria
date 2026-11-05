<?php

namespace App\Filament\Support;

use App\Support\Brand;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Avatar con iniciales generado localmente (SVG en data URI). Reemplaza al de Filament,
 * que pide la imagen a ui-avatars.com y le envía el nombre del usuario (LGPD).
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $name = Filament::getNameForDefaultAvatar($record);
        $initials = Str::of($name)->trim()->explode(' ')->filter()->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
        $bg = Brand::shades()[700];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="'.$bg.'"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="Manrope, sans-serif" font-size="26" font-weight="700" fill="#FFFFFF">'
            .e($initials ?: '?').'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
