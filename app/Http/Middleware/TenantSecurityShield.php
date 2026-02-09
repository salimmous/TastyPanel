<?php

namespace App\Http\Middleware;

use App\Models\TenantSecurityProfile;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantSecurityShield
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::get();
        if (!$tenant) {
            return $next($request);
        }

        $profile = $tenant->securityProfile;
        if (!$profile) {
            return $next($request);
        }

        $ua = $request->userAgent() ?? '';
        $path = $request->path();

        if ($this->isBlockedUa($profile, $ua) || $this->isBlockedPath($profile, $path)) {
            if ($this->isLogMode($profile)) {
                $this->logViolation('blocklist', $request, [
                    'path' => $path,
                    'ua' => $ua,
                ]);
            } else {
                return $this->blockedResponse('blocklist');
            }
        }

        if ($this->wafEnabled($profile)) {
            $payload = $this->requestPayload($request);
            $reason = $this->detectWafViolation($profile, $payload);
            if ($reason !== null) {
                if ($this->isLogMode($profile)) {
                    $this->logViolation($reason, $request, [
                        'path' => $path,
                        'ua' => $ua,
                    ]);
                } else {
                    return $this->blockedResponse($reason);
                }
            }
        }

        return $next($request);
    }

    private function isBlockedUa(TenantSecurityProfile $profile, string $ua): bool
    {
        $rules = $profile->blocked_user_agents ?? [];
        foreach ($rules as $rule) {
            if ($rule && str_contains(strtolower($ua), strtolower($rule))) {
                return true;
            }
        }
        return false;
    }

    private function isBlockedPath(TenantSecurityProfile $profile, string $path): bool
    {
        $rules = $profile->blocked_paths ?? [];
        foreach ($rules as $rule) {
            if ($rule && str_starts_with($path, ltrim($rule, '/'))) {
                return true;
            }
        }
        return false;
    }

    private function wafEnabled(TenantSecurityProfile $profile): bool
    {
        if ($profile->waf_enabled === null) {
            return true;
        }

        return (bool) $profile->waf_enabled;
    }

    private function requestPayload(Request $request): string
    {
        $segments = [
            (string) $request->getPathInfo(),
            (string) $request->getQueryString(),
            json_encode($request->query->all(), JSON_UNESCAPED_SLASHES),
            (string) $request->getContent(),
        ];

        $payload = strtolower(implode("\n", array_filter($segments, static fn ($v) => $v !== null)));

        // Keep regex checks bounded to avoid expensive scans on large payloads.
        return substr($payload, 0, 10000);
    }

    private function detectWafViolation(TenantSecurityProfile $profile, string $payload): ?string
    {
        if (($profile->waf_block_sqli ?? true) && preg_match('/union\\s+select|sleep\\s*\\(|benchmark\\s*\\(|or\\s+1=1|drop\\s+table/i', $payload)) {
            return 'waf_sqli';
        }

        if (($profile->waf_block_xss ?? true) && preg_match('/<script|javascript:|onerror\\s*=|onload\\s*=/i', $payload)) {
            return 'waf_xss';
        }

        if (($profile->waf_block_lfi ?? true) && preg_match('/\\.\\.\\/|%2e%2e%2f|\\/etc\\/passwd|\\/proc\\/self\\/environ/i', $payload)) {
            return 'waf_lfi';
        }

        return null;
    }

    private function isLogMode(TenantSecurityProfile $profile): bool
    {
        $mode = strtolower((string) ($profile->waf_mode ?: $profile->mode ?: 'block'));
        return $mode === 'log';
    }

    private function logViolation(string $reason, Request $request, array $meta = []): void
    {
        logger()->warning('tenant-security-violation', array_merge($meta, [
            'reason' => $reason,
            'tenant_id' => TenantContext::id(),
            'ip' => $request->ip(),
        ]));
    }

    private function blockedResponse(string $reason): Response
    {
        return response()->json([
            'message' => 'Access blocked',
            'reason' => $reason,
        ], 451);
    }
}
