<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminTenantResolver;
use App\Support\AdminPermissions;
use App\Services\TenantLimitService;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!AdminPermissions::canManageUsers($request->user())) {
            abort(403);
        }
        $query = User::query();
        $tenantId = AdminTenantResolver::resolveId($request);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        return response()->json($users);
    }

    public function store(Request $request)
    {
        if (!AdminPermissions::canManageUsers($request->user())) {
            abort(403);
        }
        $currentUser = $request->user();
        $isSuperadmin = AdminPermissions::isSuperadmin($currentUser);
        $tenantMode = (bool) config('services.tenant_mode.enabled', false);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'tenant_id' => 'nullable|exists:tenants,id',
            'is_superadmin' => 'nullable|boolean',
            'role' => 'nullable|string|in:superadmin,tenant-admin,editor,writer',
            'two_factor_enabled' => 'nullable|boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        if (!$isSuperadmin) {
            $validated['tenant_id'] = $currentUser?->tenant_id;
            $allowedRoles = $tenantMode ? ['tenant-admin', 'editor', 'writer'] : ['editor', 'writer'];
            $requestedRole = $validated['role'] ?? null;
            $validated['role'] = in_array($requestedRole, $allowedRoles, true)
                ? $requestedRole
                : ($tenantMode ? 'tenant-admin' : 'writer');
            $validated['is_superadmin'] = false;
            $validated['two_factor_enabled'] = false;
        } else {
            $validated['role'] = $validated['role'] ?? 'tenant-admin';
            $validated['is_superadmin'] = ($validated['role'] === 'superadmin');
        }

        if (!empty($validated['tenant_id'])) {
            $tenant = Tenant::find($validated['tenant_id']);
            if ($tenant) {
                $limits = app(TenantLimitService::class);
                if (!$limits->canCreateUser($tenant)) {
                    return response()->json([
                        'message' => 'User limit reached for this tenant.',
                    ], 422);
                }
            }
        }

        $user = User::create($validated);
        return response()->json($user, 201);
    }

    public function show($id)
    {
        if (!AdminPermissions::canManageUsers(request()->user())) {
            abort(403);
        }
        $tenantId = AdminTenantResolver::resolveId(request());
        $user = User::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        if (!AdminPermissions::canManageUsers($request->user())) {
            abort(403);
        }
        $tenantId = AdminTenantResolver::resolveId($request);
        $user = User::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'tenant_id' => 'nullable|exists:tenants,id',
            'is_superadmin' => 'nullable|boolean',
            'role' => 'nullable|string|in:superadmin,tenant-admin,editor,writer',
            'two_factor_enabled' => 'nullable|boolean',
        ]);

        $currentUser = $request->user();
        if (!AdminPermissions::isSuperadmin($currentUser)) {
            $validated['tenant_id'] = $currentUser?->tenant_id;
            $tenantMode = (bool) config('services.tenant_mode.enabled', false);
            $allowedRoles = $tenantMode ? ['tenant-admin', 'editor', 'writer'] : ['editor', 'writer'];
            $requestedRole = $validated['role'] ?? null;
            $validated['role'] = in_array($requestedRole, $allowedRoles, true)
                ? $requestedRole
                : $user->role;
            $validated['is_superadmin'] = false;
            $validated['two_factor_enabled'] = $user->two_factor_enabled;
        } else {
            $nextRole = $validated['role'] ?? $user->role ?? 'tenant-admin';
            $validated['role'] = $nextRole;
            if ($nextRole === 'superadmin') {
                $validated['is_superadmin'] = true;
            } elseif (array_key_exists('role', $validated)) {
                $validated['is_superadmin'] = false;
            }
        }

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        return response()->json($user);
    }

    public function destroy($id)
    {
        if (!AdminPermissions::canManageUsers(request()->user())) {
            abort(403);
        }
        $tenantId = AdminTenantResolver::resolveId(request());
        $user = User::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
