<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Foto/archivo del documento de identidad. El contenido está cifrado en un disco privado
 * y solo se entrega por una URL firmada de corta duración tras verificar el permiso.
 */
#[Fillable(['customer_id', 'disk', 'path', 'mime_type', 'size_bytes', 'original_name', 'uploaded_by', 'consent_at'])]
class CustomerDocument extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'consent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['customer_id', 'mime_type', 'size_bytes', 'uploaded_by'])->logOnlyDirty();
    }
}
