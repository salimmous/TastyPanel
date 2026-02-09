<?php

namespace App\Http\Middleware;

use App\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestLogger
{
    /**
     * Paths to exclude from logging
     */
    protected array $excludePaths = [
        'api/health',
        '_debugbar',
        'telescope',
        'horizon',
    ];

    /**
     * Headers to exclude from logging
     */
    protected array $excludeHeaders = [
        'authorization',
        'cookie',
        'x-csrf-token',
    ];

    /**
     * Body fields to mask
     */
    protected array $maskFields = [
        'password',
        'password_confirmation',
        'secret',
        'token',
        'api_key',
    ];

    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        // Log after response
        $this->logRequest($request, $response, $startTime);

        return $response;
    }

    protected function logRequest(Request $request, $response, float $startTime): void
    {
        // Skip excluded paths
        if ($this->shouldExclude($request)) {
            return;
        }

        $endTime = microtime(true);
        $responseTime = (int) (($endTime - $startTime) * 1000);

        try {
            RequestLog::create([
                'method' => $request->method(),
                'path' => $request->path(),
                'full_url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => Auth::id(),
                'tenant_id' => $this->getTenantId($request),
                'headers' => $this->filterHeaders($request->headers->all()),
                'query_params' => $request->query() ?: null,
                'body' => $this->filterBody($request->except(['password', 'password_confirmation'])),
                'status_code' => $response->getStatusCode(),
                'response_time_ms' => $responseTime,
                'response_size' => strlen($response->getContent()),
                'error_message' => $this->getErrorMessage($response),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Don't let logging errors break the request
            report($e);
        }
    }

    protected function shouldExclude(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->excludePaths as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return true;
            }
        }

        return false;
    }

    protected function filterHeaders(array $headers): array
    {
        $filtered = [];

        foreach ($headers as $key => $value) {
            $key = strtolower($key);
            if (!in_array($key, $this->excludeHeaders)) {
                $filtered[$key] = is_array($value) ? $value[0] : $value;
            }
        }

        return $filtered;
    }

    protected function filterBody(?array $body): ?array
    {
        if (!$body) {
            return null;
        }

        foreach ($this->maskFields as $field) {
            if (isset($body[$field])) {
                $body[$field] = '***MASKED***';
            }
        }

        return $body;
    }

    protected function getTenantId(Request $request): ?int
    {
        // Try to get from route parameter
        if ($tenant = $request->route('tenant')) {
            return is_object($tenant) ? $tenant->id : (int) $tenant;
        }

        // Try to get from request
        return $request->input('tenant_id');
    }

    protected function getErrorMessage($response): ?string
    {
        if ($response->getStatusCode() >= 400) {
            $content = json_decode($response->getContent(), true);
            return $content['message'] ?? $content['error'] ?? null;
        }

        return null;
    }
}
