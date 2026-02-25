<?php

namespace App\Support;

use App\Models\User;

class AdminPermissions
{
    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_TENANT_ADMIN = 'tenant-admin';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_WRITER = 'writer';

    public static function role(?User $user): string
    {
        if (! $user) {
            return self::ROLE_WRITER;
        }

        $role = $user->role ?: ($user->is_superadmin ? self::ROLE_SUPERADMIN : self::ROLE_TENANT_ADMIN);

        if (self::isTenantMode() && $role === self::ROLE_SUPERADMIN) {
            return self::ROLE_TENANT_ADMIN;
        }

        return $role;
    }

    public static function isSuperadmin(?User $user): bool
    {
        if (self::isTenantMode()) {
            return false;
        }

        return self::role($user) === self::ROLE_SUPERADMIN;
    }

    public static function canManageTenants(?User $user): bool
    {
        return self::isSuperadmin($user);
    }

    public static function canManageThemes(?User $user): bool
    {
        return self::isSuperadmin($user);
    }

    public static function canManageUsers(?User $user): bool
    {
        $role = self::role($user);

        return in_array($role, [self::ROLE_SUPERADMIN, self::ROLE_TENANT_ADMIN], true);
    }

    public static function canManageTenantInfrastructure(?User $user): bool
    {
        $role = self::role($user);

        return in_array($role, [self::ROLE_SUPERADMIN, self::ROLE_TENANT_ADMIN], true);
    }

    public static function canManageContent(?User $user): bool
    {
        $role = self::role($user);

        return in_array($role, [
            self::ROLE_SUPERADMIN,
            self::ROLE_TENANT_ADMIN,
            self::ROLE_EDITOR,
            self::ROLE_WRITER,
        ], true);
    }

    public static function canReviewContent(?User $user): bool
    {
        $role = self::role($user);

        return in_array($role, [
            self::ROLE_SUPERADMIN,
            self::ROLE_TENANT_ADMIN,
            self::ROLE_EDITOR,
        ], true);
    }

    public static function canPublishContent(?User $user): bool
    {
        $role = self::role($user);

        return in_array($role, [
            self::ROLE_SUPERADMIN,
            self::ROLE_TENANT_ADMIN,
        ], true);
    }

    public static function canDeleteContent(?User $user): bool
    {
        $role = self::role($user);

        return in_array($role, [
            self::ROLE_SUPERADMIN,
            self::ROLE_TENANT_ADMIN,
            self::ROLE_EDITOR,
        ], true);
    }

    private static function isTenantMode(): bool
    {
        return (bool) config('services.tenant_mode.enabled', false);
    }
}
