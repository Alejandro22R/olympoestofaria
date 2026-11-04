<?php

namespace App\Models;

use App\Domain\Inspections\Enums\ReportChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property ReportChannel $channel
 * @property Carbon $sent_at
 */
#[Fillable(['inspection_id', 'channel', 'recipient', 'sent_by', 'sent_at'])]
class InspectionReportSend extends Model
{
    protected function casts(): array
    {
        return [
            'channel' => ReportChannel::class,
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Inspection, $this>
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
