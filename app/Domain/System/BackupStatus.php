<?php

namespace App\Domain\System;

use App\Domain\Settings\BackupSettings;
use Spatie\Backup\BackupDestination\BackupDestination;
use Throwable;

/**
 * Lectura de las copias existentes en cada destino.
 */
class BackupStatus
{
    public function __construct(private readonly BackupSettings $settings) {}

    /**
     * @return list<array{disk: string, count: int, newest: ?string, size: ?int, path: ?string, error: ?string}>
     */
    public function destinations(): array
    {
        $rows = [];
        foreach ($this->disks() as $disk) {
            try {
                $destination = BackupDestination::create($disk, RunBackup::BACKUP_NAME);
                $newest = $destination->newestBackup();
                $rows[] = [
                    'disk' => $disk,
                    'count' => $destination->backups()->count(),
                    'newest' => $newest?->date()->toIso8601String(),
                    'size' => $newest !== null ? (int) $newest->sizeInBytes() : null,
                    'path' => $newest?->path(),
                    'error' => null,
                ];
            } catch (Throwable $e) {
                $rows[] = ['disk' => $disk, 'count' => 0, 'newest' => null, 'size' => null, 'path' => null, 'error' => $e->getMessage()];
            }
        }

        return $rows;
    }

    /**
     * Copia más reciente del primer destino que tenga alguna.
     *
     * @return array{disk: string, count: int, newest: ?string, size: ?int, path: ?string, error: ?string}|null
     */
    public function newest(): ?array
    {
        foreach ($this->destinations() as $row) {
            if ($row['path'] !== null) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function disks(): array
    {
        return array_values(array_intersect($this->settings->disks, (array) config('olympo.backup_disks'))) ?: ['backups'];
    }
}
