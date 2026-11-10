<?php

namespace App\Domain\Quotes\Actions;

use App\Domain\Settings\GeneralSettings;
use App\Domain\Settings\PaymentSettings;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * PDF de la cotización (idioma del cliente) en el disco privado.
 */
class GenerateQuotePdf
{
    public function __construct(
        private readonly GeneralSettings $general,
        private readonly PaymentSettings $payments,
    ) {}

    public function execute(Quote $quote): Quote
    {
        $quote->loadMissing(['customer', 'items', 'vehicle']);
        $previous = App::getLocale();
        App::setLocale($quote->customer->locale);

        try {
            $html = view('pdf.quote', [
                'quote' => $quote,
                'business' => $this->general,
                'payments' => $this->payments,
                'timezone' => $this->general->timezone,
                'fontDir' => resource_path('fonts/pdf'),
            ])->render();
        } finally {
            App::setLocale($previous);
        }

        $disk = (string) config('olympo.private_disk');
        $path = sprintf('quotes/%d/orcamento-%s.pdf', $quote->id, Str::uuid());
        Storage::disk($disk)->put($path, Pdf::loadHTML($html)->setPaper('a4')
            ->setOption(['isRemoteEnabled' => false, 'fontDir' => storage_path('fonts'), 'fontCache' => storage_path('fonts'), 'chroot' => [resource_path(), public_path('images/brand')]])
            ->output());

        $old = $quote->report_path;
        $quote->forceFill(['report_disk' => $disk, 'report_path' => $path])->save();
        if ($old !== null && $old !== $path) {
            Storage::disk($disk)->delete($old);
        }

        return $quote;
    }
}
