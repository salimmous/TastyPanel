<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantTrafficMetric;
use Carbon\Carbon;

class TenantCostService
{
    /**
     * Compute rough monthly cost based on bandwidth + storage.
     */
    public function estimate(Tenant $tenant, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?: now()->copy()->firstOfMonth();
        $to = $to ?: now();

        $bandwidthBytes = TenantTrafficMetric::where('tenant_id', $tenant->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum('bytes');

        $storageBytes = app(TenantStorageService::class)->usage($tenant)['bytes'] ?? 0;

        $rates = config('services.platform.cost_rates', [
            'bandwidth_per_gb' => 0.09,
            'storage_per_gb' => 0.03,
        ]);

        $bwGb = $bandwidthBytes / (1024 ** 3);
        $storageGb = $storageBytes / (1024 ** 3);

        $bandwidthCost = round($bwGb * $rates['bandwidth_per_gb'], 2);
        $storageCost = round($storageGb * $rates['storage_per_gb'], 2);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'bandwidth_gb' => round($bwGb, 2),
            'storage_gb' => round($storageGb, 2),
            'bandwidth_cost' => $bandwidthCost,
            'storage_cost' => $storageCost,
            'total_cost' => round($bandwidthCost + $storageCost, 2),
        ];
    }
}
