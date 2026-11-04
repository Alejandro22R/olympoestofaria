<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF del acta de entrega. Cliente: firma vigente. Panel: firma + sesión + permiso.
 */
class ShowDeliveryReport
{
    public function __invoke(Request $request, Delivery $delivery): Response
    {
        if ($request->routeIs('admin.*')) {
            Gate::authorize('view', $delivery->workOrder);
        }

        abort_if($delivery->report_path === null, 404);

        return response((string) Storage::disk((string) $delivery->report_disk)->get($delivery->report_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="entrega-%s.pdf"', $delivery->workOrder->vehicle->plate),
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
