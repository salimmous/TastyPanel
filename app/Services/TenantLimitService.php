<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Recipe;
use App\Models\Tenant;
use App\Models\User;

class TenantLimitService
{
    public function limitsFor(Tenant $tenant): array
    {
        $subscription = $tenant->activeSubscription()->with('plan')->first();

        return $subscription?->plan?->limits ?? [];
    }

    public function limitValue(Tenant $tenant, string $key, int $default = 0): int
    {
        $limits = $this->limitsFor($tenant);
        if (! array_key_exists($key, $limits)) {
            return $default;
        }

        return (int) $limits[$key];
    }

    public function canCreatePost(Tenant $tenant, string $environment = 'production'): bool
    {
        $limit = $this->limitValue($tenant, 'max_posts', 0);
        if ($limit <= 0) {
            return true;
        }

        $count = Article::where('tenant_id', $tenant->id)
            ->where('environment', $environment)
            ->count()
            + Recipe::where('tenant_id', $tenant->id)
                ->where('environment', $environment)
                ->count();

        return $count < $limit;
    }

    public function canCreateUser(Tenant $tenant): bool
    {
        $limit = $this->limitValue($tenant, 'max_users', 0);
        if ($limit <= 0) {
            return true;
        }

        $count = User::where('tenant_id', $tenant->id)->count();

        return $count < $limit;
    }

    public function storageLimitBytes(Tenant $tenant): ?int
    {
        $limitGb = $this->limitValue($tenant, 'storage_gb', 0);
        if ($limitGb <= 0) {
            return null;
        }

        return $limitGb * 1024 * 1024 * 1024;
    }
}
