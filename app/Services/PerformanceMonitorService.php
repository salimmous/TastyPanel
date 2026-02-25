<?php

namespace App\Services;

use App\Models\PerformanceMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitorService
{
    protected float $startTime;

    protected int $startMemory;

    protected bool $enabled = true;

    protected bool $recording = false;

    /**
     * Start monitoring
     */
    public function start(Request $request): void
    {
        $this->enabled = (bool) config('monitoring.performance.enabled', true);
        if (! $this->enabled || $this->shouldSkip($request)) {
            $this->recording = false;

            return;
        }

        $this->recording = true;
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();

        // Enable query logging
        DB::flushQueryLog();
        DB::enableQueryLog();
    }

    /**
     * Stop monitoring and save metrics
     */
    public function stop(Request $request, Response $response): ?PerformanceMetric
    {
        if (! $this->recording || ! isset($this->startTime)) {
            return null;
        }

        $responseTime = (microtime(true) - $this->startTime) * 1000; // milliseconds
        $memoryUsage = memory_get_usage() - $this->startMemory;

        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $queryTime = array_sum(array_column($queries, 'time'));

        $cacheStatus = (string) $request->attributes->get('cache_status', '');
        $cacheHits = $cacheStatus === 'HIT' ? 1 : 0;
        $cacheMisses = $cacheStatus === 'MISS' ? 1 : 0;

        $metric = PerformanceMetric::create([
            'tenant_id' => $this->getTenantId(),
            'endpoint' => '/'.ltrim($request->path(), '/'),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'response_time' => $responseTime,
            'memory_usage' => $memoryUsage,
            'query_count' => $queryCount,
            'query_time' => $queryTime,
            'cache_hits' => $cacheHits,
            'cache_misses' => $cacheMisses,
            'ip_address' => $request->ip(),
            'user_id' => auth()->id(),
        ]);

        $slowThreshold = (float) config('monitoring.performance.slow_threshold', 1000);
        if ($responseTime >= $slowThreshold) {
            Log::warning('Slow request captured in performance metrics', [
                'tenant_id' => $metric->tenant_id,
                'endpoint' => $metric->endpoint,
                'response_time_ms' => round($responseTime, 2),
                'status_code' => $metric->status_code,
            ]);
        }

        return $metric;
    }

    /**
     * Get performance stats
     */
    public function getStats(int $hours = 24, ?int $tenantId = null): array
    {
        $since = now()->subHours($hours);

        $query = PerformanceMetric::where('created_at', '>', $since);
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        $metrics = $query->get();

        if ($metrics->isEmpty()) {
            return [
                'total_requests' => 0,
                'avg_response_time' => 0,
                'p95_response_time' => 0,
                'slow_requests' => 0,
                'avg_memory' => 0,
                'avg_queries' => 0,
                'error_rate_5xx' => 0,
            ];
        }

        $responses = $metrics->pluck('response_time')->sort()->values()->all();
        $error5xx = $metrics->filter(fn ($item) => (int) $item->status_code >= 500)->count();

        return [
            'total_requests' => $metrics->count(),
            'avg_response_time' => round($metrics->avg('response_time'), 2),
            'p95_response_time' => $this->percentile($responses, 95),
            'slow_requests' => $metrics->where('is_slow', true)->count(),
            'avg_memory' => round($metrics->avg('memory_usage'), 0),
            'avg_queries' => round($metrics->avg('query_count'), 2),
            'max_response_time' => round($metrics->max('response_time'), 2),
            'error_rate_5xx' => round(($error5xx / max(1, $metrics->count())) * 100, 2),
        ];
    }

    /**
     * Get slowest endpoints
     */
    public function getSlowestEndpoints(int $limit = 10, ?int $tenantId = null): array
    {
        $query = PerformanceMetric::select('endpoint', 'method')
            ->selectRaw('AVG(response_time) as avg_response_time')
            ->selectRaw('COUNT(*) as request_count')
            ->where('created_at', '>', now()->subDays(7));

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query
            ->groupBy('endpoint', 'method')
            ->orderByDesc('avg_response_time')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Clean old metrics
     */
    public function cleanup(int $days = 30): int
    {
        return PerformanceMetric::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Get current tenant ID
     */
    protected function getTenantId(): ?int
    {
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }

        if (class_exists('\App\Support\TenantContext')) {
            return \App\Support\TenantContext::id();
        }

        return null;
    }

    private function percentile(array $values, int $percent): float
    {
        if ($values === []) {
            return 0.0;
        }

        $percent = max(1, min(100, $percent));
        $index = (int) ceil(($percent / 100) * count($values)) - 1;
        $index = max(0, min($index, count($values) - 1));

        return round((float) $values[$index], 2);
    }

    private function shouldSkip(Request $request): bool
    {
        $path = '/'.ltrim($request->path(), '/');
        if ($path === '/up' || str_starts_with($path, '/health')) {
            return true;
        }

        if ($request->isMethod('OPTIONS')) {
            return true;
        }

        return false;
    }
}
