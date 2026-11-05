<?php

namespace App\Http\Controllers\Admin;

use App\Models\Quote;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF de la cotización para el personal (firma + sesión + permiso).
 */
class ShowQuoteReport
{
    public function __invoke(Quote $quote): Response
    {
        Gate::authorize('view', $quote);
        abort_if($quote->report_path === null, 404);

        return response((string) Storage::disk((string) $quote->report_disk)->get($quote->report_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="orcamento-'.$quote->id.'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
