<?php

namespace App\Domain\Inspections\Actions;

use App\Domain\Inspections\Support\VehicleSilhouettes;
use App\Domain\Settings\CheckinSettings;
use App\Domain\Settings\GeneralSettings;
use App\Models\ChecklistItem;
use App\Models\Inspection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Genera el acta de recepción en PDF (idioma del cliente) y la guarda en el disco privado.
 */
class GenerateInspectionReport
{
    public function __construct(
        private readonly CheckinSettings $checkin,
        private readonly GeneralSettings $general,
    ) {}

    public function execute(Inspection $inspection): Inspection
    {
        $inspection->loadMissing(['appointment', 'vehicle.customer', 'marks.damageType', 'inspector']);
        $customer = $inspection->vehicle->customer;
        $locale = $customer->locale;
        $disk = (string) config('olympo.private_disk');

        $html = $this->withLocale($locale, fn () => view('pdf.inspection-report', [
            'inspection' => $inspection,
            'customer' => $customer,
            'vehicle' => $inspection->vehicle,
            'business' => $this->general,
            'timezone' => $this->general->timezone,
            'legalText' => $this->checkin->legalText($locale),
            'consentText' => $this->checkin->consentText($locale),
            'items' => ChecklistItem::query()->where('is_active', true)->orderBy('sort')->get(),
            'diagram' => $this->diagramDataUri($inspection),
            'photos' => $this->photos($inspection, $disk),
            'fontDir' => resource_path('fonts/pdf'),
        ])->render());

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption(['isRemoteEnabled' => false, 'fontDir' => storage_path('fonts'), 'fontCache' => storage_path('fonts'), 'chroot' => [resource_path(), public_path('images/brand')]]);

        $path = sprintf('inspections/%d/acta-%s.pdf', $inspection->id, Str::uuid());
        Storage::disk($disk)->put($path, $pdf->output());

        $previous = $inspection->report_path;
        $inspection->forceFill([
            'report_disk' => $disk,
            'report_path' => $path,
            'report_generated_at' => now(),
        ])->save();

        if ($previous !== null && $previous !== $path) {
            Storage::disk($disk)->delete($previous);
        }

        return $inspection;
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withLocale(string $locale, callable $callback): mixed
    {
        $previous = App::getLocale();
        App::setLocale($locale);

        try {
            return $callback();
        } finally {
            App::setLocale($previous);
        }
    }

    private function diagramDataUri(Inspection $inspection): string
    {
        $svg = view('pdf.partials.diagram', [
            'outline' => VehicleSilhouettes::outline($inspection->silhouette),
            'zones' => VehicleSilhouettes::zones($inspection->silhouette),
            'marks' => $inspection->marks,
            'viewBox' => VehicleSilhouettes::viewBox($inspection->silhouette),
        ])->render();

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * @return array<int, list<string>> Fotos de cada marca como data URI (dompdf no lee el disco privado).
     */
    private function photos(Inspection $inspection, string $disk): array
    {
        $result = [];
        foreach ($inspection->marks as $mark) {
            foreach ($mark->photos as $path) {
                if (! Storage::disk($disk)->exists($path)) {
                    continue;
                }
                $mime = Storage::disk($disk)->mimeType($path) ?: 'image/jpeg';
                $result[$mark->id][] = 'data:'.$mime.';base64,'.base64_encode((string) Storage::disk($disk)->get($path));
            }
        }

        return $result;
    }
}
