<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Webhook recibido (registro para soporte e idempotencia: cada evento se procesa una vez).
 */
#[Fillable(['provider', 'event_key', 'topic', 'resource_id', 'payload', 'result', 'processed_at'])]
class WebhookEvent extends Model
{
    protected function casts(): array
    {
        return ['payload' => 'array', 'processed_at' => 'datetime'];
    }
}
