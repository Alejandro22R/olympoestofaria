<?php

namespace Database\Seeders;

use App\Jobs\ProcessGalleryVideo;
use App\Models\GalleryItem;
use App\Models\Material;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;
use Symfony\Component\Process\Process;

/**
 * Medios de demostración del portal (solo desarrollo): imágenes generadas con GD y un clip
 * generado con FFmpeg. Son marcadores propios (sin licencias de terceros); se reemplazan por
 * el material original del taller desde Portal > Biblioteca de medios.
 */
class DemoPortalSeeder extends Seeder
{
    public function run(): void
    {
        if (GalleryItem::query()->exists() || ! function_exists('imagecreatetruecolor')) {
            return;
        }

        config(['media-library.queue_conversions_by_default' => false]);
        $tmp = storage_path('app/demo-media');
        @mkdir($tmp, 0755, true);

        $leather = Material::query()->where('type', 'leather')->first();
        $services = ServiceType::query()->pluck('id', 'slug');

        $items = [
            ['Bancos em couro caramelo', 'Bancos de cuero caramelo', 'seats', 'Volkswagen', 'Fusca', [181, 101, 29], ['gallery', 'services', 'before_after']],
            ['Volante costurado à mão', 'Volante cosido a mano', 'steering_wheel', 'Chevrolet', 'Onix', [17, 17, 17], ['gallery', 'services']],
            ['Forro de porta laranja', 'Panel de puerta naranja', 'doors', 'Toyota', 'Hilux', [217, 102, 31], ['gallery', 'services']],
            ['Teto em alcântara', 'Techo en alcántara', 'headliner', 'Chevrolet', 'Onix', [46, 46, 46], ['gallery', 'services']],
            ['Carpete novo', 'Piso nuevo', 'flooring', 'Volkswagen', 'Fusca', [30, 30, 30], ['gallery', 'services']],
            ['Restauração completa', 'Restauración completa', 'restoration', 'Volkswagen', 'Fusca', [142, 27, 27], ['gallery']],
        ];

        foreach ($items as $i => [$pt, $es, $slug, $brand, $model, $rgb, $placements]) {
            $item = GalleryItem::query()->create([
                'title' => ['pt_BR' => $pt, 'es' => $es],
                'service_type_id' => $services[$slug] ?? null,
                'material_id' => $leather?->id,
                'brand' => $brand,
                'model' => $model,
                'placements' => $placements,
                'sort' => $i,
            ]);

            $after = $this->interiorImage("{$tmp}/after-{$i}.jpg", $rgb);
            $item->addMedia($after)->toMediaCollection('image');

            if (in_array('before_after', $placements, true)) {
                $before = $this->interiorImage("{$tmp}/before-{$i}.jpg", [92, 84, 74], worn: true);
                $item->addMedia($before)->toMediaCollection('before');
            }
        }

        // Clip del hero: solo si hay FFmpeg y no estamos en pruebas.
        if (! app()->runningUnitTests() && is_executable((string) config('media-library.ffmpeg_path'))) {
            $clip = "{$tmp}/demo-hero.mp4";
            $process = new Process([(string) config('media-library.ffmpeg_path'), '-y', '-loglevel', 'error', '-f', 'lavfi', '-i', 'color=c=0x2a1407:s=1280x720:d=8:r=30',
                '-vf', "noise=alls=18:allf=t+u,drawbox=x=0:y=340:w=1280:h=40:color=0x6b3410@0.9:t=fill,drawbox=x='mod(t*160\\,80)':y=356:w=40:h=6:color=0xE8C9A0@0.95:t=fill,vignette=PI/4",
                '-c:v', 'libx264', '-pix_fmt', 'yuv420p', $clip]);
            $process->setTimeout(120)->run();

            if ($process->isSuccessful()) {
                $hero = GalleryItem::query()->create([
                    'title' => ['pt_BR' => 'Costura em couro (demo)', 'es' => 'Costura en cuero (demo)'],
                    'placements' => ['hero', 'reels'],
                    'sort' => 99,
                ]);
                $hero->addMedia($clip)->toMediaCollection('video');
                ProcessGalleryVideo::dispatchSync($hero);
            }
        }
    }

    /**
     * Ilustración simple de un interior (asientos con costura) para usar como marcador.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function interiorImage(string $path, array $rgb, bool $worn = false): string
    {
        [$w, $h] = [1600, 1200];
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 12, 12, 12));

        for ($y = 0; $y < $h; $y++) {
            $shade = (int) (18 + 22 * ($y / $h));
            imageline($im, 0, $y, $w, $y, imagecolorallocate($im, $shade, $shade, $shade));
        }

        $seat = imagecolorallocate($im, ...$rgb);
        $light = imagecolorallocate($im, min(255, $rgb[0] + 40), min(255, $rgb[1] + 40), min(255, $rgb[2] + 40));
        $stitch = imagecolorallocate($im, 225, 205, 170);

        foreach ([[260, 180], [900, 180]] as [$x, $y]) {
            imagefilledrectangle($im, $x, $y + 120, $x + 440, $y + 860, $seat);
            imagefilledellipse($im, $x + 220, $y + 90, 300, 180, $seat);
            imagefilledrectangle($im, $x + 40, $y + 160, $x + 400, $y + 300, $light);
            for ($k = 0; $k < 6; $k++) {
                imagesetthickness($im, 3);
                $yy = $y + 360 + $k * 80;
                for ($xx = $x + 40; $xx < $x + 400; $xx += 24) {
                    imageline($im, $xx, $yy, $xx + 12, $yy, $stitch);
                }
            }
        }

        if ($worn) {
            $crack = imagecolorallocate($im, 40, 36, 30);
            mt_srand(7);
            for ($n = 0; $n < 140; $n++) {
                $x = mt_rand(270, 1330);
                $y = mt_rand(320, 1030);
                imageline($im, $x, $y, $x + mt_rand(-40, 40), $y + mt_rand(-30, 30), $crack);
            }
        }

        imagestring($im, 5, 40, $h - 50, 'OLYMPO - DEMO', imagecolorallocate($im, 115, 115, 115));
        imagejpeg($im, $path, 82);
        imagedestroy($im);

        return $path;
    }
}
