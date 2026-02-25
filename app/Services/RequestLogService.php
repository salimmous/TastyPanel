<?php

namespace App\Services;

use App\Models\RequestLog;
use Illuminate\Support\Facades\DB;

class RequestLogService
{
    /**
     * Get logs with filters
     */
    public function getLogs(array $filters, int $perPage = 50)
    {
        $query = RequestLog::with(['user:id,name,email', 'tenant:id,name']);

        // Apply filters
        if (! empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }

        if (! empty($filters['status'])) {
            if ($filters['status'] === 'error') {
                $query->errors();
            } elseif ($filters['status'] === 'success') {
                $query->where('status_code', '<', 400);
            } else {
                $query->where('status_code', $filters['status']);
            }
        }

        if (! empty($filters['path'])) {
            $query->where('path', 'like', '%'.$filters['path'].'%');
        }

        if (! empty($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (! empty($filters['tenant_id'])) {
            $query->forTenant($filters['tenant_id']);
        }

        if (! empty($filters['ip'])) {
            $query->where('ip', $filters['ip']);
        }

        if (! empty($filters['slow'])) {
            $query->slowRequests($filters['slow']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats(int $days = 7): array
    {
        $from = now()->subDays($days);

        return [
            'avg_response_time' => RequestLog::where('created_at', '>=', $from)
                ->avg('response_time_ms'),
            'slow_requests' => RequestLog::where('created_at', '>=', $from)
                ->slowRequests(1000)
                ->count(),
            'total_requests' => RequestLog::where('created_at', '>=', $from)
                ->count(),
            'error_rate' => $this->calculateErrorRate($from),
            'by_status' => $this->getByStatusCode($from),
            'slowest_endpoints' => $this->getSlowestEndpoints($from),
        ];
    }

    /**
     * Get error rate
     */
    protected function calculateErrorRate($from): float
    {
        $total = RequestLog::where('created_at', '>=', $from)->count();
        $errors = RequestLog::where('created_at', '>=', $from)->errors()->count();

        if ($total === 0) {
            return 0;
        }

        return round(($errors / $total) * 100, 2);
    }

    /**
     * Get requests by status code
     */
    protected function getByStatusCode($from): array
    {
        return RequestLog::where('created_at', '>=', $from)
            ->select('status_code', DB::raw('count(*) as count'))
            ->groupBy('status_code')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * Get slowest endpoints
     */
    protected function getSlowestEndpoints($from, int $limit = 10): array
    {
        return RequestLog::where('created_at', '>=', $from)
            ->select('method', 'path', DB::raw('AVG(response_time_ms) as avg_time'), DB::raw('COUNT(*) as count'))
            ->groupBy('method', 'path')
            ->orderByDesc('avg_time')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get recent errors
     */
    public function getRecentErrors(int $limit = 20): array
    {
        return RequestLog::errors()
            ->with(['user:id,name', 'tenant:id,name'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Cleanup old logs
     */
    public function cleanup(int $days = 30): int
    {
        $cutoff = now()->subDays($days);

        return RequestLog::where('created_at', '<', $cutoff)->delete();
    }

    /**
     * Get daily request counts
     */
    public function getDailyStats(int $days = 30): array
    {
        $from = now()->subDays($days)->toDateString();

        return RequestLog::where('created_at', '>=', $from)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors'),
                DB::raw('AVG(response_time_ms) as avg_response_time')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
