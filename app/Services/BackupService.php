<?php

namespace App\Services;

use App\Models\BackupRun;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BackupService
{
    public function run(?int $userId = null): BackupRun
    {
        $run = BackupRun::create([
            'type' => 'manual',
            'status' => 'running',
            'started_at' => now(),
            'created_by' => $userId,
        ]);

        $timestamp = now()->format('Ymd_His');
        $backupDir = storage_path('app/backups/' . $timestamp);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $output = [];
        $status = 'completed';
        $settings = PlatformSetting::getData();
        $uploadToS3 = (bool) ($settings['backup_s3_enabled'] ?? false);
        $keepLocal = (bool) ($settings['backup_keep_local'] ?? true);
        $s3Prefix = trim((string) ($settings['backup_s3_prefix'] ?? 'tastypanel/backups'), '/');

        try {
            $dbPath = $backupDir . '/database.sql';
            $this->dumpDatabase($dbPath, $output);

            $filesPath = $backupDir . '/storage.tar.gz';
            $this->archiveStorage($filesPath, $output);

            $zipPath = $backupDir . '/backup.zip';
            $this->createZip($zipPath, [$dbPath, $filesPath], $output);

            $size = 0;
            foreach ([$dbPath, $filesPath, $zipPath] as $path) {
                if (file_exists($path)) {
                    $size += filesize($path);
                }
            }

            $run->path = $backupDir;
            $run->size_bytes = $size ?: null;
            $run->disk = 'local';
            $run->checksum = file_exists($zipPath) ? hash_file('sha256', $zipPath) : null;

            if ($uploadToS3) {
                $remotePath = $s3Prefix . '/' . $timestamp . '/backup.zip';
                $stream = fopen($zipPath, 'r');
                Storage::disk('s3')->put($remotePath, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $run->disk = 's3';
                $run->remote_path = $remotePath;
                $output[] = 'Backup uploaded to S3.';
            }

            if (!$keepLocal && file_exists($backupDir)) {
                $this->deleteDirectory($backupDir);
                $run->path = null;
                $output[] = 'Local backup removed.';
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $output[] = $e->getMessage();
        }

        $run->status = $status;
        $run->output = implode("\n", $output);
        $run->finished_at = now();
        $run->save();

        return $run;
    }

    /**
     * Backup specific tenant
     */
    public function backupTenant(Tenant $tenant): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = "tenant_{$tenant->id}_{$timestamp}";
        $backupDir = storage_path("app/backups/tenants/{$backupName}");

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        try {
            // Backup tenant database
            $dbFile = $this->backupTenantDatabase($tenant, $backupDir);

            // Backup tenant files
            $filesFile = $this->backupTenantFiles($tenant, $backupDir);

            // Create ZIP
            $zipPath = storage_path("app/backups/tenants/{$backupName}.zip");
            $this->createZip($zipPath, [$dbFile, $filesFile], $output = []);

            // Upload to cloud if enabled
            if (config('backup.cloud_enabled')) {
                Storage::disk(config('backup.cloud_disk', 's3'))
                    ->put("tenants/{$backupName}.zip", file_get_contents($zipPath));
            }

            // Cleanup temp dir
            $this->deleteDirectory($backupDir);

            Log::info("Tenant backup completed: {$tenant->id}");

            return $zipPath;
        } catch (\Exception $e) {
            Log::error("Tenant backup failed: {$tenant->id}", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Backup tenant database
     */
    private function backupTenantDatabase(Tenant $tenant, string $backupDir): string
    {
        $dbName = $tenant->instance_db_name;
        $dbUser = $tenant->instance_db_user;
        $dbPass = $tenant->instance_db_password;
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');

        $outputFile = "{$backupDir}/database.sql";

        $cmd = sprintf(
            'MYSQL_PWD=%s mysqldump --single-transaction -h %s -u %s --result-file=%s %s 2>&1',
            escapeshellarg($dbPass),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($outputFile),
            escapeshellarg($dbName)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException("Tenant DB backup failed: " . implode("\n", $output));
        }

        return $outputFile;
    }

    /**
     * Backup tenant files
     */
    private function backupTenantFiles(Tenant $tenant, string $backupDir): string
    {
        $filesRoot = storage_path("app/tenant-files/{$tenant->id}");
        $outputFile = "{$backupDir}/files.tar.gz";

        if (!is_dir($filesRoot)) {
            touch($outputFile); // Empty file
            return $outputFile;
        }

        $cmd = sprintf(
            'tar -czf %s -C %s . 2>&1',
            escapeshellarg($outputFile),
            escapeshellarg($filesRoot)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException("Tenant files backup failed");
        }

        return $outputFile;
    }

    /**
     * Restore tenant from backup
     */
    public function restoreTenant(Tenant $tenant, string $backupFile): bool
    {
        $extractDir = storage_path("app/backups/restore_" . uniqid());

        try {
            mkdir($extractDir, 0755, true);

            // Extract ZIP
            $cmd = sprintf('unzip -q %s -d %s', escapeshellarg($backupFile), escapeshellarg($extractDir));
            exec($cmd);

            // Restore database
            if (file_exists("{$extractDir}/database.sql")) {
                $this->restoreTenantDatabase($tenant, "{$extractDir}/database.sql");
            }

            // Restore files
            if (file_exists("{$extractDir}/files.tar.gz")) {
                $this->restoreTenantFiles($tenant, "{$extractDir}/files.tar.gz");
            }

            // Cleanup
            $this->deleteDirectory($extractDir);

            Log::info("Tenant restore completed: {$tenant->id}");

            return true;
        } catch (\Exception $e) {
            Log::error("Tenant restore failed: {$tenant->id}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Restore tenant database
     */
    private function restoreTenantDatabase(Tenant $tenant, string $sqlFile): void
    {
        $dbName = $tenant->instance_db_name;
        $dbUser = $tenant->instance_db_user;
        $dbPass = $tenant->instance_db_password;
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');

        $cmd = sprintf(
            'MYSQL_PWD=%s mysql -h %s -u %s %s < %s 2>&1',
            escapeshellarg($dbPass),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbName),
            escapeshellarg($sqlFile)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException("Database restore failed");
        }
    }

    /**
     * Restore tenant files
     */
    private function restoreTenantFiles(Tenant $tenant, string $tarFile): void
    {
        $filesRoot = storage_path("app/tenant-files/{$tenant->id}");

        if (!is_dir($filesRoot)) {
            mkdir($filesRoot, 0755, true);
        }

        $cmd = sprintf(
            'tar -xzf %s -C %s 2>&1',
            escapeshellarg($tarFile),
            escapeshellarg($filesRoot)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException("Files restore failed");
        }
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

    private function dumpDatabase(string $path, array &$output): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? '') === 'sqlite') {
            $source = $config['database'] ?? '';
            if (!$source || !file_exists($source)) {
                throw new \RuntimeException('SQLite database not found.');
            }
            copy($source, $path);
            $output[] = 'SQLite database copied.';
            return;
        }

        if (($config['driver'] ?? '') !== 'mysql') {
            throw new \RuntimeException('Unsupported database driver for backup.');
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        $cmd = sprintf(
            'MYSQL_PWD=%s mysqldump --single-transaction -h %s -P %s -u %s --result-file=%s %s 2>&1',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($path),
            escapeshellarg($database)
        );

        $lines = [];
        $exitCode = 0;
        exec($cmd, $lines, $exitCode);
        $output = array_merge($output, $lines);

        if ($exitCode !== 0) {
            throw new \RuntimeException('mysqldump failed.');
        }
    }

    private function archiveStorage(string $path, array &$output): void
    {
        $storage = storage_path();
        $cmd = sprintf(
            'tar -czf %s -C %s %s %s 2>&1',
            escapeshellarg($path),
            escapeshellarg($storage),
            escapeshellarg('themes'),
            escapeshellarg('app/nginx')
        );

        $lines = [];
        $exitCode = 0;
        exec($cmd, $lines, $exitCode);
        $output = array_merge($output, $lines);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Storage archive failed.');
        }
    }

    private function createZip(string $zipPath, array $files, array &$output): void
    {
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        $args = array_map('escapeshellarg', $files);
        $cmd = sprintf('zip -j %s %s 2>&1', escapeshellarg($zipPath), implode(' ', $args));
        $lines = [];
        $exitCode = 0;
        exec($cmd, $lines, $exitCode);
        $output = array_merge($output, $lines);
    }
}
