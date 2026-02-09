<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScheduledPublication extends Model
{
    protected $fillable = [
        'schedulable_type',
        'schedulable_id',
        'action',
        'scheduled_at',
        'executed_at',
        'status',
        'user_id',
        'data',
        'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
        'data' => 'array',
    ];

    public function schedulable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Pending schedules
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Due for execution
     */
    public function scopeDue($query)
    {
        return $query->where('scheduled_at', '<=', now())
            ->where('status', 'pending');
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'executed_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'executed_at' => now(),
        ]);
    }

    /**
     * Execute the scheduled action
     */
    public function execute(): bool
    {
        try {
            $this->update(['status' => 'executing']);

            $model = $this->schedulable;

            if (!$model) {
                throw new \Exception('Schedulable model not found');
            }

            match ($this->action) {
                'publish' => $model->update(['status' => 'published']),
                'unpublish' => $model->update(['status' => 'draft']),
                'update' => $model->update($this->data ?? []),
                default => throw new \Exception("Unknown action: {$this->action}"),
            };

            $this->markAsCompleted();
            return true;

        } catch (\Exception $e) {
            $this->markAsFailed($e->getMessage());
            return false;
        }
    }
}
