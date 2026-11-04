<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entrega el PDF del acta. Ruta del cliente: solo firma vigente.
 * Ruta del panel: firma + sesión + permiso.
 */
class ShowInspectionReport
{
    public function __invoke(Request $request, Inspection $inspection): Response
    {
        if ($request->routeIs('admin.*')) {
            Gate::authorize('view', $inspection);
        }

        abort_unless($inspection->hasReport(), 404);

        return response((string) Storage::disk((string) $inspection->report_disk)->get((string) $inspection->report_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="acta-%s.pdf"', $inspection->vehicle->plate),
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
