<?php

namespace App\Support;

use App\Models\User;

/**
 * Platform RBAC (Admin UI)
 *
 * Goal: keep Control Center and sensitive modules restricted while allowing
 * ops/support to use day-to-day tooling (monitoring/incidents) safely.
 *
 * We intentionally keep this separate from the legacy content/tenant roles
 * in AdminPermissions to avoid breaking tenant-mode assumptions.
 */
class PlatformPermissions
{
    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_OPS = 'ops';

    public const ROLE_SUPPORT = 'support';

    public static function role(?User $user): string
    {
        if (! $user) {
            return '';
        }

        if ($user->is_superadmin || $user->role === self::ROLE_SUPERADMIN || (method_exists($user, 'isSuperadmin') && $user->isSuperadmin())) {
            return self::ROLE_SUPERADMIN;
        }

        return (string) ($user->role ?? '');
    }

    public static function isSuperadmin(?User $user): bool
    {
        return self::role($user) === self::ROLE_SUPERADMIN;
    }

    public static function isOps(?User $user): bool
    {
        return self::role($user) === self::ROLE_OPS;
    }

    public static function isSupport(?User $user): bool
    {
        return self::role($user) === self::ROLE_SUPPORT;
    }

    public static function canAccessPlatform(?User $user): bool
    {
        return in_array(self::role($user), [self::ROLE_SUPERADMIN, self::ROLE_OPS, self::ROLE_SUPPORT], true);
    }

    public static function canUseControlCenter(?User $user): bool
    {
        return self::isSuperadmin($user) || self::isOps($user);
    }

    public static function canManageMonitoring(?User $user): bool
    {
        return self::isSuperadmin($user) || self::isOps($user);
    }

    public static function canManageMonitoringRules(?User $user): bool
    {
        return self::isSuperadmin($user) || self::isOps($user);
    }

    public static function canViewDomains(?User $user): bool
    {
        return self::canAccessPlatform($user);
    }

    public static function canManageDomains(?User $user): bool
    {
        return self::isSuperadmin($user) || self::isOps($user);
    }

    public static function canViewDeploy(?User $user): bool
    {
        return self::canAccessPlatform($user);
    }

    public static function canManageDeploy(?User $user): bool
    {
        return self::isSuperadmin($user) || self::isOps($user);
    }

    public static function canViewIncidents(?User $user): bool
    {
        return self::canAccessPlatform($user);
    }

    public static function canAckIncidents(?User $user): bool
    {
        return self::canAccessPlatform($user);
    }

    public static function canResolveIncidents(?User $user): bool
    {
        return self::isSuperadmin($user) || self::isOps($user);
    }

    public static function canManageSecurityCenter(?User $user): bool
    {
        return self::isSuperadmin($user);
    }

    public static function canManageAccessControl(?User $user): bool
    {
        // Users + Roles management
        return self::isSuperadmin($user);
    }
}
