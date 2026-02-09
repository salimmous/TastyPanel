<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityScan extends Model
{
    protected $fillable = [
        'type',
        'status',
        'target_path',
        'output',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
