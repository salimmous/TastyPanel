<?php

namespace App\Services;

use App\Models\ErrorLog;
use App\Models\PerformanceMetric;
use App\Models\Tenant;

class TenantObservabilityService
{
    public function summary(Tenant $tenant, int $hours = 24): array
    {
        $hours = max(1, min($hours, 24 * 30));
        $since = now()->subHours($hours);
        $previousSince = now()->subHours($hours * 2);

        $metricsQuery = PerformanceMetric::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $since);

        $totalRequests = (clone $metricsQuery)->count();
        $avgResponse = (float) ((clone $metricsQuery)->avg('response_time') ?? 0);
        $responses = (clone $metricsQuery)->pluck('response_time')->sort()->values()->all();
        $p95Response = $this->percentile($responses, 95);
        $slowRequests = (clone $metricsQuery)->where('is_slow', true)->count();
        $errors5xx = (clone $metricsQuery)->where('status_code', '>=', 500)->count();
        $errorRate5xx = $totalRequests > 0 ? round(($errors5xx / $totalRequests) * 100, 2) : 0.0;

        $prevAvgResponse = (float) (PerformanceMetric::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$previousSince, $since])
            ->avg('response_time') ?? 0);

        $errorQuery = ErrorLog::where('tenant_id', $tenant->id)->where('created_at', '>=', $since);
        $errorsTotal = (clone $errorQuery)->count();
        $errorsCritical = (clone $errorQuery)->where('level', 'critical')->count();
        $errorsUnresolved = (clone $errorQuery)->where('is_resolved', false)->count();
        $errorsUnresolvedCritical = (clone $errorQuery)
            ->where('is_resolved', false)
            ->where('level', 'critical')
            ->count();

        $slowEndpoints = PerformanceMetric::select('endpoint', 'method')
            ->selectRaw('AVG(response_time) as avg_response_time')
            ->selectRaw('COUNT(*) as request_count')
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $since)
            ->groupBy('endpoint', 'method')
            ->orderByDesc('avg_response_time')
            ->limit(5)
            ->get()
            ->toArray();

        $anomalies = [];
        $slowThreshold = (float) config('monitoring.performance.slow_threshold', 1000);
        if ($totalRequests >= 50 && $errorRate5xx >= 3.0) {
            $anomalies[] = [
                'level' => 'critical',
                'code' => 'error_rate_spike',
                'message' => "5xx rate is {$errorRate5xx}% in the last {$hours}h.",
            ];
        }
        if ($p95Response >= ($slowThreshold * 1.5) && $totalRequests >= 30) {
            $anomalies[] = [
                'level' => 'warning',
                'code' => 'latency_spike',
                'message' => "P95 response time is {$p95Response}ms.",
            ];
        }
        if ($prevAvgResponse > 0 && $avgResponse > ($prevAvgResponse * 1.5) && $totalRequests >= 30) {
            $anomalies[] = [
                'level' => 'warning',
                'code' => 'latency_regression',
                'message' => 'Average response time regressed by more than 50% vs previous window.',
            ];
        }
        if ($errorsUnresolvedCritical > 0) {
            $anomalies[] = [
                'level' => 'critical',
                'code' => 'critical_errors_open',
                'message' => "{$errorsUnresolvedCritical} unresolved critical errors are open.",
            ];
        }

        return [
            'status' => $this->statusFromAnomalies($anomalies),
            'window_hours' => $hours,
            'performance' => [
                'total_requests' => $totalRequests,
                'avg_response_time_ms' => round($avgResponse, 2),
                'p95_response_time_ms' => $p95Response,
                'slow_requests' => $slowRequests,
                'errors_5xx' => $errors5xx,
                'error_rate_5xx_percent' => $errorRate5xx,
                'previous_avg_response_time_ms' => round($prevAvgResponse, 2),
                'slow_endpoints' => $slowEndpoints,
            ],
            'errors' => [
                'total' => $errorsTotal,
                'critical' => $errorsCritical,
                'unresolved' => $errorsUnresolved,
                'unresolved_critical' => $errorsUnresolvedCritical,
            ],
            'anomalies' => $anomalies,
        ];
    }

    private function statusFromAnomalies(array $anomalies): string
    {
        foreach ($anomalies as $item) {
            if (($item['level'] ?? '') === 'critical') {
                return 'critical';
            }
        }
        foreach ($anomalies as $item) {
            if (($item['level'] ?? '') === 'warning') {
                return 'degraded';
            }
        }
        return 'healthy';
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
}

