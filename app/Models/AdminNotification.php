<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'category',
        'title',
        'message',
        'data',
        'action_url',
        'is_read',
        'read_at',
        'created_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($notification) {
            $notification->created_at = now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Scope: Unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: By category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: By type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get icon based on type
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'error' => '❌',
            'warning' => '⚠️',
            'success' => '✅',
            'info' => 'ℹ️',
            'security' => '🔒',
            default => '📢',
        };
    }

    /**
     * Get color based on type
     */
    public function getColorAttribute(): string
    {
        return match ($this->type) {
            'error' => 'danger',
            'warning' => 'warning',
            'success' => 'success',
            'info' => 'info',
            'security' => 'primary',
            default => 'secondary',
        };
    }
}
