<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use App\Services\TenantLimitService;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ThrottleTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $limit = $this->rateLimit();
        $tenant = TenantContext::get();
        if ($tenant) {
            if ($tenant->securityProfile && $tenant->securityProfile->rate_limit_per_minute) {
                $limit = (int) $tenant->securityProfile->rate_limit_per_minute;
            }
            $planLimit = app(TenantLimitService::class)->limitValue($tenant, 'rate_limit_per_minute', 0);
            if ($planLimit > 0) {
                $limit = $planLimit;
            }
        }
        if ($limit <= 0) {
            return $next($request);
        }

        $tenantId = TenantContext::id() ?? 'public';
        $key = sprintf('tenant_rate:%s:%s:%s', $tenantId, $request->ip(), $request->path());
        $ttl = now()->addMinute();

        if (!Cache::has($key)) {
            Cache::put($key, 1, $ttl);
        } else {
            Cache::increment($key);
        }

        $hits = (int) Cache::get($key, 1);
        if ($hits > $limit) {
            return response()->json([
                'message' => 'Rate limit exceeded.',
            ], 429);
        }

        return $next($request);
    }

    private function rateLimit(): int
    {
        $settings = PlatformSetting::getData();
        $limit = $settings['rate_limit_per_minute'] ?? config('services.platform.rate_limit_per_minute', 120);
        return (int) $limit;
    }
}
