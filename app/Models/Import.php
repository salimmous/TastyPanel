<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'format',
        'type',
        'status',
        'file_path',
        'original_filename',
        'file_size',
        'total_items',
        'processed_items',
        'success_count',
        'error_count',
        'skipped_count',
        'mapping',
        'options',
        'errors',
        'error_file_path',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'mapping' => 'array',
        'options' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_items === 0) {
            return 0;
        }

        return (int) (($this->processed_items / $this->total_items) * 100);
    }

    /**
     * Check if import is completed
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'completed_with_errors']);
    }

    /**
     * Check if import has errors
     */
    public function hasErrors(): bool
    {
        return $this->error_count > 0;
    }

    /**
     * Get human  readable status
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'completed_with_errors' => 'Completed with Errors',
            'failed' => 'Failed',
            default => 'Unknown',
        };
    }
}
