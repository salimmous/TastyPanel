<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchIndex extends Model
{
    protected $fillable = [
        'tenant_id',
        'provider',
        'index_name',
        'status',
        'documents_count',
        'last_indexed_at',
        'last_synced_at',
        'settings',
        'error_message',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_indexed_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if index is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if currently indexing
     */
    public function isIndexing(): bool
    {
        return $this->status === 'indexing';
    }
}
