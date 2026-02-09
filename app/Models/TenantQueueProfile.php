<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantQueueProfile extends Model
{
    protected $fillable = [
        'tenant_id',
        'high_queue',
        'default_queue',
        'low_queue',
        'min_workers',
        'max_workers',
        'scale_up_threshold',
        'scale_down_threshold',
        'updated_by',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
