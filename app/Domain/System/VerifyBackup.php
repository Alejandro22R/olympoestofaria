<?php

namespace App\Domain\System;

use App\Models\HealthCheck;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

/**
 * Prueba de restauración de la copia más reciente:
 * 1. descarga y abre el ZIP, comprueba que tenga el volcado de la base con las tablas clave;
 * 2. si hay una base de prueba configurada (BACKUP_RESTORE_TEST_DATABASE), restaura el volcado
 *    en ella y cuenta clientes y órdenes. Nunca toca la base de producción.
 */
class VerifyBackup
{
    /** Tablas que deben estar en el volcado. */
    public const REQUIRED_TABLES = ['customers', 'vehicles', 'work_orders', 'quotes', 'payments', 'settings', 'users'];

    public function __construct(private readonly BackupStatus $status) {}

    public function execute(): HealthCheck
    {
        $newest = $this->status->newest();
        if ($newest === null || $newest['path'] === null) {
            return HealthCheck::record('restore_test', false, __('system.restore.no_backup'));
        }

        $dir = storage_path('app/backup-temp/restore-'.Str::random(8));
        @mkdir($dir, 0700, true);

        try {
            $zipPath = $dir.'/backup.zip';
            $stream = Storage::disk($newest['disk'])->readStream($newest['path']);
            file_put_contents($zipPath, $stream);

            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                return HealthCheck::record('restore_test', false, __('system.restore.corrupt'));
            }

            if ($password = config('backup.backup.password')) {
                $zip->setPassword((string) $password); // copias cifradas (BACKUP_ARCHIVE_PASSWORD)
            }

            $dumpName = null;
            $files = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if (str_starts_with($name, 'db-dumps/') && str_ends_with($name, '.sql')) {
                    $dumpName = $name;
                } elseif (! str_ends_with($name, '/') && $name !== 'manifest.txt') {
                    $files++;
                }
            }
            if ($dumpName === null) {
                $zip->close();

                return HealthCheck::record('restore_test', false, __('system.restore.no_dump'));
            }

            $zip->extractTo($dir, $dumpName);
            $zip->close();
            $dump = $dir.'/'.$dumpName;
            $sql = (string) file_get_contents($dump);

            $missing = array_values(array_filter(self::REQUIRED_TABLES, fn (string $t) => ! preg_match('/CREATE TABLE (public\.)?"?'.$t.'"?\s*\(/', $sql)));
            if ($missing !== []) {
                return HealthCheck::record('restore_test', false, __('system.restore.missing_tables', ['tables' => implode(', ', $missing)]));
            }

            $details = ['backup' => $newest['path'], 'disk' => $newest['disk'], 'dump_bytes' => strlen($sql), 'files' => $files, 'restored' => false];

            if ($database = config('olympo.restore_test_database')) {
                $details = [...$details, ...$this->restoreInto((string) $database, $dump), 'restored' => true];
            }

            return HealthCheck::record('restore_test', true, $details['restored'] ? __('system.restore.ok_restored', $details) : __('system.restore.ok_checked'), $details);
        } catch (Throwable $e) {
            report($e);

            return HealthCheck::record('restore_test', false, Str::limit($e->getMessage(), 500));
        } finally {
            Process::fromShellCommandline('rm -rf '.escapeshellarg($dir))->run();
        }
    }

    /**
     * @return array{customers: int, work_orders: int}
     */
    private function restoreInto(string $database, string $dump): array
    {
        $db = config('database.connections.pgsql');
        if ($database === ($db['database'] ?? null)) {
            throw new \RuntimeException('restore test database must differ from the application database');
        }

        $env = ['PGPASSWORD' => (string) ($db['password'] ?? '')];
        $base = ['psql', '-v', 'ON_ERROR_STOP=1', '-q', '-h', (string) $db['host'], '-p', (string) $db['port'], '-U', (string) $db['username'], '-d', $database];

        $this->run([...$base, '-c', 'DROP SCHEMA IF EXISTS public CASCADE; CREATE SCHEMA public;'], $env);
        $this->run([...$base, '-f', $dump], $env);
        $count = fn (string $table) => (int) trim($this->run([...$base, '-t', '-A', '-c', "select count(*) from {$table}"], $env));

        return ['customers' => $count('customers'), 'work_orders' => $count('work_orders')];
    }

    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $env
     */
    private function run(array $command, array $env): string
    {
        $process = new Process($command, null, $env, null, 600);
        $process->mustRun();

        return $process->getOutput();
    }
}
