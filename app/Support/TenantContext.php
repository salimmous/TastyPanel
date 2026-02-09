<?php

namespace App\Support;

use App\Models\Tenant;

class TenantContext
{
    private static ?Tenant $tenant = null;
    private static string $environment = 'production';

    public static function set(?Tenant $tenant, string $environment = 'production'): void
    {
        self::$tenant = $tenant;
        self::$environment = $environment ?: 'production';
    }

    public static function get(): ?Tenant
    {
        return self::$tenant;
    }

    public static function id(): ?int
    {
        return self::$tenant?->id;
    }

    public static function environment(): string
    {
        return self::$environment;
    }
}
