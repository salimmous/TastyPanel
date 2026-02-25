<?php

namespace App\Services;

use App\Models\BackupRun;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\File;

class BackupCleanupService
{
    public function cleanup(?int $days = null): int
    {
        $days = $days ?? $this->retentionDays();
        if ($days <= 0) {
            return 0;
        }

        $cutoff = now()->subDays($days);
        $deleted = 0;

        $runs = BackupRun::whereNotNull('path')
            ->where(function ($query) use ($cutoff) {
                $query->where('started_at', '<=', $cutoff)
                    ->orWhere('created_at', '<=', $cutoff);
            })
            ->get();

        foreach ($runs as $run) {
            if ($run->path && File::exists($run->path)) {
                File::deleteDirectory($run->path);
                $deleted++;
            }
            $run->status = 'expired';
            $run->save();
        }

        $this->cleanupOrphans($cutoff, $deleted);

        return $deleted;
    }

    private function cleanupOrphans($cutoff, int &$deleted): void
    {
        $root = storage_path('app/backups');
        if (! File::exists($root)) {
            return;
        }

        $directories = File::directories($root);
        foreach ($directories as $dir) {
            $mtime = File::lastModified($dir);
            if ($mtime && $mtime <= $cutoff->timestamp) {
                File::deleteDirectory($dir);
                $deleted++;
            }
        }
    }

    private function retentionDays(): int
    {
        $settings = PlatformSetting::getData();
        $days = $settings['backup_retention_days'] ?? 7;

        return max(0, (int) $days);
    }
}
