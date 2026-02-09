<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UptimeEvent extends Model
{
    protected $fillable = [
        'uptime_check_id',
        'status',
        'response_ms',
        'error',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function check()
    {
        return $this->belongsTo(UptimeCheck::class, 'uptime_check_id');
    }
}
