<?php

namespace App\Console\Commands;

use App\Services\HealthCheckService;
use App\Services\ErrorTrackerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MonitorHealth extends Command
{
    protected $signature = 'monitor:health {--alert : Send alerts if health is degraded}';
    protected $description = 'Check system health and optionally send alerts';

    public function handle(HealthCheckService $healthCheck, ErrorTrackerService $errorTracker): int
    {
        $this->info('Running health checks...');

        $health = $healthCheck->check();

        $this->displayHealthStatus($health);

        // Send alert if requested and health is not ok
        if ($this->option('alert') && in_array($health['status'], ['degraded', 'down'])) {
            $this->sendHealthAlert($health);
        }

        return $health['status'] === 'healthy' ? 0 : 1;
    }

    protected function displayHealthStatus(array $health): void
    {
        $statusColor = match ($health['status']) {
            'healthy' => 'green',
            'degraded' => 'yellow',
            'down' => 'red',
            default => 'white',
        };

        $this->line('');
        $this->line("<fg={$statusColor};options=bold>Overall Status: {$health['status']}</>");
        $this->line('');

        foreach ($health['checks'] as $service => $check) {
            $icon = $check['status'] === 'up' ? '✓' : '✗';
            $color = $check['status'] === 'up' ? 'green' : 'red';

            $this->line("<fg={$color}>{$icon}</> {$service}: {$check['message']}");

            if (isset($check['response_time'])) {
                $this->line("  Response Time: {$check['response_time']}");
            }
        }

        $this->line('');
    }

    protected function sendHealthAlert(array $health): void
    {
        $adminEmail = config('monitoring.error_tracking.alert_email');

        if (!$adminEmail) {
            $this->warn('No admin email configured for alerts');
            return;
        }

        try {
            Mail::raw(
                "System Health Alert\n\n" .
                "Status: {$health['status']}\n" .
                "Time: {$health['timestamp']}\n\n" .
                "Checks:\n" .
                json_encode($health['checks'], JSON_PRETTY_PRINT),
                function ($message) use ($adminEmail, $health) {
                    $message->to($adminEmail)
                        ->subject("[TastyPanel] System Health: {$health['status']}");
                }
            );

            $this->info('Alert sent to ' . $adminEmail);
        } catch (\Exception $e) {
            $this->error('Failed to send alert: ' . $e->getMessage());
        }
    }
}
