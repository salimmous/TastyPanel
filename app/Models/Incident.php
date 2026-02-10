<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = [
        'tenant_id',
        'fingerprint',
        'category',
        'status',
        'severity',
        'title',
        'summary',
        'resource_type',
        'resource_id',
        'meta',
        'first_seen_at',
        'last_seen_at',
        'acked_by',
        'acked_at',
        'snoozed_until',
        'resolved_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'acked_at' => 'datetime',
        'snoozed_until' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ackedBy()
    {
        return $this->belongsTo(User::class, 'acked_by');
    }

    public function events()
    {
        return $this->hasMany(IncidentEvent::class);
    }
}

