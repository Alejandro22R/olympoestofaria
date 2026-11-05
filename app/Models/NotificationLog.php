<?php

namespace App\Models;

use App\Domain\Settings\NotificationSettings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registro de cada intento de aviso al cliente (canal, estado, error). Se depura según
 * Ajustes > Notificaciones > días de retención.
 */
#[Fillable(['notifiable_type', 'notifiable_id', 'event', 'channel', 'recipient', 'status', 'attempt', 'idempotency_key', 'provider_id', 'error'])]
class NotificationLog extends Model
{
    use Prunable;

    /**
     * @return MorphTo<Model, $this>
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(app(NotificationSettings::class)->log_retention_days));
    }
}
