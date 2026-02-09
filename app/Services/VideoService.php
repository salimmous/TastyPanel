<?php

namespace App\Services;

class VideoService
{
    /**
     * Parse video URL and extract info
     */
    public function parseUrl(string $url): ?array
    {
        // YouTube
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return [
                'provider' => 'youtube',
                'video_id' => $matches[1],
                'embed_url' => "https://www.youtube.com/embed/{$matches[1]}",
                'thumbnail' => "https://img.youtube.com/vi/{$matches[1]}/maxresdefault.jpg",
            ];
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return [
                'provider' => 'vimeo',
                'video_id' => $matches[1],
                'embed_url' => "https://player.vimeo.com/video/{$matches[1]}",
                'thumbnail' => null, // Requires API call
            ];
        }

        // TikTok
        if (preg_match('/tiktok\.com\/@[^\/]+\/video\/(\d+)/', $url, $matches)) {
            return [
                'provider' => 'tiktok',
                'video_id' => $matches[1],
                'embed_url' => "https://www.tiktok.com/embed/v2/{$matches[1]}",
                'thumbnail' => null,
            ];
        }

        // Instagram
        if (preg_match('/instagram\.com\/(?:p|reel)\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return [
                'provider' => 'instagram',
                'video_id' => $matches[1],
                'embed_url' => "https://www.instagram.com/p/{$matches[1]}/embed",
                'thumbnail' => null,
            ];
        }

        return null;
    }

    /**
     * Generate embed HTML
     */
    public function getEmbedHtml(string $provider, string $videoId, array $options = []): string
    {
        $width = $options['width'] ?? '100%';
        $height = $options['height'] ?? '315';

        return match ($provider) {
            'youtube' => "<iframe width=\"{$width}\" height=\"{$height}\" src=\"https://www.youtube.com/embed/{$videoId}\" frameborder=\"0\" allowfullscreen></iframe>",
            'vimeo' => "<iframe width=\"{$width}\" height=\"{$height}\" src=\"https://player.vimeo.com/video/{$videoId}\" frameborder=\"0\" allowfullscreen></iframe>",
            'tiktok' => "<iframe width=\"{$width}\" height=\"{$height}\" src=\"https://www.tiktok.com/embed/v2/{$videoId}\" frameborder=\"0\" allowfullscreen></iframe>",
            'instagram' => "<iframe width=\"{$width}\" height=\"{$height}\" src=\"https://www.instagram.com/p/{$videoId}/embed\" frameborder=\"0\" allowfullscreen></iframe>",
            default => '',
        };
    }

    /**
     * Process video URL for recipe
     */
    public function processForRecipe(string $url): array
    {
        $parsed = $this->parseUrl($url);

        if (!$parsed) {
            return [
                'video_url' => $url,
                'video_embed' => null,
                'video_provider' => null,
            ];
        }

        return [
            'video_url' => $url,
            'video_embed' => $parsed['embed_url'],
            'video_provider' => $parsed['provider'],
        ];
    }
}
