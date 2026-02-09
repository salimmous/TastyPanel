<?php

namespace App\Http\Middleware;

use App\Services\ApiKeyService;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);
        if (!$token) {
            return response()->json(['message' => 'API key required.'], 401);
        }

        $service = app(ApiKeyService::class);
        $key = $service->verify($token);
        if (!$key) {
            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        if (!$this->checkRateLimit($key->id, (int) ($key->rate_limit_per_minute ?? 0))) {
            return response()->json(['message' => 'API key rate limit exceeded.'], 429);
        }

        $key->last_used_at = now();
        $key->save();

        TenantContext::set($key->tenant, 'production');
        $request->attributes->set('api_key', $key);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            return trim(substr($header, 7));
        }

        if ($request->header('X-API-Key')) {
            return trim($request->header('X-API-Key'));
        }

        if ($request->query('api_key')) {
            return trim($request->query('api_key'));
        }

        return null;
    }

    private function checkRateLimit(int $keyId, int $limit): bool
    {
        if ($limit <= 0) {
            return true;
        }

        $bucket = now()->format('YmdHi');
        $cacheKey = "api_key_rate:{$keyId}:{$bucket}";
        $ttl = now()->addMinute();

        if (!Cache::has($cacheKey)) {
            Cache::put($cacheKey, 1, $ttl);
            return true;
        }

        Cache::increment($cacheKey);
        $hits = (int) Cache::get($cacheKey, 1);
        return $hits <= $limit;
    }
}
