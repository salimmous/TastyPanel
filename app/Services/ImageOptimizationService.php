<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageOptimizationService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Optimize image to multiple formats (AVIF, WebP, original)
     */
    public function optimize(string $path, array $options = []): array
    {
        $quality = $options['quality'] ?? 85;
        $maxWidth = $options['max_width'] ?? 1920;
        $maxHeight = $options['max_height'] ?? 1080;

        try {
            // Load image
            $image = $this->manager->read($path);

            // Resize if needed
            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->scale(width: $maxWidth, height: $maxHeight);
            }

            $pathInfo = pathinfo($path);
            $directory = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];

            // Generate AVIF (best compression)
            $avifPath = "{$directory}/{$filename}.avif";
            $this->convertToAvif($image, $avifPath, $quality);

            // Generate WebP (fallback)
            $webpPath = "{$directory}/{$filename}.webp";
            $this->convertToWebp($image, $webpPath, $quality);

            // Keep original or optimize it
            $originalPath = $path;
            if ($pathInfo['extension'] !== 'avif' && $pathInfo['extension'] !== 'webp') {
                $image->save($originalPath, quality: $quality);
            }

            return [
                'avif' => $avifPath,
                'webp' => $webpPath,
                'original' => $originalPath,
                'sizes' => [
                    'avif' => filesize($avifPath),
                    'webp' => filesize($webpPath),
                    'original' => filesize($originalPath),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Image optimization failed: ' . $e->getMessage(), [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'original' => $path,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Convert to AVIF using shell command (best quality/size)
     */
    private function convertToAvif($image, string $outputPath, int $quality): void
    {
        // Save temp file
        $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.png';
        $image->toPng()->save($tempPath);

        $escapedTempPath = escapeshellarg($tempPath);
        $escapedOutputPath = escapeshellarg($outputPath);

        // Convert using avifenc if available
        if ($this->commandExists('avifenc')) {
            $qualityParam = (int) ($quality * 0.63); // AVIF uses 0-63 scale
            exec("avifenc -s {$qualityParam} {$escapedTempPath} {$escapedOutputPath} 2>&1", $output, $returnCode);

            if ($returnCode === 0 && file_exists($outputPath)) {
                unlink($tempPath);
                return;
            }
        }

        // Fallback: use ImageMagick if available
        if ($this->commandExists('convert')) {
            $escapedQuality = (int) $quality;
            exec("convert {$escapedTempPath} -quality {$escapedQuality} {$escapedOutputPath} 2>&1");
            if (file_exists($outputPath)) {
                unlink($tempPath);
                return;
            }
        }

        // Last resort: just save as WebP (better than nothing)
        $image->toWebp(quality: $quality)->save($outputPath);
        unlink($tempPath);
    }

    /**
     * Convert to WebP
     */
    private function convertToWebp($image, string $outputPath, int $quality): void
    {
        $image->toWebp(quality: $quality)->save($outputPath);
    }

    /**
     * Check if command exists in system
     */
    private function commandExists(string $command): bool
    {
        $escapedCommand = escapeshellarg($command);
        $result = shell_exec("which {$escapedCommand} 2>/dev/null");
        return !empty($result);
    }

    /**
     * Get optimized image URLs for use in templates
     */
    public function getUrls(string $basePath): array
    {
        $pathInfo = pathinfo($basePath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];

        return [
            'avif' => "{$directory}/{$filename}.avif",
            'webp' => "{$directory}/{$filename}.webp",
            'original' => $basePath,
        ];
    }

    /**
     * Calculate savings from optimization
     */
    public function calculateSavings(array $result): array
    {
        if (!isset($result['sizes'])) {
            return ['savings' => 0, 'percentage' => 0];
        }

        $original = $result['sizes']['original'];
        $avif = $result['sizes']['avif'];

        $savings = $original - $avif;
        $percentage = ($savings / $original) * 100;

        return [
            'savings' => $savings,
            'percentage' => round($percentage, 2),
            'original_size' => $original,
            'optimized_size' => $avif,
        ];
    }
}
