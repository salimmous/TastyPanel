<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Support\AdminTenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuditLog
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldSkip($request)) {
            return $response;
        }

        $user = $request->user();
        $tenantId = AdminTenantResolver::resolveId($request);

        $payload = $request->except([
            'password',
            'password_confirmation',
            'current_password',
            'two_factor_code',
        ]);

        try {
            AuditLog::create([
                'user_id' => $user?->id,
                'tenant_id' => $tenantId,
                'action' => $this->actionLabel($request),
                'route' => $request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'meta' => [
                    'status' => $response->getStatusCode(),
                    'payload' => $payload,
                    'query' => $request->query(),
                ],
            ]);
        } catch (\Throwable $e) {
            // Ignore audit logging failures.
        }

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        if ($request->method() === 'GET') {
            return true;
        }

        $path = $request->path();
        return str_contains($path, 'admin/login')
            || str_contains($path, 'admin/2fa')
            || str_contains($path, 'admin/user');
    }

    private function actionLabel(Request $request): string
    {
        return strtoupper($request->method()) . ' ' . $request->path();
    }
}
