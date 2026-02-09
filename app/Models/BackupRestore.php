<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupRestore extends Model
{
    protected $fillable = [
        'backup_run_id',
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

    public function backup()
    {
        return $this->belongsTo(BackupRun::class, 'backup_run_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
