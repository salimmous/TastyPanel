<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'trigger_type',
        'trigger_config',
        'conditions',
        'actions',
        'is_active',
        'priority',
        'last_run_at',
        'last_run_status',
        'last_run_output',
        'run_count',
        'success_count',
        'failure_count',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    /**
     * Scope: Active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Scheduled rules
     */
    public function scopeScheduled($query)
    {
        return $query->where('trigger_type', 'schedule');
    }

    /**
     * Check if rule should run based on schedule
     */
    public function shouldRunNow(): bool
    {
        if ($this->trigger_type !== 'schedule') {
            return false;
        }

        $config = $this->trigger_config ?? [];
        $interval = $config['interval_minutes'] ?? 60;

        if (! $this->last_run_at) {
            return true;
        }

        return $this->last_run_at->addMinutes($interval)->isPast();
    }

    /**
     * Record run result
     */
    public function recordRun(bool $success, ?string $output = null): void
    {
        $this->run_count++;
        if ($success) {
            $this->success_count++;
        } else {
            $this->failure_count++;
        }

        $this->last_run_at = now();
        $this->last_run_status = $success ? 'success' : 'failed';
        $this->last_run_output = $output;
        $this->save();
    }

    /**
     * Get available rule types
     */
    public static function getTypes(): array
    {
        return [
            'maintenance' => 'Maintenance Tasks',
            'cleanup' => 'Cleanup Operations',
            'alert' => 'Alert Triggers',
            'action' => 'Automated Actions',
        ];
    }

    /**
     * Get available trigger types
     */
    public static function getTriggerTypes(): array
    {
        return [
            'schedule' => 'Scheduled (Cron)',
            'event' => 'Event-based',
            'condition' => 'Condition-based',
        ];
    }
}
