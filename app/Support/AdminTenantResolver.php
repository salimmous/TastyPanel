<?php

namespace App\Support;

use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class AdminTenantResolver
{
    public static function resolveId(Request $request): ?int
    {
        $tenantModeId = self::lockedTenantId();
        if ($tenantModeId !== null) {
            return $tenantModeId;
        }
        $user = $request->user();

        if ($user && ! AdminPermissions::isSuperadmin($user)) {
            return $user->tenant_id;
        }

        if ($request->query('scope') === 'all') {
            return null;
        }

        $routeTenantId = self::resolveFromRoute($request);
        if ($routeTenantId !== null) {
            return $routeTenantId;
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
        if ($user && ! AdminPermissions::isSuperadmin($user)) {
            return $user->tenant_id;
        }

        return $requestedId;
    }

    private static function lockedTenantId(): ?int
    {
        if (! config('services.tenant_mode.enabled', false)) {
            return null;
        }

        $locked = config('services.tenant_mode.locked_tenant_id');
        if ($locked !== null && $locked !== '') {
            return (int) $locked;
        }

        return Tenant::query()->value('id');
    }

    private static function resolveFromRoute(Request $request): ?int
    {
        $route = $request->route();
        if (! $route) {
            return null;
        }

        $params = $route->parameters();

        // Common: /api/admin/tenants/{tenant}
        if (array_key_exists('tenant', $params)) {
            $tenant = $params['tenant'];
            if ($tenant instanceof Tenant) {
                return (int) $tenant->id;
            }
            if (is_numeric($tenant)) {
                return (int) $tenant;
            }
        }

        // Common: /api/admin/domains/{domain}
        if (array_key_exists('domain', $params)) {
            $domain = $params['domain'];
            if ($domain instanceof Domain) {
                return (int) $domain->tenant_id;
            }
            if (is_numeric($domain)) {
                return (int) (Domain::query()->whereKey((int) $domain)->value('tenant_id') ?: 0) ?: null;
            }
        }

        // Common: /api/admin/users/{user}
        if (array_key_exists('user', $params)) {
            $u = $params['user'];
            if ($u instanceof User) {
                return $u->tenant_id ? (int) $u->tenant_id : null;
            }
            if (is_numeric($u)) {
                return (int) (User::query()->whereKey((int) $u)->value('tenant_id') ?: 0) ?: null;
            }
        }

        // Platform web routes: /platform/tenants/{id}
        if (array_key_exists('id', $params) && is_numeric($params['id'])) {
            $path = $request->path();
            if (str_contains($path, 'tenants/')) {
                return (int) $params['id'];
            }
        }

        return null;
    }
}
