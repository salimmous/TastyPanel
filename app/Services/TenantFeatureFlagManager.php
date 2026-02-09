<?php

namespace App\Services;

use App\Models\FeatureFlag;
use App\Models\Tenant;

class TenantFeatureFlagManager
{
    public function upsert(Tenant $tenant, string $key, array $data): FeatureFlag
    {
        return FeatureFlag::updateOrCreate(
            ['tenant_id' => $tenant->id, 'key' => $key, 'environment' => $data['environment'] ?? null],
            [
                'name' => $data['name'] ?? $key,
                'description' => $data['description'] ?? null,
                'enabled' => (bool) ($data['enabled'] ?? false),
                'rollout_percentage' => (int) ($data['rollout_percentage'] ?? 0),
                'created_by' => $data['created_by'] ?? null,
            ]
        );
    }
}
