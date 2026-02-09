<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    public $timestamps = false;
    protected $table = 'search_logs';

    protected $fillable = [
        'tenant_id',
        'query',
        'type',
        'results_count',
        'response_time',
        'filters',
        'ip',
        'user_agent',
        'user_id',
        'session_id',
        'referrer',
        'created_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
