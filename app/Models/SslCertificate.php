<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SslCertificate extends Model
{
    protected $fillable = [
        'domain_id',
        'status',
        'provider',
        'last_error',
        'issued_at',
        'expires_at',
        'meta',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
