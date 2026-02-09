<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\IpRestrictionService;
use App\Support\TenantContext;

class IpRestriction
{
    public function __construct(
        protected IpRestrictionService $ipService
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $tenant = TenantContext::id() ? \App\Models\Tenant::find(TenantContext::id()) : null;

        if (!$this->ipService->isAllowed($ip, $tenant)) {
            abort(403, 'Your IP address is not allowed to access this resource.');
        }

        return $next($request);
    }
}
