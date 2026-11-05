<?php

namespace App\Domain\Alerts;

/**
 * Espacio libre del disco donde vive storage/ (en porcentaje). Separado para poder simularlo en pruebas.
 */
class DiskUsage
{
    public function freePercent(): ?float
    {
        $total = @disk_total_space(storage_path());
        $free = @disk_free_space(storage_path());

        return $total && $free !== false ? round($free / $total * 100, 1) : null;
    }
}
