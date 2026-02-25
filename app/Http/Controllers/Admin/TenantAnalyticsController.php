<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Recipe;
use App\Models\Tenant;
use App\Models\TenantTrafficMetric;
use App\Services\RealtimeAnalyticsService;
use App\Services\TenantCostService;
use App\Services\TenantLimitService;
use App\Services\TenantObservabilityService;
use App\Services\TenantStorageService;
use App\Support\AdminEnvironmentResolver;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantAnalyticsController extends Controller
{
    public function show(
        Request $request,
        Tenant $tenant,
        TenantStorageService $storage,
        TenantLimitService $limits,
        TenantCostService $costs,
        TenantObservabilityService $observability
    ) {
        $user = $request->user();
        if (! AdminPermissions::isSuperadmin($user) && (int) $user?->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        $days = (int) $request->get('days', 30);
        $days = max(1, min($days, 120));
        $from = now()->subDays($days);

        $traffic = TenantTrafficMetric::where('tenant_id', $tenant->id)
            ->where('date', '>=', $from->toDateString())
            ->orderBy('date')
            ->get();

        $environment = AdminEnvironmentResolver::resolve($request);

        $articles = $this->contentSeries(Article::query(), $tenant->id, $environment, $from);
        $recipes = $this->contentSeries(Recipe::query(), $tenant->id, $environment, $from);

        $totals = [
            'articles' => Article::where('tenant_id', $tenant->id)->where('environment', $environment)->count(),
            'recipes' => Recipe::where('tenant_id', $tenant->id)->where('environment', $environment)->count(),
        ];

        $usage = $storage->usage($tenant);
        $limitBytes = $limits->storageLimitBytes($tenant);
        $percent = null;
        if ($limitBytes && $limitBytes > 0) {
            $percent = round(($usage['bytes'] / $limitBytes) * 100, 2);
        }

        $cost = $costs->estimate($tenant);
        $observabilityData = $observability->summary($tenant, max(24, $days * 24));

        return response()->json([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'traffic' => $traffic,
            'content_growth' => [
                'articles' => $articles,
                'recipes' => $recipes,
            ],
            'totals' => $totals,
            'storage' => array_merge($usage, [
                'limit_bytes' => $limitBytes,
                'usage_percent' => $percent,
            ]),
            'costs' => $cost,
            'observability' => $observabilityData,
        ]);
    }

    public function realtime(Request $request, Tenant $tenant, RealtimeAnalyticsService $realtime)
    {
        $user = $request->user();
        if (! AdminPermissions::isSuperadmin($user) && (int) $user?->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        $environment = AdminEnvironmentResolver::resolve($request);
        $lines = (int) $request->get('lines', 1200);
        $lines = max(200, min($lines, 5000));

        $snapshot = $realtime->snapshotForTenant($tenant, $environment, $lines);

        return response()->json($snapshot);
    }

    private function contentSeries($query, int $tenantId, string $environment, $from)
    {
        return $query
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->where('created_at', '>=', $from)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }
}
