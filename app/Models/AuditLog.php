<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'action',
        'resource_type',
        'resource_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'method',
        'url',
        'status',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get human-readable action label
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'login' => 'Logged In',
            'logout' => 'Logged Out',
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get changes summary
     */
    public function getChangesSummaryAttribute(): ?string
    {
        if (!$this->new_values || !$this->old_values) {
            return null;
        }

        $changes = array_diff_assoc($this->new_values, $this->old_values);
        $summary = [];

        foreach ($changes as $key => $value) {
            $old = $this->old_values[$key] ?? 'N/A';
            $summary[] = "{$key}: {$old} → {$value}";
        }

        return implode(', ', $summary);
    }
}
