<?php

namespace App\Services;

use App\Models\Article;
use App\Models\PlatformMetric;
use App\Models\Recipe;
use App\Models\Tenant;
use App\Models\TenantTrafficMetric;
use Illuminate\Support\Facades\DB;

class PlatformAnalyticsService
{
    /**
     * Collect and store daily platform metrics
     */
    public function collectDailyMetrics(): PlatformMetric
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Get tenant metrics
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $newTenants = Tenant::whereDate('created_at', $today)->count();
        $churned = Tenant::whereDate('updated_at', $today)
            ->where('status', 'inactive')
            ->count();

        // Get content metrics
        $totalRecipes = Recipe::count();
        $totalArticles = Article::count();
        $newRecipes = Recipe::whereDate('created_at', $today)->count();
        $newArticles = Article::whereDate('created_at', $today)->count();

        // Get traffic metrics (from TenantTrafficMetric)
        $trafficToday = TenantTrafficMetric::where('date', $today)
            ->selectRaw('SUM(requests) as total_requests, SUM(bytes) as total_bytes, SUM(unique_ips) as unique_visitors')
            ->first();

        // Calculate storage
        $totalStorage = DB::table('tenants')
            ->sum('storage_used_bytes') ?? 0;

        return PlatformMetric::updateOrCreate(
            ['date' => $today],
            [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'new_tenants' => $newTenants,
                'churned_tenants' => $churned,
                'total_recipes' => $totalRecipes,
                'total_articles' => $totalArticles,
                'new_recipes' => $newRecipes,
                'new_articles' => $newArticles,
                'total_requests' => $trafficToday->total_requests ?? 0,
                'total_bytes' => $trafficToday->total_bytes ?? 0,
                'unique_visitors' => $trafficToday->unique_visitors ?? 0,
                'total_storage_bytes' => $totalStorage,
            ]
        );
    }

    /**
     * Get dashboard overview
     */
    public function getDashboardOverview(): array
    {
        $today = PlatformMetric::latest();
        $lastWeek = PlatformMetric::forRange(
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        return [
            'current' => $today,
            'trends' => [
                'tenants_growth' => $today?->growthVsPrevious('total_tenants'),
                'recipes_growth' => $today?->growthVsPrevious('total_recipes'),
                'traffic_growth' => $today?->growthVsPrevious('total_requests'),
            ],
            'weekly_series' => $lastWeek,
        ];
    }

    /**
     * Get tenant leaderboard
     */
    public function getTenantLeaderboard(int $limit = 10): array
    {
        // Top by content
        $topByContent = Tenant::withCount(['recipes', 'articles'])
            ->orderByDesc('recipes_count')
            ->limit($limit)
            ->get(['id', 'name', 'recipes_count', 'articles_count']);

        // Top by traffic (last 30 days)
        $topByTraffic = TenantTrafficMetric::where('date', '>=', now()->subDays(30))
            ->selectRaw('tenant_id, SUM(requests) as total_requests')
            ->groupBy('tenant_id')
            ->orderByDesc('total_requests')
            ->limit($limit)
            ->with('tenant:id,name')
            ->get();

        return [
            'by_content' => $topByContent,
            'by_traffic' => $topByTraffic,
        ];
    }

    /**
     * Get content analytics
     */
    public function getContentAnalytics(int $days = 30): array
    {
        $from = now()->subDays($days);

        $recipeSeries = Recipe::where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $articleSeries = Article::where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'recipes' => $recipeSeries,
            'articles' => $articleSeries,
            'totals' => [
                'recipes' => Recipe::count(),
                'articles' => Article::count(),
            ],
        ];
    }

    /**
     * Get traffic analytics
     */
    public function getTrafficAnalytics(int $days = 30): array
    {
        $from = now()->subDays($days)->toDateString();

        $series = TenantTrafficMetric::where('date', '>=', $from)
            ->selectRaw('date, SUM(requests) as requests, SUM(bytes) as bytes, SUM(unique_ips) as visitors')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totals = TenantTrafficMetric::where('date', '>=', $from)
            ->selectRaw('SUM(requests) as requests, SUM(bytes) as bytes, SUM(unique_ips) as visitors')
            ->first();

        return [
            'series' => $series,
            'totals' => $totals,
        ];
    }

    /**
     * Get health summary
     */
    public function getHealthSummary(): array
    {
        return [
            'tenants' => [
                'total' => Tenant::count(),
                'active' => Tenant::where('status', 'active')->count(),
                'inactive' => Tenant::where('status', 'inactive')->count(),
            ],
            'system' => [
                'queue_size' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ],
        ];
    }
}
