<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisasterRecoveryDrill extends Model
{
    protected $fillable = [
        'scope',
        'tenant_id',
        'backup_run_id',
        'tenant_backup_run_id',
        'status',
        'message',
        'details',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected $casts = [
        'details' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function platformBackupRun(): BelongsTo
    {
        return $this->belongsTo(BackupRun::class, 'backup_run_id');
    }

    public function tenantBackupRun(): BelongsTo
    {
        return $this->belongsTo(TenantBackupRun::class, 'tenant_backup_run_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
