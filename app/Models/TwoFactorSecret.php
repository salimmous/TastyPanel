<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorSecret extends Model
{
    protected $fillable = [
        'user_id',
        'secret',
        'recovery_codes',
        'enabled',
        'verified_at',
        'enabled_at',
        'trusted_devices',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'verified_at' => 'datetime',
        'enabled_at' => 'datetime',
        'trusted_devices' => 'array',
    ];

    protected $hidden = [
        'secret',
        'recovery_codes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
