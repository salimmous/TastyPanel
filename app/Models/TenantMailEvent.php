<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantMailEvent extends Model
{
    protected $fillable = [
        'tenant_id',
        'tenant_mailbox_id',
        'direction',
        'event_type',
        'recipient',
        'status',
        'message_id',
        'response',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function mailbox()
    {
        return $this->belongsTo(TenantMailbox::class, 'tenant_mailbox_id');
    }
}
