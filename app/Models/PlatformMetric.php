<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformMetric extends Model
{
    protected $fillable = [
        'date',
        'total_tenants',
        'active_tenants',
        'new_tenants',
        'churned_tenants',
        'total_recipes',
        'total_articles',
        'new_recipes',
        'new_articles',
        'total_requests',
        'total_bytes',
        'unique_visitors',
        'avg_response_time',
        'error_count',
        'cache_hit_rate',
        'total_storage_bytes',
    ];

    protected $casts = [
        'date' => 'date',
        'avg_response_time' => 'float',
        'cache_hit_rate' => 'float',
    ];

    /**
     * Get metrics for date range
     */
    public static function forRange(string $from, string $to)
    {
        return self::whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();
    }

    /**
     * Get latest metrics
     */
    public static function latest(): ?self
    {
        return self::orderByDesc('date')->first();
    }

    /**
     * Calculate growth percentage
     */
    public function growthVsPrevious(string $field): ?float
    {
        $previous = self::where('date', '<', $this->date)
            ->orderByDesc('date')
            ->first();

        if (! $previous || ! $previous->$field) {
            return null;
        }

        return round((($this->$field - $previous->$field) / $previous->$field) * 100, 2);
    }
}
