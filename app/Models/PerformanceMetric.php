<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'endpoint',
        'method',
        'status_code',
        'response_time',
        'memory_usage',
        'query_count',
        'query_time',
        'cache_hits',
        'cache_misses',
        'is_slow',
        'ip_address',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'response_time' => 'float',
        'memory_usage' => 'integer',
        'query_count' => 'integer',
        'query_time' => 'float',
        'cache_hits' => 'integer',
        'cache_misses' => 'integer',
        'is_slow' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($metric) {
            $metric->created_at = now();
            $metric->is_slow = $metric->response_time > 1000;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getFormattedResponseTimeAttribute(): string
    {
        return number_format($this->response_time, 2) . 'ms';
    }

    public function getFormattedMemoryAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($this->memory_usage, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
