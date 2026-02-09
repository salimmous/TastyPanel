<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSecret extends Model
{
    protected $fillable = [
        'tenant_id',
        'secret_key',
        'encrypted_value',
        'version',
        'rotated_at',
        'updated_by',
    ];

    protected $casts = [
        'rotated_at' => 'datetime',
    ];

    protected $hidden = [
        'encrypted_value',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

