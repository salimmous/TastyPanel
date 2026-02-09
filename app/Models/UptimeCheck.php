<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UptimeCheck extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'expected_status',
        'is_active',
        'last_checked_at',
        'last_status',
        'last_response_ms',
        'last_error',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function events()
    {
        return $this->hasMany(UptimeEvent::class);
    }
}
