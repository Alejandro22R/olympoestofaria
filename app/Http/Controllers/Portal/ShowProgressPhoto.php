<?php

namespace App\Http\Controllers\Portal;

use App\Models\WorkOrder;
use App\Models\WorkOrderUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Foto de avance visible al cliente (disco privado): solo el dueño de la orden o el personal.
 */
class ShowProgressPhoto
{
    public function __invoke(Request $request, WorkOrder $workOrder, WorkOrderUpdate $update, int $index): Response
    {
        Gate::authorize('track', $workOrder);
        abort_unless($update->work_order_id === $workOrder->id && $update->visible_to_customer, 404);

        $path = $update->photos[$index] ?? null;
        abort_if($path === null, 404);

        $disk = Storage::disk((string) config('olympo.private_disk'));
        abort_unless($disk->exists($path), 404);

        return response((string) $disk->get($path), 200, [
            'Content-Type' => $disk->mimeType($path) ?: 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
