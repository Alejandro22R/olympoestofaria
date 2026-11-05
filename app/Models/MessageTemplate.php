<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property list<string>|null $whatsapp_params
 */
#[Fillable(['event', 'channel', 'locale', 'subject', 'body', 'whatsapp_template', 'whatsapp_language', 'whatsapp_params', 'is_active'])]
class MessageTemplate extends Model
{
    protected function casts(): array
    {
        return ['whatsapp_params' => 'array', 'is_active' => 'boolean'];
    }
}
