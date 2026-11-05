<?php

namespace App\Jobs;

use App\Domain\Media\Events\MediaProcessed;
use App\Domain\Media\VideoTranscoder;
use App\Models\GalleryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Convierte el video subido desde el panel a MP4 + WebM sin audio y genera el póster.
 * Avisa al usuario que lo subió por su canal privado cuando termina.
 */
class ProcessGalleryVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 900;

    public function __construct(public GalleryItem $item, public ?int $userId = null) {}

    public function handle(): void
    {
        $media = $this->item->getFirstMedia('video');
        if ($media === null) {
            $this->item->forceFill(['video_status' => null])->save();

            return;
        }

        $this->item->forceFill(['video_status' => 'processing', 'video_error' => null])->save();

        try {
            $relativeDir = 'gallery/videos/'.$this->item->id;
            $outputs = VideoTranscoder::make()->transcode(
                $media->getPath(),
                Storage::disk('public')->path($relativeDir),
                'clip-'.$media->id,
            );

            $this->item->forceFill([
                'video_status' => 'ready',
                'video_mp4_path' => $relativeDir.'/'.basename($outputs['mp4']),
                'video_webm_path' => $relativeDir.'/'.basename($outputs['webm']),
                'video_poster_path' => $relativeDir.'/'.basename($outputs['poster']),
            ])->save();
        } catch (Throwable $e) {
            $this->item->forceFill(['video_status' => 'failed', 'video_error' => mb_substr($e->getMessage(), 0, 250)])->save();
            report($e);
        }

        if ($this->userId !== null) {
            MediaProcessed::dispatch($this->userId, $this->item->id, (string) $this->item->video_status);
        }
    }
}
