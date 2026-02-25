<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformMetric;
use App\Services\PlatformAnalyticsService;
use Illuminate\Http\Request;

class PlatformAnalyticsController extends Controller
{
    public function __construct(
        protected PlatformAnalyticsService $analytics
    ) {}

    /**
     * Dashboard overview
     */
    public function dashboard(Request $request)
    {
        $overview = $this->analytics->getDashboardOverview();
        $health = $this->analytics->getHealthSummary();

        return response()->json([
            'data' => [
                'overview' => $overview,
                'health' => $health,
            ],
        ]);
    }

    /**
     * Tenant leaderboard
     */
    public function leaderboard(Request $request)
    {
        $limit = (int) $request->get('limit', 10);
        $limit = max(5, min(50, $limit));

        $leaderboard = $this->analytics->getTenantLeaderboard($limit);

        return response()->json([
            'data' => $leaderboard,
        ]);
    }

    /**
     * Content analytics
     */
    public function content(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $days = max(7, min(365, $days));

        $content = $this->analytics->getContentAnalytics($days);

        return response()->json([
            'data' => $content,
        ]);
    }

    /**
     * Traffic analytics
     */
    public function traffic(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $days = max(7, min(365, $days));

        $traffic = $this->analytics->getTrafficAnalytics($days);

        return response()->json([
            'data' => $traffic,
        ]);
    }

    /**
     * Metrics history
     */
    public function history(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $days = max(7, min(365, $days));

        $metrics = PlatformMetric::forRange(
            now()->subDays($days)->toDateString(),
            now()->toDateString()
        );

        return response()->json([
            'data' => $metrics,
        ]);
    }

    /**
     * Collect metrics (manual trigger)
     */
    public function collect(Request $request)
    {
        $metric = $this->analytics->collectDailyMetrics();

        return response()->json([
            'data' => $metric,
            'message' => 'Metrics collected successfully',
        ]);
    }
}
