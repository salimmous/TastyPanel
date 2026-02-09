<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantBackupRestore extends Model
{
    protected $fillable = [
        'tenant_backup_run_id',
        'status',
        'output',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function backupRun()
    {
        return $this->belongsTo(TenantBackupRun::class, 'tenant_backup_run_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
