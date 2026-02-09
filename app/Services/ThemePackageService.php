<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use Illuminate\Filesystem\Filesystem;

class ThemePackageService
{
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
        if (is_dir($targetDir)) {
            $filesystem->deleteDirectory($targetDir);
        }
        $filesystem->makeDirectory($targetDir, 0755, true, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Failed to open theme zip.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (str_contains($entry, '..') || str_starts_with($entry, '/') || str_starts_with($entry, '\\')) {
                continue;
            }

            $destination = $targetDir . '/' . $entry;
            $destinationDir = dirname($destination);

            if (!is_dir($destinationDir)) {
                $filesystem->makeDirectory($destinationDir, 0755, true, true);
            }

            if (str_ends_with($entry, '/')) {
                if (!is_dir($destination)) {
                    $filesystem->makeDirectory($destination, 0755, true, true);
                }
                continue;
            }

            $stream = $zip->getStream($entry);
            if ($stream === false) {
                continue;
            }
            $contents = stream_get_contents($stream);
            fclose($stream);
            file_put_contents($destination, $contents);
        }

        $zip->close();

        $view = null;
        if (file_exists($targetDir . '/home.blade.php')) {
            $view = 'tenant::' . $safeKey . '.home';
        } elseif (file_exists($targetDir . '/index.blade.php')) {
            $view = 'tenant::' . $safeKey . '.index';
        }

        if (!$view) {
            throw new \RuntimeException('Theme zip must include home.blade.php or index.blade.php');
        }

        return $view;
    }
}
