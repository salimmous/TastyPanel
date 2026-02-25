<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'username',
        'password',
        'home_dir',
        'shell',
        'ssh_keys',
        'status',
    ];

    protected $casts = [
        'ssh_keys' => 'array',
        'password' => 'encrypted',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
