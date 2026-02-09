<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProvisioningJob extends Model
{
    protected $fillable = [
        'tenant_id',
        'status',
        'message',
        'meta',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
