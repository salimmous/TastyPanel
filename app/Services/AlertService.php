<?php

namespace App\Services;

use App\Models\BackupRun;
use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Services\TenantStorageService;
use App\Services\TenantLimitService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    public function dispatch(): array
    {
        $settings = PlatformSetting::getData();
        $interval = (int) ($settings['alerts_interval_hours'] ?? 24);
        $lastSent = $settings['alerts_last_sent_at'] ?? null;

        if ($lastSent && $interval > 0) {
            $last = \Carbon\Carbon::parse($lastSent);
            if ($last->diffInHours(now()) < $interval) {
                return ['skipped' => true, 'reason' => 'interval_not_reached'];
            }
        }

        $sslDays = (int) ($settings['ssl_alert_days'] ?? 14);
        $sslExpiring = app(SslHealthService::class)->expiringSoon($sslDays);

        $http3Issues = Domain::where('http3_enabled', true)
            ->whereNotIn('http3_status', ['ok', 'advertised'])
            ->get();

        $backupFailures = BackupRun::where('status', 'failed')
            ->where('created_at', '>=', now()->subDays(1))
            ->get();

        $storageOverages = $this->storageOverages();

        $hasIssues = $sslExpiring->count() || $http3Issues->count() || $backupFailures->count() || count($storageOverages);
        $sendEmpty = (bool) ($settings['alerts_send_empty'] ?? false);

        if (!$hasIssues && !$sendEmpty) {
            return ['skipped' => true, 'reason' => 'no_issues'];
        }

        $message = $this->buildMessage($sslExpiring, $http3Issues, $backupFailures, $storageOverages);
        $emails = $this->parseEmails($settings['alerts_emails'] ?? '');
        $slackWebhook = $settings['alerts_slack_webhook'] ?? null;

        $sent = false;
        if ($emails) {
            $sent = $this->sendEmail($emails, $message) || $sent;
        }
        if ($slackWebhook) {
            $sent = $this->sendSlack($slackWebhook, $message) || $sent;
        }

        if ($sent) {
            $settings['alerts_last_sent_at'] = now()->toDateTimeString();
            PlatformSetting::updateData($settings);
        }

        return [
            'sent' => $sent,
            'ssl_expiring' => $sslExpiring->count(),
            'http3_issues' => $http3Issues->count(),
            'backup_failures' => $backupFailures->count(),
            'storage_overages' => count($storageOverages),
        ];
    }

    private function buildMessage($sslExpiring, $http3Issues, $backupFailures, array $storageOverages): string
    {
        $lines = [];
        $lines[] = 'TastyPanel Platform Alerts';
        $lines[] = '-----------------------';
        $lines[] = 'SSL expiring: ' . $sslExpiring->count();
        foreach ($sslExpiring as $cert) {
            $lines[] = '- ' . ($cert->domain?->hostname ?? 'unknown') . ' expires at ' . $cert->expires_at;
        }
        $lines[] = 'HTTP/3 issues: ' . $http3Issues->count();
        foreach ($http3Issues as $domain) {
            $lines[] = '- ' . $domain->hostname . ' (' . $domain->http3_status . ')';
        }
        $lines[] = 'Backup failures (24h): ' . $backupFailures->count();
        foreach ($backupFailures as $backup) {
            $lines[] = '- Backup #' . $backup->id . ' at ' . $backup->created_at;
        }
        $lines[] = 'Storage overages: ' . count($storageOverages);
        foreach ($storageOverages as $overage) {
            $lines[] = '- ' . $overage['tenant'] . ' ' . $overage['usage_mb'] . 'MB / ' . $overage['limit_mb'] . 'MB';
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

    private function sendEmail(array $emails, string $message): bool
    {
        try {
            Mail::raw($message, function ($mail) use ($emails) {
                $mail->to($emails)
                    ->subject('TastyPanel Platform Alerts');
            });
            return true;
        } catch (\Throwable $e) {
            return false;
        }
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
}
