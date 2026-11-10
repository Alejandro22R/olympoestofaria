<?php

namespace App\Domain\Deliveries\Actions;

use App\Domain\Inspections\Support\VehicleSilhouettes;
use App\Domain\Settings\GeneralSettings;
use App\Models\Delivery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Acta de entrega en PDF: servicios realizados, comparación de cada daño del check-in
 * con su estado final, kilometraje y garantía. En el idioma del cliente.
 */
class GenerateDeliveryReport
{
    public function __construct(private readonly GeneralSettings $general) {}

    public function execute(Delivery $delivery): Delivery
    {
        $delivery->loadMissing(['workOrder.vehicle.customer', 'workOrder.items.serviceType', 'workOrder.inspection.marks.damageType', 'workOrder.warranty', 'deliverer']);
        $workOrder = $delivery->workOrder;
        $customer = $workOrder->vehicle->customer;
        $disk = (string) config('olympo.private_disk');
        $inspection = $workOrder->inspection;

        $previous = App::getLocale();
        App::setLocale($customer->locale);
        try {
            $diagram = null;
            if ($inspection !== null) {
                $svg = view('pdf.partials.diagram', [
                    'outline' => VehicleSilhouettes::outline($inspection->silhouette),
                    'zones' => VehicleSilhouettes::zones($inspection->silhouette),
                    'marks' => $inspection->marks,
                    'viewBox' => VehicleSilhouettes::viewBox($inspection->silhouette),
                ])->render();
                $diagram = 'data:image/svg+xml;base64,'.base64_encode($svg);
            }

            $html = view('pdf.delivery-report', [
                'delivery' => $delivery,
                'workOrder' => $workOrder,
                'customer' => $customer,
                'vehicle' => $workOrder->vehicle,
                'inspection' => $inspection,
                'diagram' => $diagram,
                'business' => $this->general,
                'timezone' => $this->general->timezone,
                'fontDir' => resource_path('fonts/pdf'),
            ])->render();
        } finally {
            App::setLocale($previous);
        }

        $pdf = Pdf::loadHTML($html)->setPaper('a4')
            ->setOption(['isRemoteEnabled' => false, 'fontDir' => storage_path('fonts'), 'fontCache' => storage_path('fonts'), 'chroot' => [resource_path(), public_path('images/brand')]]);

        $path = sprintf('deliveries/%d/entrega-%s.pdf', $delivery->id, Str::uuid());
        Storage::disk($disk)->put($path, $pdf->output());

        $old = $delivery->report_path;
        $delivery->forceFill(['report_disk' => $disk, 'report_path' => $path, 'report_generated_at' => now()])->save();
        if ($old !== null && $old !== $path) {
            Storage::disk($disk)->delete($old);
        }

        return $delivery;
    }
}
