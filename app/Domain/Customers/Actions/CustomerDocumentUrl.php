<?php

namespace App\Domain\Customers\Actions;

use App\Models\CustomerDocument;
use Illuminate\Support\Facades\URL;

/**
 * URL firmada y de corta duración para ver el documento. Además de la firma,
 * la ruta exige sesión y el permiso customer_documents.view (CustomerDocumentPolicy).
 */
class CustomerDocumentUrl
{
    public const TTL_MINUTES = 5;

    public function for(CustomerDocument $document): string
    {
        return URL::temporarySignedRoute(
            'admin.customer-documents.show',
            now()->addMinutes(self::TTL_MINUTES),
            ['document' => $document->id],
        );
    }
}
