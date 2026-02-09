<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\PlatformSetting;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $settings = PlatformSetting::getData();
        $cronEnabled = (bool) ($settings['cron_enabled'] ?? true);
        if (!$cronEnabled) {
            return;
        }
        $cronTimezone = (string) ($settings['cron_timezone'] ?? config('app.timezone', 'UTC'));
        if ($cronTimezone === '') {
            $cronTimezone = config('app.timezone', 'UTC');
        }

        $http3Interval = (int) ($settings['http3_check_interval_minutes'] ?? 30);
        $http3Interval = $http3Interval > 0 ? $http3Interval : 30;
        $schedule->command('http3:check')->everyMinutes($http3Interval)->timezone($cronTimezone)->withoutOverlapping();

        $sslIntervalHours = (int) ($settings['ssl_check_interval_hours'] ?? 6);
        $sslIntervalHours = $sslIntervalHours > 0 ? $sslIntervalHours : 6;
        $schedule->command('ssl:renew')->everyHours($sslIntervalHours)->timezone($cronTimezone)->withoutOverlapping();

        $backupIntervalHours = (int) ($settings['backup_interval_hours'] ?? 24);
        $backupIntervalHours = $backupIntervalHours > 0 ? $backupIntervalHours : 24;
        $schedule->command('backups:run')->everyHours($backupIntervalHours)->timezone($cronTimezone)->withoutOverlapping();

        $schedule->command('backups:cleanup')->daily()->timezone($cronTimezone)->withoutOverlapping();
        $schedule->command('tenant:backups')->hourly()->timezone($cronTimezone)->withoutOverlapping();
        $schedule->command('tenant:backups:cleanup')->daily()->timezone($cronTimezone)->withoutOverlapping();
        $schedule->command('automation:run')->everyThirtyMinutes()->timezone($cronTimezone)->withoutOverlapping();

        $alertsInterval = (int) ($settings['alerts_interval_hours'] ?? 24);
        $alertsInterval = $alertsInterval > 0 ? $alertsInterval : 24;
        $schedule->command('alerts:dispatch')->everyHours($alertsInterval)->timezone($cronTimezone)->withoutOverlapping();

        $analyticsInterval = (int) ($settings['analytics_interval_hours'] ?? 6);
        $analyticsInterval = $analyticsInterval > 0 ? $analyticsInterval : 6;
        $schedule->command('analytics:collect')->everyHours($analyticsInterval)->timezone($cronTimezone)->withoutOverlapping();

        $uptimeInterval = (int) ($settings['uptime_check_interval_minutes'] ?? 5);
        $uptimeInterval = $uptimeInterval > 0 ? $uptimeInterval : 5;
        $schedule->command('uptime:check')->everyMinutes($uptimeInterval)->timezone($cronTimezone)->withoutOverlapping();

        $integrityInterval = (int) ($settings['integrity_check_interval_hours'] ?? 24);
        $integrityInterval = $integrityInterval > 0 ? $integrityInterval : 24;
        $schedule->command('integrity:check')->everyHours($integrityInterval)->timezone($cronTimezone)->withoutOverlapping();

        $schedule->command('audit:cleanup')->daily()->timezone($cronTimezone)->withoutOverlapping();
        $schedule->command('drill:run --all-tenants')->weeklyOn(0, '03:00')->timezone($cronTimezone)->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
