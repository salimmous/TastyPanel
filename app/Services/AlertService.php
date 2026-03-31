<?php

namespace App\Services;

use App\Models\BackupRun;
use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\TenantAlertRule;
use App\Models\TenantBackupRun;
use App\Models\UptimeCheck;
use App\Services\TenantStorageService;
use App\Services\TenantLimitService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    public function dispatch(): array
    {
        $settings = PlatformSetting::getData();
        $platformInterval = (int) ($settings['alerts_interval_hours'] ?? 24);
        $platformLastSent = $settings['alerts_last_sent_at'] ?? null;
        $platformDue = $this->isDue($platformLastSent, $platformInterval);

        $platformSslDays = (int) ($settings['ssl_alert_days'] ?? 14);
        $sendEmpty = (bool) ($settings['alerts_send_empty'] ?? false);
        $uptimeIntervalMinutes = (int) ($settings['uptime_check_interval_minutes'] ?? 5);
        $uptimeWindowMinutes = max(15, $uptimeIntervalMinutes * 3);

        $rules = TenantAlertRule::query()
            ->with('tenant:id,name,slug')
            ->get()
            ->keyBy('tenant_id');

        $maxSslDays = $platformSslDays;
        foreach ($rules as $rule) {
            if (!empty($rule->ssl_days) && (int) $rule->ssl_days > $maxSslDays) {
                $maxSslDays = (int) $rule->ssl_days;
            }
        }

        $sslExpiringMax = app(SslHealthService::class)->expiringSoon($maxSslDays);

        $http3Issues = Domain::query()
            ->where('http3_enabled', true)
            ->whereNotIn('http3_status', ['ok', 'advertised'])
            ->get(['id', 'tenant_id', 'hostname', 'http3_status', 'http3_error', 'http3_checked_at']);

        $tenantBackupFailures = TenantBackupRun::query()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->get(['id', 'tenant_id', 'type', 'status', 'created_at', 'output']);

        $platformBackupFailures = BackupRun::query()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->get(['id', 'type', 'status', 'created_at']);

        $uptimeFailures = UptimeCheck::query()
            ->where('is_active', true)
            ->whereNotNull('last_checked_at')
            ->where('last_checked_at', '>=', now()->subMinutes($uptimeWindowMinutes))
            ->where(function ($q) {
                $q->whereNotNull('last_error')
                    ->orWhereColumn('last_status', '!=', 'expected_status');
            })
            ->get(['id', 'tenant_id', 'name', 'url', 'expected_status', 'last_checked_at', 'last_status', 'last_error', 'last_response_ms']);

        $storageOverages = $this->storageOverages();

        $issuesByTenant = [];

        foreach ($sslExpiringMax as $cert) {
            $tenantId = (int) ($cert->domain?->tenant_id ?? 0);
            if ($tenantId > 0) {
                $issuesByTenant[$tenantId]['ssl'][] = $cert;
            }
        }
        foreach ($http3Issues as $d) {
            $tenantId = (int) ($d->tenant_id ?? 0);
            if ($tenantId > 0) {
                $issuesByTenant[$tenantId]['http3'][] = $d;
            }
        }
        foreach ($tenantBackupFailures as $b) {
            $tenantId = (int) ($b->tenant_id ?? 0);
            if ($tenantId > 0) {
                $issuesByTenant[$tenantId]['backup'][] = $b;
            }
        }
        foreach ($uptimeFailures as $u) {
            $tenantId = (int) ($u->tenant_id ?? 0);
            if ($tenantId > 0) {
                $issuesByTenant[$tenantId]['uptime'][] = $u;
            }
        }
        foreach ($storageOverages as $o) {
            $tenantId = (int) ($o['tenant_id'] ?? 0);
            if ($tenantId > 0) {
                $issuesByTenant[$tenantId]['storage'][] = $o;
            }
        }

        $tenantIds = array_keys($issuesByTenant);
        $tenantMap = Tenant::query()
            ->whereIn('id', $tenantIds)
            ->get(['id', 'name', 'slug'])
            ->keyBy('id');

        // PLATFORM DELIVERY (global channels)
        $platformEmails = $this->parseEmails((string) ($settings['alerts_emails'] ?? ''));
        $platformSlackWebhook = $settings['alerts_slack_webhook'] ?? null;

        $sslExpiringPlatform = array_values(array_filter($sslExpiringMax->all(), function ($cert) use ($platformSslDays) {
            if (!$cert?->expires_at) {
                return false;
            }
            $daysLeft = now()->diffInDays($cert->expires_at, false);
            return $daysLeft <= $platformSslDays;
        }));

        $platformHasIssues = count($sslExpiringPlatform)
            || $http3Issues->count()
            || $tenantBackupFailures->count()
            || $platformBackupFailures->count()
            || $uptimeFailures->count()
            || count($storageOverages);

        $platformSent = false;
        if ($platformDue && (($platformHasIssues) || $sendEmpty) && ($platformEmails || $platformSlackWebhook)) {
            $message = $this->buildPlatformMessage(
                $platformSslDays,
                $sslExpiringPlatform,
                $http3Issues->all(),
                $tenantBackupFailures->all(),
                $platformBackupFailures->all(),
                $uptimeFailures->all(),
                $storageOverages,
                $tenantMap
            );

            if ($platformEmails) {
                $platformSent = $this->sendEmail($platformEmails, $message, 'TastyPanel Platform Alerts') || $platformSent;
            }
            if ($platformSlackWebhook) {
                $platformSent = $this->sendSlack($platformSlackWebhook, $message) || $platformSent;
            }
        }

        if ($platformSent) {
            $settings['alerts_last_sent_at'] = now()->toDateTimeString();
            PlatformSetting::updateData($settings);
        }

        // TENANT DELIVERY (per-tenant channels)
        $tenantSent = 0;
        $tenantSkippedInterval = 0;
        $tenantSkippedNoChannels = 0;
        $tenantSkippedNoIssues = 0;

        foreach ($rules as $tenantId => $rule) {
            if (!$rule->enabled) {
                continue;
            }

            $interval = (int) ($rule->interval_hours ?? $platformInterval);
            if (!$this->isDue($rule->last_sent_at, $interval)) {
                $tenantSkippedInterval++;
                continue;
            }

            $tenant = $tenantMap->get((int) $tenantId) ?? $rule->tenant;
            if (!$tenant) {
                continue;
            }

            $emails = $this->parseEmails((string) ($rule->emails ?? ''));
            $slackWebhook = $rule->slack_webhook ?: null;
            if (!$emails && !$slackWebhook) {
                $tenantSkippedNoChannels++;
                continue;
            }

            $tenantIssues = $issuesByTenant[(int) $tenantId] ?? [];
            $sslDays = (int) ($rule->ssl_days ?? $platformSslDays);

            $sslItems = $rule->notify_ssl
                ? array_values(array_filter($tenantIssues['ssl'] ?? [], function ($cert) use ($sslDays) {
                    if (!$cert?->expires_at) {
                        return false;
                    }
                    $daysLeft = now()->diffInDays($cert->expires_at, false);
                    return $daysLeft <= $sslDays;
                }))
                : [];

            $http3Items = $rule->notify_http3 ? ($tenantIssues['http3'] ?? []) : [];
            $backupItems = $rule->notify_backup ? ($tenantIssues['backup'] ?? []) : [];
            $uptimeItems = $rule->notify_uptime ? ($tenantIssues['uptime'] ?? []) : [];
            $storageItems = $rule->notify_storage ? ($tenantIssues['storage'] ?? []) : [];

            $hasIssues = count($sslItems) || count($http3Items) || count($backupItems) || count($uptimeItems) || count($storageItems);
            if (!$hasIssues) {
                $tenantSkippedNoIssues++;
                continue;
            }

            $message = $this->buildTenantMessage(
                (string) ($tenant->name ?? ('tenant#' . $tenantId)),
                $sslDays,
                $sslItems,
                $http3Items,
                $backupItems,
                $uptimeItems,
                $storageItems
            );

            $sent = false;
            if ($emails) {
                $sent = $this->sendEmail($emails, $message, 'TastyPanel Tenant Alerts: ' . (string) ($tenant->name ?? 'Tenant')) || $sent;
            }
            if ($slackWebhook) {
                $sent = $this->sendSlack($slackWebhook, $message) || $sent;
            }

            if ($sent) {
                $rule->last_sent_at = now();
                $rule->save();
                $tenantSent++;
            }
        }

        $sentAnything = $platformSent || $tenantSent > 0;
        if (!$sentAnything) {
            if (!$platformDue && $tenantSkippedInterval > 0) {
                return ['skipped' => true, 'reason' => 'interval_not_reached'];
            }
            if (!$platformHasIssues && !$sendEmpty && $tenantSkippedNoIssues > 0) {
                return ['skipped' => true, 'reason' => 'no_issues'];
            }
        }

        return [
            'sent_platform' => $platformSent,
            'sent_tenants' => $tenantSent,
            'skipped_tenant_interval' => $tenantSkippedInterval,
            'skipped_tenant_no_channels' => $tenantSkippedNoChannels,
            'skipped_tenant_no_issues' => $tenantSkippedNoIssues,
            'ssl_expiring_max' => $sslExpiringMax->count(),
            'http3_issues' => $http3Issues->count(),
            'tenant_backup_failures' => $tenantBackupFailures->count(),
            'platform_backup_failures' => $platformBackupFailures->count(),
            'uptime_failures' => $uptimeFailures->count(),
            'storage_overages' => count($storageOverages),
        ];
    }

    private function buildPlatformMessage(
        int $sslDays,
        array $sslExpiring,
        array $http3Issues,
        array $tenantBackupFailures,
        array $platformBackupFailures,
        array $uptimeFailures,
        array $storageOverages,
        $tenantMap
    ): string
    {
        $lines = [];
        $lines[] = 'TastyPanel Platform Alerts';
        $lines[] = 'Generated at: ' . now()->toDateTimeString();
        $lines[] = '-----------------------';

        $lines[] = 'SSL expiring (<= ' . $sslDays . 'd): ' . count($sslExpiring);
        foreach ($sslExpiring as $cert) {
            $tenantName = $tenantMap->get((int) ($cert->domain?->tenant_id ?? 0))?->name ?? 'unknown-tenant';
            $daysLeft = $cert->expires_at ? now()->diffInDays($cert->expires_at, false) : null;
            $lines[] = '- [' . $tenantName . '] ' . ($cert->domain?->hostname ?? 'unknown') . ' expires at ' . ($cert->expires_at?->toDateTimeString() ?? 'unknown') . ($daysLeft !== null ? " ({$daysLeft}d)" : '');
        }
        $lines[] = 'HTTP/3 issues: ' . count($http3Issues);
        foreach ($http3Issues as $domain) {
            $tenantName = $tenantMap->get((int) ($domain->tenant_id ?? 0))?->name ?? 'unknown-tenant';
            $lines[] = '- [' . $tenantName . '] ' . ($domain->hostname ?? 'unknown') . ' (' . ($domain->http3_status ?? 'unknown') . ')';
        }
        $lines[] = 'Uptime failures (recent): ' . count($uptimeFailures);
        foreach ($uptimeFailures as $check) {
            $tenantName = $tenantMap->get((int) ($check->tenant_id ?? 0))?->name ?? 'unknown-tenant';
            $status = $check->last_status ?? '—';
            $expected = $check->expected_status ?? '—';
            $error = $check->last_error ? (' error=' . $check->last_error) : '';
            $lines[] = '- [' . $tenantName . '] ' . ($check->name ?? 'check') . ' ' . ($check->url ?? '') . " status={$status} expected={$expected}{$error}";
        }

        $lines[] = 'Tenant backup failures (24h): ' . count($tenantBackupFailures);
        foreach ($tenantBackupFailures as $backup) {
            $tenantName = $tenantMap->get((int) ($backup->tenant_id ?? 0))?->name ?? 'unknown-tenant';
            $lines[] = '- [' . $tenantName . '] Backup #' . ($backup->id ?? '—') . ' at ' . ($backup->created_at ?? '—') . ' (type=' . ($backup->type ?? '—') . ')';
        }

        $lines[] = 'Platform backup failures (24h): ' . count($platformBackupFailures);
        foreach ($platformBackupFailures as $backup) {
            $lines[] = '- Backup #' . ($backup->id ?? '—') . ' at ' . ($backup->created_at ?? '—') . ' (type=' . ($backup->type ?? '—') . ')';
        }
        $lines[] = 'Storage overages: ' . count($storageOverages);
        foreach ($storageOverages as $overage) {
            $lines[] = '- ' . ($overage['tenant'] ?? 'unknown') . ' ' . ($overage['usage_mb'] ?? '—') . 'MB / ' . ($overage['limit_mb'] ?? '—') . 'MB';
        }

        return implode("\n", $lines);
    }

    private function buildTenantMessage(
        string $tenantName,
        int $sslDays,
        array $sslExpiring,
        array $http3Issues,
        array $backupFailures,
        array $uptimeFailures,
        array $storageOverages
    ): string {
        $lines = [];
        $lines[] = 'TastyPanel Tenant Alerts';
        $lines[] = 'Tenant: ' . $tenantName;
        $lines[] = 'Generated at: ' . now()->toDateTimeString();
        $lines[] = '-----------------------';

        $lines[] = 'SSL expiring (<= ' . $sslDays . 'd): ' . count($sslExpiring);
        foreach ($sslExpiring as $cert) {
            $daysLeft = $cert->expires_at ? now()->diffInDays($cert->expires_at, false) : null;
            $lines[] = '- ' . ($cert->domain?->hostname ?? 'unknown') . ' expires at ' . ($cert->expires_at?->toDateTimeString() ?? 'unknown') . ($daysLeft !== null ? " ({$daysLeft}d)" : '');
        }

        $lines[] = 'HTTP/3 issues: ' . count($http3Issues);
        foreach ($http3Issues as $domain) {
            $lines[] = '- ' . ($domain->hostname ?? 'unknown') . ' (' . ($domain->http3_status ?? 'unknown') . ')';
        }

        $lines[] = 'Uptime failures (recent): ' . count($uptimeFailures);
        foreach ($uptimeFailures as $check) {
            $status = $check->last_status ?? '—';
            $expected = $check->expected_status ?? '—';
            $error = $check->last_error ? (' error=' . $check->last_error) : '';
            $lines[] = '- ' . ($check->name ?? 'check') . ' ' . ($check->url ?? '') . " status={$status} expected={$expected}{$error}";
        }

        $lines[] = 'Backup failures (24h): ' . count($backupFailures);
        foreach ($backupFailures as $backup) {
            $lines[] = '- Backup #' . ($backup->id ?? '—') . ' at ' . ($backup->created_at ?? '—') . ' (type=' . ($backup->type ?? '—') . ')';
        }

        $lines[] = 'Storage overages: ' . count($storageOverages);
        foreach ($storageOverages as $overage) {
            $lines[] = '- ' . ($overage['usage_mb'] ?? '—') . 'MB / ' . ($overage['limit_mb'] ?? '—') . 'MB';
        }

        return implode("\n", $lines);
    }

    private function storageOverages(): array
    {
        $storage = app(TenantStorageService::class);
        $limits = app(TenantLimitService::class);
        $overages = [];

        foreach (Tenant::all(['id', 'name']) as $tenant) {
            $limitBytes = $limits->storageLimitBytes($tenant);
            if (!$limitBytes) {
                continue;
            }
            $usage = $storage->usage($tenant);
            if ($usage['bytes'] > $limitBytes) {
                $overages[] = [
                    'tenant_id' => (int) $tenant->id,
                    'tenant' => $tenant->name,
                    'usage_mb' => (int) round($usage['bytes'] / 1024 / 1024),
                    'limit_mb' => (int) round($limitBytes / 1024 / 1024),
                ];
            }
        }

        return $overages;
    }

    private function parseEmails(string $emails): array
    {
        $items = array_map('trim', explode(',', $emails));
        return array_values(array_filter($items));
    }

    private function sendSlack(string $webhook, string $message): bool
    {
        try {
            $response = Http::post($webhook, [
                'text' => $message,
            ]);
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function isDue($lastSent, int $intervalHours): bool
    {
        if ($intervalHours <= 0) {
            return true;
        }

        if (!$lastSent) {
            return true;
        }

        try {
            $last = $lastSent instanceof \Carbon\CarbonInterface
                ? $lastSent
                : \Carbon\Carbon::parse((string) $lastSent);
        } catch (\Throwable $e) {
            return true;
        }

        return $last->diffInHours(now()) >= $intervalHours;
    }

    private function sendEmail(array $emails, string $message, string $subject): bool
    {
        try {
            Mail::raw($message, function ($mail) use ($emails, $subject) {
                $mail->to($emails)
                    ->subject($subject);
            });
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
