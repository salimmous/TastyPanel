<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantMailbox extends Model
{
    protected $fillable = [
        'tenant_id',
        'email',
        'mailbox_path',
        'quota_mb',
        'last_usage_bytes',
        'last_usage_checked_at',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'quota_mb' => 'integer',
        'last_usage_bytes' => 'integer',
        'last_usage_checked_at' => 'datetime',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
