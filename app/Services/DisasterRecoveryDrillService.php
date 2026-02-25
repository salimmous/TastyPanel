<?php

namespace App\Services;

use App\Models\BackupRun;
use App\Models\DisasterRecoveryDrill;
use App\Models\Tenant;
use App\Models\TenantBackupRun;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DisasterRecoveryDrillService
{
    public function runPlatformDrill(?int $userId = null): DisasterRecoveryDrill
    {
        $drill = DisasterRecoveryDrill::create([
            'scope' => 'platform',
            'status' => 'running',
            'started_at' => now(),
            'created_by' => $userId,
        ]);

        $backup = BackupRun::query()->where('status', 'completed')->latest('id')->first();
        if (! $backup) {
            return $this->finish($drill, false, 'No completed platform backup found.', [
                'rto_seconds' => 0,
                'rpo_hours' => null,
            ]);
        }

        $drill->backup_run_id = $backup->id;
        $drill->save();

        $started = microtime(true);
        [$ok, $message, $details] = $this->verifyBackupArchive($backup->path, $backup->disk, $backup->remote_path, $backup->checksum, true);
        $details['rto_seconds'] = round(max(0, microtime(true) - $started), 2);
        $details['rpo_hours'] = round(max(0, now()->diffInMinutes($backup->created_at) / 60), 2);

        return $this->finish($drill, $ok, $message, $details);
    }

    public function runTenantDrill(Tenant $tenant, ?int $userId = null): DisasterRecoveryDrill
    {
        $drill = DisasterRecoveryDrill::create([
            'scope' => 'tenant',
            'tenant_id' => $tenant->id,
            'status' => 'running',
            'started_at' => now(),
            'created_by' => $userId,
        ]);

        $backup = TenantBackupRun::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        if (! $backup) {
            return $this->finish($drill, false, 'No completed tenant backup found.', [
                'tenant_id' => $tenant->id,
                'rto_seconds' => 0,
                'rpo_hours' => null,
            ]);
        }

        $drill->tenant_backup_run_id = $backup->id;
        $drill->save();

        $started = microtime(true);
        [$ok, $message, $details] = $this->verifyBackupArchive($backup->path, $backup->disk, $backup->remote_path, $backup->checksum, false);
        $details['tenant_id'] = $tenant->id;
        $details['rto_seconds'] = round(max(0, microtime(true) - $started), 2);
        $details['rpo_hours'] = round(max(0, now()->diffInMinutes($backup->created_at) / 60), 2);

        return $this->finish($drill, $ok, $message, $details);
    }

    public function runAllTenantDrills(?int $userId = null): array
    {
        $results = [];
        Tenant::query()->where('status', 'active')->chunkById(50, function ($tenants) use (&$results, $userId): void {
            foreach ($tenants as $tenant) {
                $results[] = $this->runTenantDrill($tenant, $userId);
            }
        });

        return $results;
    }

    private function verifyBackupArchive(
        ?string $localPath,
        ?string $disk,
        ?string $remotePath,
        ?string $expectedChecksum,
        bool $platform
    ): array {
        $zipPath = $this->resolveZipPath($localPath, $disk, $remotePath);
        if (! $zipPath) {
            return [false, 'Backup archive not reachable.', ['archive_found' => false]];
        }

        $zip = new ZipArchive;
        $open = $zip->open($zipPath);
        if ($open !== true) {
            return [false, 'Backup archive is corrupted or unreadable.', [
                'archive_found' => true,
                'zip_open_code' => $open,
            ]];
        }

        $numFiles = $zip->numFiles;
        $entries = [];
        for ($i = 0; $i < min($numFiles, 20); $i++) {
            $entries[] = (string) $zip->getNameIndex($i);
        }

        $hasSql = false;
        $hasStorage = false;
        for ($i = 0; $i < $numFiles; $i++) {
            $name = strtolower((string) $zip->getNameIndex($i));
            if (str_ends_with($name, '.sql')) {
                $hasSql = true;
            }
            if (str_contains($name, 'storage') || str_ends_with($name, '.tar.gz')) {
                $hasStorage = true;
            }
        }
        $zip->close();

        $actualChecksum = @hash_file('sha256', $zipPath) ?: null;
        $checksumOk = ! $expectedChecksum || ! $actualChecksum || hash_equals((string) $expectedChecksum, (string) $actualChecksum);

        $tmpDownloaded = $disk === 's3' && is_file($zipPath) && str_contains($zipPath, sys_get_temp_dir());
        if ($tmpDownloaded) {
            @unlink($zipPath);
        }

        $details = [
            'archive_found' => true,
            'entries_preview' => $entries,
            'num_files' => $numFiles,
            'has_sql_dump' => $hasSql,
            'has_storage_archive' => $hasStorage,
            'checksum_ok' => $checksumOk,
        ];

        if (! $checksumOk) {
            return [false, 'Backup checksum verification failed.', $details];
        }

        if ($platform && (! $hasSql || ! $hasStorage)) {
            return [false, 'Platform backup structure is incomplete.', $details];
        }

        if (! $platform && ! $hasSql) {
            return [false, 'Tenant backup is missing SQL dump.', $details];
        }

        return [true, 'Disaster recovery drill passed.', $details];
    }

    private function resolveZipPath(?string $localPath, ?string $disk, ?string $remotePath): ?string
    {
        if ($localPath) {
            $zipPath = rtrim($localPath, '/').'/backup.zip';
            if (is_file($zipPath)) {
                return $zipPath;
            }
        }

        if ($disk === 's3' && $remotePath) {
            try {
                $stream = Storage::disk('s3')->readStream($remotePath);
                if (! $stream) {
                    return null;
                }
                $tmp = tempnam(sys_get_temp_dir(), 'tb-drill-');
                if (! $tmp) {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }

                    return null;
                }
                $target = fopen($tmp, 'wb');
                if (! $target) {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }

                    return null;
                }
                stream_copy_to_stream($stream, $target);
                fclose($target);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                return $tmp;
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    private function finish(DisasterRecoveryDrill $drill, bool $ok, string $message, array $details): DisasterRecoveryDrill
    {
        $drill->status = $ok ? 'passed' : 'failed';
        $drill->message = $message;
        $drill->details = $details;
        $drill->finished_at = now();
        $drill->save();

        return $drill->fresh();
    }
}
