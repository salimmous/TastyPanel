<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Database extends Model
{
    use HasFactory;

    protected  = [
        'tenant_id',
        'name',
        'username',
        'password',
        'size_mb',
        'last_backup_at',
        'status',
    ];

    protected  = [
        'last_backup_at' => 'datetime',
        'size_mb' => 'decimal:2',
        'password' => 'encrypted',
    ];

    public function tenant(): BelongsTo
    {
        return ->belongsTo(Tenant::class);
    }
}
