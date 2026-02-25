<?php

namespace App\Services;

use App\Models\FeatureFlag;
use App\Models\Tenant;

class FeatureFlagService
{
    public function isEnabled(string $key, ?Tenant $tenant = null, ?string $environment = null): bool
    {
        $query = FeatureFlag::where('key', $key);
        if ($tenant) {
            $query->where(function ($q) use ($tenant) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenant->id);
            });
        } else {
            $query->whereNull('tenant_id');
        }
        if ($environment) {
            $query->where(function ($q) use ($environment) {
                $q->whereNull('environment')->orWhere('environment', $environment);
            });
        }

        $flag = $query->orderByDesc('tenant_id')->first();
        if (! $flag || ! $flag->enabled) {
            return false;
        }

        $rollout = (int) $flag->rollout_percentage;
        if ($rollout >= 100) {
            return true;
        }
        if ($rollout <= 0) {
            return false;
        }

        $hashBase = $key.($tenant?->id ?? 'global');
        $bucket = crc32($hashBase) % 100;

        return $bucket < $rollout;
    }
}
