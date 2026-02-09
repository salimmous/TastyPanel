<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpRestriction extends Model
{
    protected $fillable = [
        'tenant_id',
        'ip_address',
        'type',
        'reason',
        'notes',
        'is_auto_ban',
        'failed_attempts',
        'expires_at',
        'is_permanent',
        'created_by',
    ];

    protected $casts = [
        'is_auto_ban' => 'boolean',
        'is_permanent' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if restriction is active
     */
    public function isActive(): bool
    {
        if ($this->is_permanent) {
            return true;
        }

        return $this->expires_at && $this->expires_at->isFuture();
    }

    /**
     * Scope: Active restrictions
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('is_permanent', true)
                ->orWhere('expires_at', '>', now())
                ->orWhereNull('expires_at');
        });
    }

    /**
     * Scope: Blacklist
     */
    public function scopeBlacklist($query)
    {
        return $query->where('type', 'blacklist');
    }

    /**
     * Scope: Whitelist
     */
    public function scopeWhitelist($query)
    {
        return $query->where('type', 'whitelist');
    }
}
