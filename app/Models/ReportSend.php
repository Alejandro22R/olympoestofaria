<?php

namespace App\Models;

use App\Domain\Inspections\Enums\ReportChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Registro de envío de un acta al cliente: quién, cuándo, canal y destinatario.
 *
 * @property ReportChannel $channel
 * @property Carbon $sent_at
 */
#[Fillable(['channel', 'recipient', 'sent_by', 'sent_at'])]
class ReportSend extends Model
{
    protected function casts(): array
    {
        return [
            'channel' => ReportChannel::class,
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function sendable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
