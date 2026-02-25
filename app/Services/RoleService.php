<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class RoleService
{
    /**
     * Check if user has permission
     */
    public function hasPermission(User $user, string $permission): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $permissions = $this->getUserPermissions($user);

        return in_array($permission, $permissions);
    }

    /**
     * Check if user has any of the permissions
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all permissions
     */
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has role
     */
    public function hasRole(User $user, string $roleName): bool
    {
        return $user->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(User $user): bool
    {
        return $this->hasRole($user, 'superadmin');
    }

    /**
     * Get all permissions for user
     */
    public function getUserPermissions(User $user): array
    {
        $cacheKey = "user_permissions_{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $roleIds = $user->roles()->pluck('roles.id');

            return Permission::whereHas('roles', function ($q) use ($roleIds) {
                $q->whereIn('roles.id', $roleIds);
            })->pluck('name')->toArray();
        });
    }

    /**
     * Clear user permission cache
     */
    public function clearCache(User $user): void
    {
        Cache::forget("user_permissions_{$user->id}");
    }

    /**
     * Assign role to user
     */
    public function assignRole(User $user, string|int $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail()->id;
        }

        $user->roles()->syncWithoutDetaching([$role]);
        $this->clearCache($user);
    }

    /**
     * Remove role from user
     */
    public function removeRole(User $user, string|int $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail()->id;
        }

        $user->roles()->detach($role);
        $this->clearCache($user);
    }

    /**
     * Sync user roles
     */
    public function syncRoles(User $user, array $roleIds): void
    {
        $user->roles()->sync($roleIds);
        $this->clearCache($user);
    }

    /**
     * Seed default roles and permissions
     */
    public function seedDefaults(): void
    {
        // Create permissions
        foreach (Permission::getDefaults() as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        // Create roles
        foreach (Role::getDefaults() as $roleData) {
            Role::firstOrCreate(['name' => $roleData['name']], $roleData);
        }

        // Assign all permissions to superadmin
        $superadmin = Role::where('name', 'superadmin')->first();
        if ($superadmin) {
            $superadmin->permissions()->sync(Permission::pluck('id'));
        }

        // Assign most permissions to admin (except system)
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->permissions()->sync(
                Permission::where('group', '!=', 'system')->pluck('id')
            );
        }
    }
}
