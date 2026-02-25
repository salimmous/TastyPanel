<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Theme;
use App\Support\AdminPermissions;
use App\Support\AdminTenantResolver;
use Illuminate\Http\Request;

class ThemeMarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $query = Theme::query()
            ->where('is_active', true)
            ->where('is_marketplace', true)
            ->orderByDesc('is_featured')
            ->orderBy('name');

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('key', 'like', '%'.$search.'%');
            });
        }

        $themes = $query->get();

        return response()->json([
            'data' => $themes,
        ]);
    }

    public function install(Request $request, Theme $theme)
    {
        $user = $request->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }

        $tenantId = AdminTenantResolver::resolveId($request);
        if (! $tenantId) {
            abort(422, 'Tenant required.');
        }

        if (! $theme->is_active || ! $theme->is_marketplace) {
            return response()->json(['message' => 'Theme not available.'], 409);
        }

        $tenant = Tenant::findOrFail($tenantId);
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $tenant->id) {
            abort(403);
        }

        $tenant->theme_id = $theme->id;
        $tenant->save();

        return response()->json([
            'data' => $tenant->load(['theme', 'settings']),
        ]);
    }
}
