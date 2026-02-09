<?php

namespace App\Services;

use App\Models\SecurityBaseline;
use App\Models\SecurityIntegrityCheck;

class FileIntegrityService
{
    public function createBaseline(string $name, string $rootPath, array $paths = [], ?int $userId = null): SecurityBaseline
    {
        $paths = $paths ?: $this->defaultPaths();
        $hashes = $this->hashPaths($rootPath, $paths);

        return SecurityBaseline::create([
            'name' => $name,
            'root_path' => $rootPath,
            'paths' => $paths,
            'hashes' => $hashes,
            'created_by' => $userId,
        ]);
    }

    public function check(SecurityBaseline $baseline, ?int $userId = null): SecurityIntegrityCheck
    {
        $check = SecurityIntegrityCheck::create([
            'security_baseline_id' => $baseline->id,
            'status' => 'running',
            'started_at' => now(),
            'created_by' => $userId,
        ]);

        $output = [];
        try {
            $paths = $baseline->paths ?: $this->defaultPaths();
            $current = $this->hashPaths($baseline->root_path, $paths);
            $baselineHashes = $baseline->hashes ?? [];

            $changed = [];
            $missing = [];
            foreach ($baselineHashes as $path => $hash) {
                if (!array_key_exists($path, $current)) {
                    $missing[] = $path;
                    continue;
                }
                if ($current[$path] !== $hash) {
                    $changed[] = $path;
                }
            }

            $newFiles = array_diff_key($current, $baselineHashes);

            $output = [
                'changed' => array_values($changed),
                'missing' => array_values($missing),
                'new' => array_keys($newFiles),
                'counts' => [
                    'changed' => count($changed),
                    'missing' => count($missing),
                    'new' => count($newFiles),
                ],
            ];

            $check->status = 'completed';
        } catch (\Throwable $e) {
            $check->status = 'failed';
            $output = ['error' => $e->getMessage()];
        }

        $check->output = json_encode($output, JSON_PRETTY_PRINT);
        $check->finished_at = now();
        $check->save();

        return $check;
    }

    private function defaultPaths(): array
    {
        return [
            'app',
            'config',
            'routes',
            'resources',
            'artisan',
            'bootstrap/app.php',
            'composer.lock',
            'package-lock.json',
        ];
    }

    private function hashPaths(string $rootPath, array $paths): array
    {
        $hashes = [];

        foreach ($paths as $path) {
            $fullPath = rtrim($rootPath, '/') . '/' . ltrim($path, '/');
            if (is_dir($fullPath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($fullPath, \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $relative = $this->relativePath($rootPath, $file->getPathname());
                        if ($this->shouldSkip($relative)) {
                            continue;
                        }
                        $hashes[$relative] = hash_file('sha256', $file->getPathname());
                    }
                }
            } elseif (is_file($fullPath)) {
                $relative = $this->relativePath($rootPath, $fullPath);
                if (!$this->shouldSkip($relative)) {
                    $hashes[$relative] = hash_file('sha256', $fullPath);
                }
            }
        }

        return $hashes;
    }

    private function relativePath(string $rootPath, string $path): string
    {
        $rootPath = rtrim($rootPath, '/') . '/';
        if (str_starts_with($path, $rootPath)) {
            return substr($path, strlen($rootPath));
        }
        return $path;
    }

    private function shouldSkip(string $path): bool
    {
        $skipPrefixes = ['vendor/', 'node_modules/', 'storage/', '.git/'];
        foreach ($skipPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
