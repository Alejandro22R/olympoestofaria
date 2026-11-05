<?php

namespace App\Jobs;

use App\Domain\System\RunBackup;
use App\Domain\System\VerifyBackup;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Copia o prueba de restauración pedida desde el panel: corre en cola y avisa a quien la pidió.
 */
class RunSystemTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    /**
     * @param  'backup'|'restore_test'  $task
     */
    public function __construct(public string $task, public ?User $requestedBy = null) {}

    public function handle(): void
    {
        $ok = $this->task === 'backup'
            ? app(RunBackup::class)->execute()
            : app(VerifyBackup::class)->execute()->ok;

        if ($this->requestedBy !== null) {
            $title = __("system.tasks.{$this->task}_".($ok ? 'ok' : 'failed'), [], $this->requestedBy->locale);
            Notification::make()->title($title)->{$ok ? 'success' : 'danger'}()->sendToDatabase($this->requestedBy, isEventDispatched: true);
        }
    }
}
