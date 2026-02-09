<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantBackupRun;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Storage;

class TenantBackupCleanupService
{
    public function cleanup(?int $overrideDays = null): int
    {
        $deleted = 0;
        $platform = PlatformSetting::getData();

        $tenants = Tenant::query()->get();
        foreach ($tenants as $tenant) {
            $retention = $overrideDays;
            if ($retention === null) {
                $retention = $tenant->backup_retention_days;
            }
            if ($retention === null) {
                $retention = $platform['tenant_backup_retention_days'] ?? $platform['backup_retention_days'] ?? 7;
            }

            $retention = (int) $retention;
            if ($retention <= 0) {
                continue;
            }

            $cutoff = now()->subDays($retention);
            $runs = TenantBackupRun::where('tenant_id', $tenant->id)
                ->whereNotNull('finished_at')
                ->where('finished_at', '<', $cutoff)
                ->get();

            foreach ($runs as $run) {
                if ($run->path && is_dir($run->path)) {
                    $this->deleteDirectory($run->path);
                }
                if ($run->disk === 's3' && $run->remote_path) {
                    try {
                        Storage::disk('s3')->delete($run->remote_path);
                    } catch (\Throwable $e) {
                        // Ignore cleanup errors for remote paths.
                    }
                }
                $run->delete();
                $deleted++;
            }
        }

        return $deleted;
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
