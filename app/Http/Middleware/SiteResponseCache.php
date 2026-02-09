<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Domain;
use App\Services\EdgeCacheService;

class SiteResponseCache
{
    private int $ttlSeconds = 120;

    public function handle(Request $request, Closure $next)
    {
        // Only cache public GET/HEAD with no query string
        if (!in_array($request->method(), ['GET', 'HEAD'], true) || $request->getQueryString()) {
            return $next($request);
        }

        $host = $request->getHost();
        $domain = $this->resolveDomain($host);
        if (!$domain) {
            return $next($request);
        }

        // Share resolved domain with controllers to avoid duplicate lookup
        $request->attributes->set('resolved_domain', $domain);

        $cacheKey = sprintf('site-cache:%s:%s', $host, $request->path() ?: 'home');

        /** edge cache hit first */
        if ($edge = app(EdgeCacheService::class)->fetch($host, $request->path())) {
            return response($edge['body'], 200, $edge['headers'] ?? []);
        }

        if ($cached = Cache::get($cacheKey)) {
            return response($cached['content'], $cached['status'], $cached['headers']);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200 &&
            str_contains($response->headers->get('Content-Type', ''), 'text/html') &&
            !auth()->check()) {
            Cache::put($cacheKey, [
                'status' => 200,
                'headers' => $response->headers->allPreserveCase(),
                'content' => $response->getContent(),
            ], $this->ttlSeconds);
        }

        return $response;
    }

    private function resolveDomain(string $host): ?Domain
    {
        $domain = Domain::with(['tenant.theme', 'tenant.stagingTheme', 'tenant.settings', 'tenant.stagingSettings'])
            ->where('hostname', $host)
            ->first();

        if ($domain) {
            return $domain;
        }

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
            return Domain::with(['tenant.theme', 'tenant.stagingTheme', 'tenant.settings', 'tenant.stagingSettings'])
                ->where('hostname', $host)
                ->first();
        }

        return null;
    }
}
