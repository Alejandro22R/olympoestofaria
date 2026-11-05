<?php

namespace App\Domain\Media;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Envoltura de FFmpeg: video para web sin audio (MP4 H.264 + WebM VP9) y póster JPG.
 */
class VideoTranscoder
{
    public function __construct(private readonly string $ffmpeg = '/usr/bin/ffmpeg') {}

    public static function make(): self
    {
        return new self((string) config('media-library.ffmpeg_path', '/usr/bin/ffmpeg'));
    }

    /**
     * @return array{mp4: string, webm: string, poster: string} rutas absolutas generadas
     */
    public function transcode(string $input, string $outputDir, string $basename, int $maxWidth = 1280): array
    {
        if (! is_file($input)) {
            throw new RuntimeException("Video not found: {$input}");
        }
        if (! is_dir($outputDir) && ! mkdir($outputDir, 0755, true) && ! is_dir($outputDir)) {
            throw new RuntimeException("Cannot create {$outputDir}");
        }

        $scale = "scale='min({$maxWidth},iw)':-2";
        $out = [
            'mp4' => "{$outputDir}/{$basename}.mp4",
            'webm' => "{$outputDir}/{$basename}.webm",
            'poster' => "{$outputDir}/{$basename}.jpg",
        ];

        $this->run([$this->ffmpeg, '-y', '-i', $input, '-an', '-vf', $scale, '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '26', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', $out['mp4']]);
        $this->run([$this->ffmpeg, '-y', '-i', $input, '-an', '-vf', $scale, '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', '36', '-deadline', 'realtime', '-cpu-used', '8', $out['webm']]);
        $this->run([$this->ffmpeg, '-y', '-ss', '0.5', '-i', $input, '-frames:v', '1', '-vf', $scale, '-q:v', '4', $out['poster']]);

        return $out;
    }

    /**
     * @param  list<string>  $command
     */
    private function run(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(mb_substr(trim($process->getErrorOutput()), -300));
        }
    }
}
