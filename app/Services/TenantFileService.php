<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class TenantFileService
{
    public function list(int $tenantId, string $path = ''): array
    {
        $tenantRoot = $this->tenantRoot($tenantId);
        $relativePath = $this->sanitizePath($path);
        $absolutePath = $this->resolvePath($tenantRoot, $relativePath);

        if (!is_dir($absolutePath)) {
            File::makeDirectory($absolutePath, 0755, true);
        }

        $directories = [];
        foreach (File::directories($absolutePath) as $dir) {
            $directories[] = $this->mapItem($dir, $relativePath, true);
        }

        $files = [];
        foreach (File::files($absolutePath) as $file) {
            $files[] = $this->mapItem($file->getPathname(), $relativePath, false);
        }

        return [
            'path' => $relativePath,
            'items' => array_merge($directories, $files),
        ];
    }

    public function upload(int $tenantId, string $path, array $files): array
    {
        $tenantRoot = $this->tenantRoot($tenantId);
        $relativePath = $this->sanitizePath($path);
        $absolutePath = $this->resolvePath($tenantRoot, $relativePath);

        if (!is_dir($absolutePath)) {
            File::makeDirectory($absolutePath, 0755, true);
        }

        $uploaded = [];
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }
            $name = $this->sanitizeFileName($file->getClientOriginalName());
            if ($name === '') {
                $name = Str::random(12);
            }
            $file->move($absolutePath, $name);
            $uploaded[] = [
                'name' => $name,
                'path' => trim($relativePath . '/' . $name, '/'),
            ];
        }

        return $uploaded;
    }

    public function createFolder(int $tenantId, string $path, string $name): string
    {
        $tenantRoot = $this->tenantRoot($tenantId);
        $relativePath = $this->sanitizePath($path);
        $folderName = $this->sanitizeFileName($name);
        if ($folderName === '') {
            throw new RuntimeException('Invalid folder name.');
        }

        $absolutePath = $this->resolvePath($tenantRoot, trim($relativePath . '/' . $folderName, '/'));
        if (!is_dir($absolutePath)) {
            File::makeDirectory($absolutePath, 0755, true);
        }

        return trim($relativePath . '/' . $folderName, '/');
    }

    public function delete(int $tenantId, string $path): void
    {
        $tenantRoot = $this->tenantRoot($tenantId);
        $relativePath = $this->sanitizePath($path);
        if ($relativePath === '') {
            throw new RuntimeException('Path required.');
        }
        $absolutePath = $this->resolvePath($tenantRoot, $relativePath);

        if (is_dir($absolutePath)) {
            File::deleteDirectory($absolutePath);
            return;
        }

        if (file_exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    public function rename(int $tenantId, string $path, string $newName): string
    {
        $tenantRoot = $this->tenantRoot($tenantId);
        $relativePath = $this->sanitizePath($path);
        if ($relativePath === '') {
            throw new RuntimeException('Path required.');
        }
        $absolutePath = $this->resolvePath($tenantRoot, $relativePath);
        if (!file_exists($absolutePath)) {
            throw new RuntimeException('Path not found.');
        }

        $newName = $this->sanitizeFileName($newName);
        if ($newName === '') {
            throw new RuntimeException('Invalid name.');
        }

        $parent = dirname($relativePath);
        $targetRelative = trim(($parent === '.' ? '' : $parent) . '/' . $newName, '/');
        $targetAbsolute = $this->resolvePath($tenantRoot, $targetRelative);

        File::move($absolutePath, $targetAbsolute);

        return $targetRelative;
    }

    public function download(int $tenantId, string $path): array
    {
        $tenantRoot = $this->tenantRoot($tenantId);
        $relativePath = $this->sanitizePath($path);
        if ($relativePath === '') {
            throw new RuntimeException('Path required.');
        }
        $absolutePath = $this->resolvePath($tenantRoot, $relativePath);

        if (!is_file($absolutePath)) {
            throw new RuntimeException('File not found.');
        }

        return [
            'absolute' => $absolutePath,
            'name' => basename($absolutePath),
        ];
    }

    private function tenantRoot(int $tenantId): string
    {
        $root = config('services.storage.tenant_files_root', storage_path('app/tenant-files'));
        $root = rtrim($root, '/');
        $tenantRoot = $root . '/' . $tenantId;

        if (!is_dir($tenantRoot)) {
            File::makeDirectory($tenantRoot, 0755, true);
        }

        return $tenantRoot;
    }

    private function sanitizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');
        if ($path === '') {
            return '';
        }
        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new RuntimeException('Invalid path.');
            }
        }
        if (str_contains($path, '..')) {
            throw new RuntimeException('Invalid path.');
        }

        return $path;
    }

    private function resolvePath(string $root, string $relative): string
    {
        $root = rtrim($root, '/');
        $relative = trim($relative, '/');
        return $relative === '' ? $root : $root . '/' . $relative;
    }

    private function sanitizeFileName(string $name): string
    {
        $name = str_replace(['/', '\\'], '_', $name);
        $name = str_replace('..', '', $name);
        $name = trim($name);
        if ($name === '') {
            return '';
        }
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
        return $name ?? '';
    }

    private function mapItem(string $absolutePath, string $baseRelative, bool $isDir): array
    {
        $name = basename($absolutePath);
        $relative = trim($baseRelative . '/' . $name, '/');
        $modified = file_exists($absolutePath) ? date('Y-m-d H:i:s', filemtime($absolutePath)) : null;
        $size = $isDir ? null : (file_exists($absolutePath) ? filesize($absolutePath) : null);

        return [
            'name' => $name,
            'path' => $relative,
            'type' => $isDir ? 'dir' : 'file',
            'size' => $size,
            'modified_at' => $modified,
        ];
    }
}
