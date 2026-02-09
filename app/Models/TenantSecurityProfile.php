<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantSecurityProfile extends Model
{
    protected $fillable = [
        'tenant_id',
        'rate_limit_per_minute',
        'blocked_user_agents',
        'blocked_paths',
        'mode',
        'waf_enabled',
        'waf_mode',
        'waf_block_sqli',
        'waf_block_xss',
        'waf_block_lfi',
        'max_monthly_requests',
        'max_storage_mb',
        'max_db_size_mb',
        'max_cpu_percent',
        'max_memory_mb',
        'max_worker_processes',
        'quota_alert_threshold_percent',
        'updated_by',
    ];

    protected $casts = [
        'blocked_user_agents' => 'array',
        'blocked_paths' => 'array',
        'waf_enabled' => 'boolean',
        'waf_block_sqli' => 'boolean',
        'waf_block_xss' => 'boolean',
        'waf_block_lfi' => 'boolean',
        'max_monthly_requests' => 'integer',
        'max_storage_mb' => 'integer',
        'max_db_size_mb' => 'integer',
        'max_cpu_percent' => 'integer',
        'max_memory_mb' => 'integer',
        'max_worker_processes' => 'integer',
        'quota_alert_threshold_percent' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
