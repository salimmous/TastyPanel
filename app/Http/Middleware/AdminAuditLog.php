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
        $payload = $this->redact($payload);

        try {
            $statusCode = method_exists($response, 'getStatusCode') ? (int) $response->getStatusCode() : 200;
            $success = $statusCode < 400;

            AuditLog::create([
                'user_id' => $user?->id,
                'tenant_id' => $tenantId,
                'action' => $this->actionLabel($request),
                'resource_type' => null,
                'resource_id' => null,
                'description' => $request->method().' '.$request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'status' => $success ? 'success' : 'failed',
                'error_message' => $success ? null : ('HTTP '.$statusCode),
                'new_values' => [
                    'status_code' => $statusCode,
                    'payload' => $payload,
                    'query' => $request->query(),
                ],
                'created_at' => now(),
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
        return strtoupper($request->method()).' '.$request->path();
    }

    private function redact(array $payload): array
    {
        $sensitiveKeys = [
            'password',
            'current_password',
            'two_factor_code',
            'secret',
            'secret_value',
            'value',
            'token',
            'access_token',
            'refresh_token',
            'api_key',
            'apiKey',
            'client_secret',
            'sso_client_secret',
            'saml_idp_x509',
            'private_key',
        ];

        $walk = function ($value) use (&$walk, $sensitiveKeys) {
            if (! is_array($value)) {
                return $value;
            }
            $out = [];
            foreach ($value as $k => $v) {
                $key = is_string($k) ? $k : '';
                if ($key !== '' && in_array($key, $sensitiveKeys, true)) {
                    $out[$k] = '[REDACTED]';

                    continue;
                }
                // Also redact common patterns (covers snake/camel variations).
                if ($key !== '' && preg_match('/(password|secret|token|client_secret|api[_-]?key|private[_-]?key|x509)/i', $key)) {
                    $out[$k] = '[REDACTED]';

                    continue;
                }
                $out[$k] = $walk($v);
            }

            return $out;
        };

        return $walk($payload);
    }
}
