<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Share extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'recipe_id',
        'tenant_id',
        'platform',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($share) {
            $share->created_at = now();

            // Auto-fill IP and user agent if not set
            if (! $share->ip_address) {
                $share->ip_address = request()->ip();
            }
            if (! $share->user_agent) {
                $share->user_agent = request()->userAgent();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get share count by platform for a recipe
     */
    public static function getSharesByPlatform(int $recipeId): array
    {
        return self::where('recipe_id', $recipeId)
            ->selectRaw('platform, COUNT(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();
    }
}
