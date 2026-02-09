<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantBackupRun extends Model
{
    protected $fillable = [
        'tenant_id',
        'type',
        'status',
        'path',
        'disk',
        'remote_path',
        'checksum',
        'size_bytes',
        'output',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
