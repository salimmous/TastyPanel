<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantBackupRestore;
use App\Models\TenantBackupRun;
use Illuminate\Support\Facades\Storage;

class TenantBackupRestoreService
{
    public function restore(Tenant $tenant, TenantBackupRun $backup, ?int $userId = null): TenantBackupRestore
    {
        $restore = TenantBackupRestore::create([
            'tenant_backup_run_id' => $backup->id,
            'status' => 'running',
            'started_at' => now(),
            'created_by' => $userId,
        ]);

        $output = [];
        $status = 'completed';

        try {
            $zipPath = $this->resolveBackupZip($backup, $output);
            $result = $this->runRestoreScript($tenant, $zipPath);
            if (! $result['success']) {
                throw new \RuntimeException($result['output'] ?: 'Restore failed.');
            }
            $output[] = $result['output'];

            if (str_contains($zipPath, storage_path('app/tenant-backups/restore_')) && file_exists($zipPath)) {
                @unlink($zipPath);
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $output[] = $e->getMessage();
        }

        $restore->status = $status;
        $restore->output = trim(implode("\n", array_filter($output)));
        $restore->finished_at = now();
        $restore->save();

        return $restore;
    }

    private function resolveBackupZip(TenantBackupRun $backup, array &$output): string
    {
        $localZip = $backup->path ? $backup->path.'/backup.zip' : null;
        if ($localZip && file_exists($localZip)) {
            return $localZip;
        }

        if ($backup->disk === 's3' && $backup->remote_path) {
            $tmp = storage_path('app/tenant-backups/restore_'.$backup->id.'.zip');
            $disk = Storage::disk('s3');
            $stream = $disk->readStream($backup->remote_path);
            if (! $stream) {
                throw new \RuntimeException('Unable to download backup from S3.');
            }
            $dest = fopen($tmp, 'w');
            stream_copy_to_stream($stream, $dest);
            fclose($dest);
            if (is_resource($stream)) {
                fclose($stream);
            }
            $output[] = 'Downloaded backup from S3.';

            return $tmp;
        }

        throw new \RuntimeException('Backup archive not found.');
    }

    private function runRestoreScript(Tenant $tenant, string $zipPath): array
    {
        if (! $tenant->instance_root || ! is_dir($tenant->instance_root)) {
            return [
                'success' => false,
                'output' => 'Instance root is missing.',
                'exit_code' => 1,
            ];
        }
        if (! $tenant->instance_db_name || ! $tenant->instance_db_user) {
            return [
                'success' => false,
                'output' => 'Instance database credentials are missing.',
                'exit_code' => 1,
            ];
        }

        $script = config('services.tenant_backups.restore_script');
        if (! $script || ! file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Tenant restore script not found.',
                'exit_code' => 1,
            ];
        }

        $commandParts = [];
        if (config('services.tenant_backups.use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = $script;
        $commandParts[] = $tenant->instance_root;
        $commandParts[] = $tenant->instance_db_name ?: '';
        $commandParts[] = $tenant->instance_db_user ?: '';
        $commandParts[] = $tenant->instance_db_password ?: '';
        $commandParts[] = $zipPath;

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));
        $output = [];
        $exitCode = 0;
        exec($escaped.' 2>&1', $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'output' => implode("\n", $output),
            'exit_code' => $exitCode,
        ];
    }
}
