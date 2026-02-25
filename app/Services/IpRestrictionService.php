<?php

namespace App\Services;

use App\Models\IpRestriction;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class IpRestrictionService
{
    protected int $maxFailedAttempts = 5;

    protected int $banDurationMinutes = 30;

    /**
     * Check if IP is allowed
     */
    public function isAllowed(string $ip, ?Tenant $tenant = null): bool
    {
        // Check blacklist first
        if ($this->isBlacklisted($ip, $tenant)) {
            return false;
        }

        // If whitelist exists, check it
        if ($this->hasWhitelist($tenant)) {
            return $this->isWhitelisted($ip, $tenant);
        }

        return true;
    }

    /**
     * Check if IP is blacklisted
     */
    public function isBlacklisted(string $ip, ?Tenant $tenant = null): bool
    {
        $query = IpRestriction::where('type', 'blacklist')
            ->where(function ($q) {
                $q->where('is_permanent', true)
                    ->orWhere('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            });

        if ($tenant) {
            $query->where(function ($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id)
                    ->orWhereNull('tenant_id');
            });
        } else {
            $query->whereNull('tenant_id');
        }

        $restrictions = $query->get();

        foreach ($restrictions as $restriction) {
            if ($this->ipMatches($ip, $restriction->ip_address)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is whitelisted
     */
    public function isWhitelisted(string $ip, ?Tenant $tenant = null): bool
    {
        $query = IpRestriction::where('type', 'whitelist');

        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        } else {
            $query->whereNull('tenant_id');
        }

        $restrictions = $query->get();

        foreach ($restrictions as $restriction) {
            if ($this->ipMatches($ip, $restriction->ip_address)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if tenant has whitelist
     */
    public function hasWhitelist(?Tenant $tenant = null): bool
    {
        $query = IpRestriction::where('type', 'whitelist');

        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        } else {
            $query->whereNull('tenant_id');
        }

        return $query->exists();
    }

    /**
     * Add IP to blacklist
     */
    public function blacklist(
        string $ip,
        ?Tenant $tenant = null,
        ?string $reason = null,
        ?int $minutes = null,
        bool $permanent = false
    ): IpRestriction {
        return IpRestriction::create([
            'tenant_id' => $tenant?->id,
            'ip_address' => $ip,
            'type' => 'blacklist',
            'reason' => $reason,
            'is_permanent' => $permanent,
            'expires_at' => $permanent ? null : ($minutes ? now()->addMinutes($minutes) : now()->addMinutes($this->banDurationMinutes)),
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Add IP to whitelist
     */
    public function whitelist(
        string $ip,
        ?Tenant $tenant = null,
        ?string $reason = null
    ): IpRestriction {
        return IpRestriction::create([
            'tenant_id' => $tenant?->id,
            'ip_address' => $ip,
            'type' => 'whitelist',
            'reason' => $reason,
            'is_permanent' => true,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Remove IP restriction
     */
    public function remove(int $id): bool
    {
        return IpRestriction::where('id', $id)->delete();
    }

    /**
     * Track failed login attempt
     */
    public function trackFailedAttempt(string $ip, ?Tenant $tenant = null): void
    {
        $key = "failed_attempts:{$ip}".($tenant ? ":{$tenant->id}" : '');
        $attempts = Cache::get($key, 0) + 1;

        Cache::put($key, $attempts, now()->addHour());

        // Auto-ban after max attempts
        if ($attempts >= $this->maxFailedAttempts) {
            $this->autoBan($ip, $tenant, $attempts);
        }
    }

    /**
     * Auto-ban IP after failed attempts
     */
    protected function autoBan(string $ip, ?Tenant $tenant, int $attempts): IpRestriction
    {
        // Check if already banned
        $existing = IpRestriction::where('ip_address', $ip)
            ->where('type', 'blacklist')
            ->where('is_auto_ban', true)
            ->where(function ($q) {
                $q->where('expires_at', '>', now())
                    ->orWhere('is_permanent', true);
            })
            ->first();

        if ($existing) {
            $existing->increment('failed_attempts');

            return $existing;
        }

        return IpRestriction::create([
            'tenant_id' => $tenant?->id,
            'ip_address' => $ip,
            'type' => 'blacklist',
            'reason' => "Auto-banned after {$attempts} failed login attempts",
            'is_auto_ban' => true,
            'failed_attempts' => $attempts,
            'expires_at' => now()->addMinutes($this->banDurationMinutes),
        ]);
    }

    /**
     * Clear failed attempts for IP
     */
    public function clearFailedAttempts(string $ip, ?Tenant $tenant = null): void
    {
        $key = "failed_attempts:{$ip}".($tenant ? ":{$tenant->id}" : '');
        Cache::forget($key);
    }

    /**
     * Clean up expired bans
     */
    public function cleanupExpired(): int
    {
        return IpRestriction::where('expires_at', '<', now())
            ->where('is_permanent', false)
            ->delete();
    }

    /**
     * Check if IP matches pattern (supports CIDR)
     */
    protected function ipMatches(string $ip, string $pattern): bool
    {
        // Exact match
        if ($ip === $pattern) {
            return true;
        }

        // CIDR notation
        if (strpos($pattern, '/') !== false) {
            return $this->ipInRange($ip, $pattern);
        }

        // Wildcard (e.g., 192.168.1.*)
        if (strpos($pattern, '*') !== false) {
            $regex = str_replace(['*', '.'], ['.*', '\\.'], $pattern);

            return preg_match("/^{$regex}$/", $ip) === 1;
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $mask] = explode('/', $range);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int) $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
