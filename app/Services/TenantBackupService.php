<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\TenantBackupRun;
use Illuminate\Support\Facades\Storage;

class TenantBackupService
{
    public function run(Tenant $tenant, ?int $userId = null, string $type = 'manual'): TenantBackupRun
    {
        $run = TenantBackupRun::create([
            'tenant_id' => $tenant->id,
            'type' => $type,
            'status' => 'running',
            'started_at' => now(),
            'created_by' => $userId,
        ]);

        $output = [];
        $status = 'completed';
        $settings = $this->settings($tenant);
        if (!$settings['s3_enabled'] && !$settings['keep_local']) {
            $settings['keep_local'] = true;
            $output[] = 'Keep local enabled because S3 is disabled.';
        }

        try {
            $this->assertTenantReady($tenant);

            $timestamp = now()->format('Ymd_His');
            $backupDir = $this->backupDirectory($tenant, $timestamp);
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $result = $this->runScript($tenant, $backupDir);
            if (!$result['success']) {
                throw new \RuntimeException($result['output'] ?: 'Backup script failed.');
            }

            $output[] = $result['output'];

            $zipPath = $backupDir . '/backup.zip';
            $size = file_exists($zipPath) ? filesize($zipPath) : null;
            $checksum = file_exists($zipPath) ? hash_file('sha256', $zipPath) : null;

            $run->path = $backupDir;
            $run->disk = 'local';
            $run->size_bytes = $size ?: null;
            $run->checksum = $checksum;

            if ($settings['s3_enabled']) {
                $remotePath = $this->s3Path($tenant, $timestamp, $settings['s3_prefix']);
                $stream = fopen($zipPath, 'r');
                Storage::disk('s3')->put($remotePath, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $run->disk = 's3';
                $run->remote_path = $remotePath;
                $output[] = 'Backup uploaded to S3.';
            }

            if (!$settings['keep_local']) {
                $this->deleteDirectory($backupDir);
                $run->path = null;
                $output[] = 'Local backup removed.';
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $output[] = $e->getMessage();
        }

        $run->status = $status;
        $run->output = trim(implode("\n", array_filter($output)));
        $run->finished_at = now();
        $run->save();

        return $run;
    }

    public function settings(Tenant $tenant): array
    {
        $platform = PlatformSetting::getData();

        $interval = $tenant->backup_interval_hours;
        if ($interval === null) {
            $interval = $platform['tenant_backup_interval_hours'] ?? $platform['backup_interval_hours'] ?? 24;
        }

        $retention = $tenant->backup_retention_days;
        if ($retention === null) {
            $retention = $platform['tenant_backup_retention_days'] ?? $platform['backup_retention_days'] ?? 7;
        }

        $s3Prefix = $tenant->backup_s3_prefix ?: ($platform['tenant_backup_s3_prefix'] ?? 'tastypanel/tenant-backups');

        return [
            'enabled' => (bool) $tenant->backup_enabled,
            'interval_hours' => (int) $interval,
            'retention_days' => (int) $retention,
            's3_enabled' => (bool) $tenant->backup_s3_enabled,
            'keep_local' => (bool) $tenant->backup_keep_local,
            's3_prefix' => trim((string) $s3Prefix, '/'),
        ];
    }

    private function backupDirectory(Tenant $tenant, string $timestamp): string
    {
        $root = config('services.tenant_backups.root', storage_path('app/tenant-backups'));
        return rtrim($root, '/') . '/' . $tenant->id . '/' . $timestamp;
    }

    private function s3Path(Tenant $tenant, string $timestamp, string $prefix): string
    {
        return trim($prefix, '/') . '/' . $tenant->id . '/' . $timestamp . '/backup.zip';
    }

    private function assertTenantReady(Tenant $tenant): void
    {
        if (!$tenant->instance_root || !is_dir($tenant->instance_root)) {
            throw new \RuntimeException('Instance root is missing.');
        }
        if (!$tenant->instance_db_name || !$tenant->instance_db_user) {
            throw new \RuntimeException('Instance database credentials are missing.');
        }
    }

    private function runScript(Tenant $tenant, string $backupDir): array
    {
        $script = config('services.tenant_backups.script');
        if (!$script || !file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Tenant backup script not found.',
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
        $commandParts[] = $tenant->instance_db_name;
        $commandParts[] = $tenant->instance_db_user;
        $commandParts[] = $tenant->instance_db_password ?: '';
        $commandParts[] = $backupDir;

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));
        $output = [];
        $exitCode = 0;
        exec($escaped . ' 2>&1', $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'output' => implode("\n", $output),
            'exit_code' => $exitCode,
        ];
    }

    private function deleteDirectory(string $path): void
    {
        $it = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
        @rmdir($path);
    }
}
