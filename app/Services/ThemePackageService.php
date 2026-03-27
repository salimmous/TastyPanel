<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use Illuminate\Filesystem\Filesystem;

class ThemePackageService
{
    private const ALLOWED_EXTENSIONS = [
        'css', 'js', 'json', 'xml', 'txt', 'md',
        'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico',
        'woff', 'woff2', 'ttf', 'eot',
        'blade.php'
    ];

    public function importThemeZip(UploadedFile $file, string $key): array
    {
        $zipPath = $this->storeZip($file, $key);
        $view = $this->extractThemeZip(Storage::disk('local')->path($zipPath), $key);

        return [
            'zip_path' => $zipPath,
            'view' => $view,
        ];
    }

    public function storeZip(UploadedFile $file, string $key): string
    {
        $safeKey = Str::slug($key);
        $timestamp = now()->format('Ymd_His');
        $filename = $safeKey . '-' . $timestamp . '.zip';

        return $file->storeAs("theme_versions/{$safeKey}", $filename);
    }

    public function extractThemeZip(string $zipPath, string $key): string
    {
        $safeKey = Str::slug($key);
        $targetDir = storage_path('themes/' . $safeKey);

        $filesystem = new Filesystem();

        // Clean up existing directory if it exists
        if (is_dir($targetDir)) {
            $filesystem->deleteDirectory($targetDir);
        }
        $filesystem->makeDirectory($targetDir, 0755, true, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Failed to open theme zip.');
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);

                // Security Check: Path Traversal
                if (str_contains($entry, '..') || str_starts_with($entry, '/') || str_starts_with($entry, '\\')) {
                    continue;
                }

                $destination = $targetDir . '/' . $entry;

                // Handle directory entries first
                if (str_ends_with($entry, '/')) {
                    if (!is_dir($destination)) {
                        $filesystem->makeDirectory($destination, 0755, true, true);
                    }
                    continue;
                }

                // Security Check: File Type Whitelist (only for files)
                if (!$this->isAllowedFile($entry)) {
                    throw new \RuntimeException("Security Violation: Disallowed file type '{$entry}'.");
                }

                $destinationDir = dirname($destination);
                if (!is_dir($destinationDir)) {
                    $filesystem->makeDirectory($destinationDir, 0755, true, true);
                }

                $stream = $zip->getStream($entry);
                if ($stream === false) {
                    continue;
                }
                $contents = stream_get_contents($stream);
                fclose($stream);

                // Security Check: Blade Content
                if (str_ends_with($entry, '.blade.php')) {
                    if (!$this->isSafeBladeContent($contents)) {
                        throw new \RuntimeException("Security Violation: Unsafe content in '{$entry}'.");
                    }
                }

                file_put_contents($destination, $contents);
            }
        } catch (\Exception $e) {
            $zip->close();
            // Clean up any files that might have been extracted before the error
            if (is_dir($targetDir)) {
                $filesystem->deleteDirectory($targetDir);
            }
            throw $e;
        }

        $zip->close();

        $view = null;
        if (file_exists($targetDir . '/home.blade.php')) {
            $view = 'tenant::' . $safeKey . '.home';
        } elseif (file_exists($targetDir . '/index.blade.php')) {
            $view = 'tenant::' . $safeKey . '.index';
        }

        if (!$view) {
            $filesystem->deleteDirectory($targetDir);
            throw new \RuntimeException('Theme zip must include home.blade.php or index.blade.php');
        }

        return $view;
    }

    private function isAllowedFile(string $filename): bool
    {
        $filename = strtolower($filename);

        // Explicitly block .php files unless they are .blade.php
        if (str_ends_with($filename, '.php') && !str_ends_with($filename, '.blade.php')) {
            return false;
        }

        // Allow .blade.php
        if (str_ends_with($filename, '.blade.php')) {
            return true;
        }

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return in_array($ext, self::ALLOWED_EXTENSIONS);
    }

    private function isSafeBladeContent(string $content): bool
    {
        // Block standard PHP tags
        if (preg_match('/<\?php/i', $content) || preg_match('/<\?=/i', $content)) {
            return false;
        }

        // Block Blade PHP directive
        if (preg_match('/@php/i', $content)) {
            return false;
        }

        // Block script language=php
        if (preg_match('/<script\s+language\s*=\s*[\'"]?php[\'"]?/i', $content)) {
            return false;
        }

        return true;
    }
}
