<?php

namespace App\Filament\Resources\GalleryItems\Pages;

use App\Jobs\ProcessGalleryVideo;
use App\Models\GalleryItem;
use Filament\Notifications\Notification;

/**
 * Tras guardar, si hay un video nuevo se encola la conversión FFmpeg.
 */
trait QueuesVideoProcessing
{
    protected function queueVideoIfNeeded(): void
    {
        $item = $this->getRecord();
        if (! $item instanceof GalleryItem) {
            return;
        }

        $item->refresh();
        if ($item->needsVideoProcessing()) {
            $item->forceFill(['video_status' => 'pending', 'video_error' => null])->save();
            ProcessGalleryVideo::dispatch($item, auth()->id());
            Notification::make()->info()->title(__('gallery.messages.video_queued'))->send();
        }
    }
}
