<?php

namespace App\Services;

use App\Models\BackupRestore;
use App\Models\BackupRun;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BackupRestoreService
{
    public function restore(BackupRun $backup, ?int $userId = null): BackupRestore
    {
        $restore = BackupRestore::create([
            'backup_run_id' => $backup->id,
            'status' => 'running',
            'started_at' => now(),
            'created_by' => $userId,
        ]);

        $output = [];
        $status = 'completed';

        try {
            $zipPath = $this->resolveBackupZip($backup, $output);
            $extractDir = storage_path('app/backups/restore_' . $backup->id . '_' . time());
            File::ensureDirectoryExists($extractDir);

            $this->extractZip($zipPath, $extractDir, $output);

            $dbPath = $extractDir . '/database.sql';
            if (file_exists($dbPath)) {
                $this->restoreDatabase($dbPath, $output);
            }

            $storagePath = $extractDir . '/storage.tar.gz';
            if (file_exists($storagePath)) {
                $this->restoreStorage($storagePath, $output);
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $output[] = $e->getMessage();
        }

        $restore->status = $status;
        $restore->output = implode("\n", $output);
        $restore->finished_at = now();
        $restore->save();

        return $restore;
    }

    private function resolveBackupZip(BackupRun $backup, array &$output): string
    {
        $localZip = $backup->path ? $backup->path . '/backup.zip' : null;
        if ($localZip && file_exists($localZip)) {
            return $localZip;
        }

        if ($backup->disk === 's3' && $backup->remote_path) {
            $tmp = storage_path('app/backups/restore_' . $backup->id . '.zip');
            $disk = Storage::disk('s3');
            $stream = $disk->readStream($backup->remote_path);
            if (!$stream) {
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

    private function extractZip(string $zipPath, string $destination, array &$output): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Unable to open backup zip.');
        }
        $zip->extractTo($destination);
        $zip->close();
        $output[] = 'Backup archive extracted.';
    }

    private function restoreDatabase(string $path, array &$output): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? '') === 'sqlite') {
            $source = $config['database'] ?? '';
            if (!$source) {
                throw new \RuntimeException('SQLite database path not configured.');
            }
            copy($path, $source);
            $output[] = 'SQLite database restored.';
            return;
        }

        if (($config['driver'] ?? '') !== 'mysql') {
            throw new \RuntimeException('Unsupported database driver for restore.');
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        $cmd = sprintf(
            'MYSQL_PWD=%s mysql -h %s -P %s -u %s %s < %s',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($path)
        );

        $output[] = 'Restoring MySQL database...';
        $lines = [];
        $exitCode = 0;
        exec('sh -c ' . escapeshellarg($cmd) . ' 2>&1', $lines, $exitCode);
        $output = array_merge($output, $lines);

        if ($exitCode !== 0) {
            throw new \RuntimeException('MySQL restore failed.');
        }
    }

    private function restoreStorage(string $path, array &$output): void
    {
        $storageRoot = storage_path();
        $cmd = sprintf('tar -xzf %s -C %s 2>&1', escapeshellarg($path), escapeshellarg($storageRoot));
        $lines = [];
        $exitCode = 0;
        exec($cmd, $lines, $exitCode);
        $output = array_merge($output, $lines);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Storage restore failed.');
        }

        $output[] = 'Storage restored.';
    }
}
