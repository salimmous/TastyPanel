<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantTrafficMetric extends Model
{
    protected $fillable = [
        'tenant_id',
        'date',
        'requests',
        'unique_ips',
        'bytes',
        'status_4xx',
        'status_5xx',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
