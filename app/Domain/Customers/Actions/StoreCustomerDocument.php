<?php

namespace App\Domain\Customers\Actions;

use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Guarda el documento de identidad del cliente: cifrado en disco privado,
 * uno solo por cliente (reemplaza el anterior) y solo con consentimiento LGPD.
 */
class StoreCustomerDocument
{
    /** Tipos MIME reales permitidos (se leen del contenido, no de la extensión). */
    public const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public const MAX_BYTES = 8 * 1024 * 1024;

    public function execute(Customer $customer, UploadedFile $file, ?User $uploader = null): CustomerDocument
    {
        if (! $customer->hasLgpdConsent()) {
            throw ValidationException::withMessages(['file' => __('customers.errors.consent_required')]);
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        if (! in_array($mime, self::ALLOWED_MIME, true)) {
            throw ValidationException::withMessages(['file' => __('customers.errors.document_mime')]);
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['file' => __('customers.errors.document_size', ['max' => self::MAX_BYTES / 1024 / 1024])]);
        }

        $disk = (string) config('olympo.private_disk');
        $path = sprintf('customers/%d/documents/%s.bin', $customer->id, Str::uuid());

        Storage::disk($disk)->put($path, Crypt::encryptString((string) file_get_contents($file->getRealPath())));

        return DB::transaction(function () use ($customer, $file, $uploader, $disk, $path, $mime): CustomerDocument {
            $previous = $customer->document()->first();

            $document = $customer->document()->updateOrCreate([], [
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $mime,
                'size_bytes' => (int) $file->getSize(),
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'uploaded_by' => $uploader?->id,
                'consent_at' => $customer->lgpd_consent_at,
            ]);

            if ($previous !== null && $previous->path !== $path) {
                DB::afterCommit(fn () => Storage::disk($previous->disk)->delete($previous->path));
            }

            return $document;
        });
    }
}
