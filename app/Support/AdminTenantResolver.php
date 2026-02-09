<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Support\AdminPermissions;

class AdminTenantResolver
{
    public static function resolveId(Request $request): ?int
    {
        $tenantModeId = self::lockedTenantId();
        if ($tenantModeId !== null) {
            return $tenantModeId;
        }
        $user = $request->user();

        if ($user && !AdminPermissions::isSuperadmin($user)) {
            return $user->tenant_id;
        }

        if ($request->query('scope') === 'all') {
            return null;
        }

        $header = $request->header('X-Tenant-ID');
        if ($header) {
            if ($header === 'all' || $header === '0') {
                return null;
            }
            return (int) $header;
        }

        $tenantId = $request->input('tenant_id') ?? $request->query('tenant_id');
        if ($tenantId) {
            return (int) $tenantId;
        }

        return Tenant::query()->value('id');
    }

    public static function enforceTenantId(Request $request, ?int $requestedId): ?int
    {
        $tenantModeId = self::lockedTenantId();
        if ($tenantModeId !== null) {
            return $tenantModeId;
        }
        $user = $request->user();
        if ($user && !AdminPermissions::isSuperadmin($user)) {
            return $user->tenant_id;
        }

        return $requestedId;
    }

    private static function lockedTenantId(): ?int
    {
        if (!config('services.tenant_mode.enabled', false)) {
            return null;
        }

        $locked = config('services.tenant_mode.locked_tenant_id');
        if ($locked !== null && $locked !== '') {
            return (int) $locked;
        }

        return Tenant::query()->value('id');
    }
}
