<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Export extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'format',
        'type',
        'status',
        'file_path',
        'filename',
        'file_size',
        'filters',
        'options',
        'total_items',
        'processed_items',
        'error_message',
        'expires_at',
        'download_count',
        'last_downloaded_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'options' => 'array',
        'expires_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
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
     * Check if export is ready for download
     */
    public function isReady(): bool
    {
        return $this->status === 'completed' && $this->file_path;
    }

    /**
     * Check if export is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get human readable format
     */
    public function getFormatLabelAttribute(): string
    {
        return match ($this->format) {
            'csv' => 'CSV (Excel)',
            'json' => 'JSON',
            'wordpress' => 'WordPress WXR',
            'pdf' => 'PDF',
            'excel' => 'Excel (.xlsx)',
            default => strtoupper($this->format),
        };
    }

    /**
     * Get download URL
     */
    public function getDownloadUrlAttribute(): ?string
    {
        if (! $this->isReady()) {
            return null;
        }

        return route('exports.download', $this->id);
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
