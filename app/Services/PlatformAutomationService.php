<?php

namespace App\Services;

use App\Models\AutomationRule;
use App\Models\Tenant;
use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlatformAutomationService
{
    public function __construct(
        protected AdminNotificationService $notifications
    ) {
    }

    /**
     * Run all due scheduled rules
     */
    public function runScheduledRules(): array
    {
        $rules = AutomationRule::active()
            ->scheduled()
            ->orderBy('priority', 'desc')
            ->get();

        $results = [];

        foreach ($rules as $rule) {
            if ($rule->shouldRunNow()) {
                $results[] = $this->executeRule($rule);
            }
        }

        return $results;
    }

    /**
     * Execute a single automation rule
     */
    public function executeRule(AutomationRule $rule): array
    {
        Log::info("Executing automation rule: {$rule->name}");

        try {
            // Check conditions
            if (!$this->evaluateConditions($rule)) {
                $rule->recordRun(true, 'Skipped: conditions not met');
                return ['rule' => $rule->name, 'status' => 'skipped', 'reason' => 'conditions'];
            }

            // Execute actions
            $output = $this->executeActions($rule);
            $rule->recordRun(true, $output);

            return ['rule' => $rule->name, 'status' => 'success', 'output' => $output];

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $rule->recordRun(false, $error);

            $this->notifications->notify(
                category: 'system',
                type: 'error',
                title: "Automation Failed: {$rule->name}",
                message: $error
            );

            return ['rule' => $rule->name, 'status' => 'failed', 'error' => $error];
        }
    }

    /**
     * Evaluate rule conditions
     */
    protected function evaluateConditions(AutomationRule $rule): bool
    {
        $conditions = $rule->conditions ?? [];

        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate single condition
     */
    protected function evaluateCondition(array $condition): bool
    {
        $type = $condition['type'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        $actual = match ($type) {
            'tenant_count' => Tenant::count(),
            'active_tenant_count' => Tenant::where('status', 'active')->count(),
            'queue_size' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'hour' => now()->hour,
            'day_of_week' => now()->dayOfWeek,
            default => null,
        };

        if ($actual === null) {
            return false;
        }

        return match ($operator) {
            '==' => $actual == $value,
            '!=' => $actual != $value,
            '>' => $actual > $value,
            '<' => $actual < $value,
            '>=' => $actual >= $value,
            '<=' => $actual <= $value,
            default => false,
        };
    }

    /**
     * Execute rule actions
     */
    protected function executeActions(AutomationRule $rule): string
    {
        $actions = $rule->actions ?? [];
        $outputs = [];

        foreach ($actions as $action) {
            $outputs[] = $this->executeAction($action);
        }

        return implode("\n", $outputs);
    }

    /**
     * Execute single action
     */
    protected function executeAction(array $action): string
    {
        $type = $action['type'] ?? '';
        $params = $action['params'] ?? [];

        return match ($type) {
            'artisan_command' => $this->runArtisanCommand($params['command'] ?? ''),
            'clear_cache' => $this->clearCache(),
            'optimize_database' => $this->optimizeDatabase(),
            'cleanup_logs' => $this->cleanupLogs($params['days'] ?? 30),
            'cleanup_backups' => $this->cleanupBackups($params['days'] ?? 30),
            'suspend_inactive_tenants' => $this->suspendInactiveTenants($params['days'] ?? 90),
            'send_notification' => $this->sendNotification($params),
            default => "Unknown action: {$type}",
        };
    }

    protected function runArtisanCommand(string $command): string
    {
        if (empty($command)) {
            return 'No command specified';
        }

        Artisan::call($command);
        return Artisan::output();
    }

    protected function clearCache(): string
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        return 'Cache cleared';
    }

    protected function optimizeDatabase(): string
    {
        // MySQL optimization
        $tables = DB::select('SHOW TABLES');
        $count = 0;

        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];
            DB::statement("OPTIMIZE TABLE {$tableName}");
            $count++;
        }

        return "Optimized {$count} tables";
    }

    protected function cleanupLogs(int $days): string
    {
        $path = storage_path('logs');
        $count = 0;

        foreach (glob("{$path}/*.log") as $file) {
            if (filemtime($file) < now()->subDays($days)->timestamp) {
                unlink($file);
                $count++;
            }
        }

        return "Deleted {$count} old log files";
    }

    protected function cleanupBackups(int $days): string
    {
        $path = storage_path('app/backups');
        if (!is_dir($path)) {
            return 'No backups directory';
        }

        $count = 0;
        foreach (glob("{$path}/*", GLOB_ONLYDIR) as $dir) {
            if (filemtime($dir) < now()->subDays($days)->timestamp) {
                $this->deleteDirectory($dir);
                $count++;
            }
        }

        return "Deleted {$count} old backups";
    }

    protected function suspendInactiveTenants(int $days): string
    {
        $count = Tenant::where('status', 'active')
            ->where('last_activity_at', '<', now()->subDays($days))
            ->update(['status' => 'suspended']);

        return "Suspended {$count} inactive tenants";
    }

    protected function sendNotification(array $params): string
    {
        $this->notifications->notify(
            category: $params['category'] ?? 'system',
            type: $params['type'] ?? 'info',
            title: $params['title'] ?? 'Automation Notification',
            message: $params['message'] ?? 'Automated notification'
        );

        return 'Notification sent';
    }

    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = "{$dir}/{$item}";
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    /**
     * Get available actions
     */
    public static function getAvailableActions(): array
    {
        return [
            'artisan_command' => [
                'label' => 'Run Artisan Command',
                'params' => ['command' => 'string'],
            ],
            'clear_cache' => [
                'label' => 'Clear Cache',
                'params' => [],
            ],
            'optimize_database' => [
                'label' => 'Optimize Database',
                'params' => [],
            ],
            'cleanup_logs' => [
                'label' => 'Cleanup Old Logs',
                'params' => ['days' => 'integer'],
            ],
            'cleanup_backups' => [
                'label' => 'Cleanup Old Backups',
                'params' => ['days' => 'integer'],
            ],
            'suspend_inactive_tenants' => [
                'label' => 'Suspend Inactive Tenants',
                'params' => ['days' => 'integer'],
            ],
            'send_notification' => [
                'label' => 'Send Notification',
                'params' => ['title' => 'string', 'message' => 'string'],
            ],
        ];
    }
}
