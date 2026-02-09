<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityIntegrityCheck extends Model
{
    protected $fillable = [
        'security_baseline_id',
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

    public function baseline()
    {
        return $this->belongsTo(SecurityBaseline::class, 'security_baseline_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
