<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'method',
        'path',
        'full_url',
        'ip',
        'user_agent',
        'user_id',
        'tenant_id',
        'headers',
        'query_params',
        'body',
        'status_code',
        'response_time_ms',
        'response_size',
        'error_message',
        'error_trace',
        'created_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'query_params' => 'array',
        'body' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeSlowRequests($query, int $ms = 1000)
    {
        return $query->where('response_time_ms', '>', $ms);
    }

    public function scopeErrors($query)
    {
        return $query->where('status_code', '>=', 400);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Helpers
    public function isError(): bool
    {
        return $this->status_code >= 400;
    }

    public function isSlow(int $threshold = 1000): bool
    {
        return $this->response_time_ms > $threshold;
    }
}
