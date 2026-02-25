<?php

namespace App\Http\Middleware;

use App\Services\PerformanceMonitorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $monitor = app(PerformanceMonitorService::class);
        $monitor->start($request);

        $response = $next($request);

        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds

        // Add header with response time
        $response->headers->set('X-Response-Time', round($duration, 2).'ms');

        // Add cache status if available
        if ($cacheStatus = $request->attributes->get('cache_status')) {
            $response->headers->set('X-Cache-Status', $cacheStatus);
        }

        // Log slow requests (> 500ms)
        if ($duration > 500) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ]);
        }

        // Log very slow requests (> 2s)
        if ($duration > 2000) {
            Log::error('Very slow request', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'queries' => \DB::getQueryLog(),
            ]);
        }

        // Persist performance metrics for tenant analytics and anomaly detection.
        $monitor->stop($request, $response);

        return $response;
    }
}
