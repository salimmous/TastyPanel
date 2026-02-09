<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Domain;
use App\Models\Recipe;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class EdgeCacheService
{
    private int $ttl = 3600; // 1h

    public function warmTenant(Tenant $tenant, int $limit = 10): int
    {
        $domain = Domain::where('tenant_id', $tenant->id)->where('is_primary', true)->first()
            ?? Domain::where('tenant_id', $tenant->id)->first();
        if (!$domain) {
            return 0;
        }

        $host = $domain->hostname;
        $base = str_starts_with($host, 'http') ? $host : "https://{$host}";

        $urls = [$base . '/'];

        $articles = Article::where('tenant_id', $tenant->id)
            ->where('environment', 'production')
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['slug']);
        foreach ($articles as $article) {
            $urls[] = $base . '/articles/' . $article->slug;
        }

        $recipes = Recipe::where('tenant_id', $tenant->id)
            ->where('environment', 'production')
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['slug']);
        foreach ($recipes as $recipe) {
            $urls[] = $base . '/recipes/' . $recipe->slug;
        }

        $warmed = 0;
        foreach ($urls as $url) {
            try {
                $response = Http::timeout(10)->get($url);
                if ($response->ok()) {
                    $this->store($host, $this->pathFromUrl($url), $response->body(), $response->headers());
                    $warmed++;
                }
            } catch (\Throwable $e) {
                // ignore failures to keep command resilient
            }
        }

        return $warmed;
    }

    public function store(string $host, string $path, string $body, array $headers = []): void
    {
        $key = $this->key($host, $path);
        Cache::put($key, [
            'body' => $body,
            'headers' => $headers,
        ], $this->ttl);
    }

    public function fetch(string $host, string $path): ?array
    {
        return Cache::get($this->key($host, $path));
    }

    private function key(string $host, string $path): string
    {
        return 'edgecache:' . $host . ':' . ltrim($path ?: '/', '/');
    }

    private function pathFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        return $parsed['path'] ?? '/';
    }
}
