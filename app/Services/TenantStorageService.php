<?php

namespace App\Services;

use App\Models\Tenant;

class TenantStorageService
{
    public function usage(Tenant $tenant): array
    {
        $paths = [
            storage_path('app/tenants/' . $tenant->id),
            storage_path('app/public/tenants/' . $tenant->id),
        ];

        $bytes = 0;
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $bytes += $this->directorySize($path);
            }
        }

        return [
            'bytes' => $bytes,
            'paths' => $paths,
        ];
    }

    private function directorySize(string $path): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
}
