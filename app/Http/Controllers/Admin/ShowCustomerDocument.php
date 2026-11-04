<?php

namespace App\Http\Controllers\Admin;

use App\Models\CustomerDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entrega el documento descifrado. Requiere URL firmada vigente, sesión y permiso.
 */
class ShowCustomerDocument
{
    public function __invoke(Request $request, CustomerDocument $document): Response
    {
        Gate::authorize('view', $document);

        activity('documents')
            ->causedBy($request->user())
            ->performedOn($document)
            ->event('viewed')
            ->log('customer_document.viewed');

        $contents = Crypt::decryptString((string) Storage::disk($document->disk)->get($document->path));
        $extension = match ($document->mime_type) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        return response($contents, 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => sprintf('inline; filename="documento-%d.%s"', $document->customer_id, $extension),
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
