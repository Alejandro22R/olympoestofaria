<?php

use App\Domain\Auth\Role;
use App\Domain\Media\Events\MediaProcessed;
use App\Jobs\ProcessGalleryVideo;
use App\Models\GalleryItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

beforeEach(function () {
    Storage::fake('public');
    config(['media-library.queue_conversions_by_default' => false]);
});

function tinyClip(): string
{
    $path = sys_get_temp_dir().'/olympo-test-clip.mp4';
    if (! is_file($path)) {
        (new Process(['/usr/bin/ffmpeg', '-y', '-loglevel', 'error', '-f', 'lavfi', '-i', 'color=c=red:s=160x120:d=1:r=10', '-c:v', 'libx264', '-pix_fmt', 'yuv420p', $path]))->mustRun();
    }
    $copy = sys_get_temp_dir().'/olympo-test-clip-'.uniqid().'.mp4';
    copy($path, $copy);

    return $copy;
}

it('converts the uploaded video to MP4, WebM and a poster and tells the uploader', function () {
    Event::fake([MediaProcessed::class]);
    $user = userWithRole(Role::Owner);
    $item = GalleryItem::create(['title' => ['pt_BR' => 'Clip'], 'placements' => ['hero']]);
    $item->addMedia(tinyClip())->toMediaCollection('video');

    expect($item->fresh()->needsVideoProcessing())->toBeTrue();

    ProcessGalleryVideo::dispatchSync($item, $user->id);

    $item->refresh();
    expect($item->video_status)->toBe('ready')
        ->and($item->hasVideo())->toBeTrue()
        ->and($item->needsVideoProcessing())->toBeFalse();
    Storage::disk('public')->assertExists([$item->video_mp4_path, $item->video_webm_path, $item->video_poster_path]);
    Event::assertDispatched(MediaProcessed::class, fn ($e) => $e->userId === $user->id && $e->status === 'ready');
})->skip(! is_executable('/usr/bin/ffmpeg'), 'FFmpeg no disponible');

it('marks the video as failed when FFmpeg breaks', function () {
    config(['media-library.ffmpeg_path' => '/bin/false']);
    $item = GalleryItem::create(['title' => ['pt_BR' => 'Clip'], 'placements' => ['hero']]);
    $item->addMedia(tinyClip())->toMediaCollection('video');

    ProcessGalleryVideo::dispatchSync($item);

    expect($item->fresh()->video_status)->toBe('failed')
        ->and($item->fresh()->hasVideo())->toBeFalse();
})->skip(! is_executable('/usr/bin/ffmpeg'), 'FFmpeg no disponible');

it('creates WebP conversions for photos', function () {
    $item = GalleryItem::create(['title' => ['pt_BR' => 'Foto'], 'placements' => ['gallery']]);
    $item->addMedia(UploadedFile::fake()->image('foto.jpg', 1200, 900))->toMediaCollection('image');

    $media = $item->getFirstMedia('image');
    expect($media->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and($media->hasGeneratedConversion('large'))->toBeTrue()
        ->and($media->getUrl('large'))->toEndWith('.webp');
});
