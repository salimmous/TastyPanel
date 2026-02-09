<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $domain = $this->resolveDomain($host);
        if (!$domain) {
            $headerHost = trim((string) $request->header('X-Tenant-Host', ''));
            if ($headerHost !== '') {
                $domain = $this->resolveDomain($headerHost);
            }
        }

        if ($domain && !$this->isTenantDomainActive($domain)) {
            $domain = null;
        }

        $environment = $domain?->environment ?? 'production';
        TenantContext::set($domain?->tenant, $environment);
        if ($domain?->tenant) {
            $request->attributes->set('tenant', $domain->tenant);
        }

        return $next($request);
    }

    private function resolveDomain(string $host): ?Domain
    {
        $domain = Domain::with('tenant')->where('hostname', $host)->first();

        if ($domain) {
            return $domain;
        }

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
            return Domain::with('tenant')->where('hostname', $host)->first();
        }

        return null;
    }

    private function isTenantDomainActive(Domain $domain): bool
    {
        $tenant = $domain->tenant;
        if (!$tenant || $tenant->status !== 'active') {
            return false;
        }

        return $domain->status === 'active';
    }
}
