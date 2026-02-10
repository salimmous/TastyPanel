<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantAlertRule extends Model
{
    protected $fillable = [
        'tenant_id',
        'enabled',
        'emails',
        'slack_webhook',
        'interval_hours',
        'ssl_days',
        'notify_ssl',
        'notify_uptime',
        'notify_backup',
        'notify_http3',
        'notify_storage',
        'last_sent_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'notify_ssl' => 'boolean',
        'notify_uptime' => 'boolean',
        'notify_backup' => 'boolean',
        'notify_http3' => 'boolean',
        'notify_storage' => 'boolean',
        'last_sent_at' => 'datetime',
        'interval_hours' => 'integer',
        'ssl_days' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

