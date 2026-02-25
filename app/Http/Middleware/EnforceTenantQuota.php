<?php

namespace App\Http\Middleware;

use App\Services\TenantQuotaService;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantQuota
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::get();
        if (! $tenant) {
            return $next($request);
        }

        $quota = app(TenantQuotaService::class)->evaluateAndTrack($tenant);
        if (! ($quota['allowed'] ?? true)) {
            return response()->json([
                'message' => 'Tenant quota exceeded.',
                'reason' => $quota['reason'] ?? 'quota_exceeded',
                'usage' => $quota['usage'] ?? [],
            ], (int) ($quota['status'] ?? 429));
        }

        /** @var Response $response */
        $response = $next($request);
        $usage = $quota['usage']['requests'] ?? null;
        if (is_array($usage)) {
            $used = (int) ($usage['used'] ?? 0);
            $limit = $usage['limit'] ?? 'unlimited';
            $response->headers->set('X-Tenant-Requests', $used.'/'.$limit);
        }

        return $response;
    }
}
