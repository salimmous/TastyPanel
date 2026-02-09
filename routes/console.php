<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Services\SslProvisioningService;
use App\Services\NginxProvisioningService;
use App\Services\Http3HealthService;
use App\Services\BackupService;
use App\Services\BackupCleanupService;
use App\Services\TenantBackupService;
use App\Services\TenantBackupCleanupService;
use App\Services\SslHealthService;
use App\Services\AlertService;
use App\Services\TrafficAnalyticsService;
use App\Services\SecurityScanService;
use App\Services\FirewallService;
use App\Services\UptimeMonitorService;
use App\Services\FileIntegrityService;
use App\Services\AuditExportService;
use App\Services\AutomationRunnerService;
use App\Services\AutomationSettingsService;
use App\Services\ContentScoringService;
use App\Models\SecurityBaseline;
use App\Models\TenantBackupRun;
use App\Models\SiteSetting;
use App\Models\Article;
use App\Models\Recipe;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ssl:provision {domain}', function (SslProvisioningService $ssl, $domain) {
    $domainModel = Domain::find($domain);
    if (!$domainModel) {
        $this->error('Domain not found.');
        return;
    }
    $ssl->provisionCertificate($domainModel, true);
    $this->info('SSL provisioning triggered for ' . $domainModel->hostname);
})->purpose('Provision SSL certificates via certbot and Cloudflare DNS-01');

Artisan::command('nginx:provision {domain}', function (NginxProvisioningService $nginx, $domain) {
    $domainModel = Domain::find($domain);
    if (!$domainModel) {
        $this->error('Domain not found.');
        return;
    }
    $nginx->provisionDomain($domainModel, true);
    $this->info('Nginx provisioning triggered for ' . $domainModel->hostname);
})->purpose('Provision Nginx vhost config for a domain');

Artisan::command('http3:check', function (Http3HealthService $health) {
    $count = 0;
    Domain::where('http3_enabled', true)->chunkById(50, function ($domains) use ($health, &$count) {
        foreach ($domains as $domain) {
            $health->check($domain);
            $count++;
        }
    });
    $this->info("HTTP/3 health checked for {$count} domains.");
})->purpose('Check HTTP/3 health for all enabled domains');

Artisan::command('backups:run', function (BackupService $backupService) {
    $run = $backupService->run();
    $this->info('Backup completed with status: ' . $run->status);
})->purpose('Run platform backup (DB + storage)');

Artisan::command('backups:cleanup {--days=}', function (BackupCleanupService $cleanup) {
    $days = $this->option('days');
    $days = $days !== null ? (int) $days : null;
    $deleted = $cleanup->cleanup($days);
    $this->info("Cleaned {$deleted} backup folders.");
})->purpose('Cleanup old backups based on retention days');

Artisan::command('tenant:backups', function (TenantBackupService $backupService) {
    $settings = PlatformSetting::getData();
    $defaultInterval = (int) ($settings['tenant_backup_interval_hours'] ?? $settings['backup_interval_hours'] ?? 24);
    $defaultInterval = $defaultInterval > 0 ? $defaultInterval : 24;
    $now = now();
    $count = 0;
    $skipped = 0;

    Tenant::where('backup_enabled', true)->chunkById(50, function ($tenants) use ($backupService, $defaultInterval, $now, &$count, &$skipped) {
        foreach ($tenants as $tenant) {
            $interval = $tenant->backup_interval_hours;
            if ($interval === null) {
                $interval = $defaultInterval;
            }
            $interval = (int) $interval;
            if ($interval <= 0) {
                $skipped++;
                continue;
            }
            if ($tenant->instance_status && $tenant->instance_status !== 'ready') {
                $skipped++;
                continue;
            }
            if (!$tenant->instance_root || !is_dir($tenant->instance_root)) {
                $skipped++;
                continue;
            }

            $last = TenantBackupRun::where('tenant_id', $tenant->id)
                ->where('status', 'completed')
                ->orderByDesc('finished_at')
                ->first();

            if ($last && $last->finished_at && $last->finished_at->diffInHours($now) < $interval) {
                $skipped++;
                continue;
            }

            $backupService->run($tenant, null, 'scheduled');
            $count++;
        }
    });

    $this->info("Tenant backups triggered: {$count}, skipped: {$skipped}");
})->purpose('Run scheduled backups for tenants');

Artisan::command('tenant:backups:cleanup {--days=}', function (TenantBackupCleanupService $cleanup) {
    $days = $this->option('days');
    $days = $days !== null ? (int) $days : null;
    $deleted = $cleanup->cleanup($days);
    $this->info("Cleaned {$deleted} tenant backup runs.");
})->purpose('Cleanup old tenant backups based on retention days');

Artisan::command('automation:run', function (AutomationRunnerService $runner, AutomationSettingsService $settingsService) {
    $count = 0;
    $skipped = 0;
    $now = now();

    Tenant::chunkById(50, function ($tenants) use ($runner, $settingsService, $now, &$count, &$skipped) {
        foreach ($tenants as $tenant) {
            $settingsList = SiteSetting::where('tenant_id', $tenant->id)->get();
            if ($settingsList->isEmpty()) {
                $skipped++;
                continue;
            }

            foreach ($settingsList as $settings) {
                $automation = $settingsService->mergeWithDefaults($settings->data['automation'] ?? []);
                $schedule = $automation['schedule'] ?? [];
                if (empty($schedule['enabled'])) {
                    $skipped++;
                    continue;
                }

                $environment = $schedule['environment'] ?? $settings->environment ?? 'production';
                $timezone = $schedule['timezone'] ?? 'UTC';
                $start = $schedule['window_start'] ?? '08:00';
                $end = $schedule['window_end'] ?? '22:00';

                $current = $now->copy()->setTimezone($timezone);
                $windowStart = $current->copy()->setTimeFromTimeString($start);
                $windowEnd = $current->copy()->setTimeFromTimeString($end);

                $inWindow = $windowStart->lte($windowEnd)
                    ? $current->between($windowStart, $windowEnd)
                    : ($current->gte($windowStart) || $current->lte($windowEnd));

                if (!$inWindow) {
                    $skipped++;
                    continue;
                }

                $postsPerDay = (int) ($schedule['posts_per_day'] ?? 0);
                if ($postsPerDay <= 0) {
                    $skipped++;
                    continue;
                }

                $runsToday = $runner->runsToday($tenant, $environment);
                if ($runsToday >= $postsPerDay) {
                    $skipped++;
                    continue;
                }

                $runner->runForTenant($tenant, $environment, 'scheduled');
                $count++;
            }
        }
    });

    $this->info("Automation runs triggered: {$count}, skipped: {$skipped}");
})->purpose('Run scheduled automation for all tenants');

Artisan::command('content:score {--tenant=} {--type=}', function (ContentScoringService $scoring) {
    $tenantId = $this->option('tenant');
    $type = $this->option('type');

    $scoreArticle = function (Article $article) use ($scoring) {
        $score = $scoring->score($article->title ?? '', $article->description ?? '');
        $article->fill($score);
        $article->save();
    };

    $scoreRecipe = function (Recipe $recipe) use ($scoring) {
        $parts = [];
        if (!empty($recipe->description)) {
            $parts[] = $recipe->description;
        }
        if (is_array($recipe->ingredients)) {
            $parts[] = implode(' ', $recipe->ingredients);
        }
        if (is_array($recipe->instructions)) {
            $parts[] = implode(' ', array_map(function ($item) {
                if (is_string($item)) {
                    return $item;
                }
                if (is_array($item)) {
                    return implode(' ', $item);
                }
                return '';
            }, $recipe->instructions));
        }
        $score = $scoring->score($recipe->title ?? '', implode("\n", $parts));
        $recipe->fill($score);
        $recipe->save();
    };

    $articleQuery = Article::query();
    $recipeQuery = Recipe::query();
    if ($tenantId) {
        $articleQuery->where('tenant_id', $tenantId);
        $recipeQuery->where('tenant_id', $tenantId);
    }

    $count = 0;
    if (!$type || $type === 'articles') {
        $articleQuery->chunkById(100, function ($articles) use (&$count, $scoreArticle) {
            foreach ($articles as $article) {
                $scoreArticle($article);
                $count++;
            }
        });
    }
    if (!$type || $type === 'recipes') {
        $recipeQuery->chunkById(100, function ($recipes) use (&$count, $scoreRecipe) {
            foreach ($recipes as $recipe) {
                $scoreRecipe($recipe);
                $count++;
            }
        });
    }

    $this->info("Scored {$count} items.");
})->purpose('Recalculate content scores for articles/recipes');

Artisan::command('ssl:renew {--days=}', function (SslHealthService $sslHealth, SslProvisioningService $ssl) {
    if (!config('services.ssl.auto')) {
        $this->info('SSL_AUTO is disabled. Skipping renewals.');
        return;
    }

    $settings = PlatformSetting::getData();
    $days = $this->option('days');
    $days = $days !== null ? (int) $days : (int) ($settings['ssl_alert_days'] ?? 14);
    $cutoff = now()->addDays($days);
    $count = 0;

    Domain::with('sslCertificate')->whereHas('sslCertificate', function ($query) {
        $query->where('status', 'issued');
    })->chunkById(50, function ($domains) use ($sslHealth, $ssl, $cutoff, &$count) {
        foreach ($domains as $domain) {
            $cert = $domain->sslCertificate;
            if (!$cert) {
                continue;
            }
            if (!$cert->expires_at) {
                $sslHealth->updateExpiry($cert);
            }
            if ($cert->expires_at && $cert->expires_at->lessThanOrEqualTo($cutoff)) {
                $ssl->provisionCertificate($domain, true);
                $count++;
            }
        }
    });

    $this->info("SSL renewals triggered for {$count} domains.");
})->purpose('Renew SSL certificates nearing expiry');

Artisan::command('alerts:dispatch', function (AlertService $alerts) {
    $result = $alerts->dispatch();
    if (!empty($result['skipped'])) {
        $this->info('Alerts skipped: ' . ($result['reason'] ?? 'unknown'));
        return;
    }
    $this->info('Alerts sent: ' . ($result['sent'] ? 'yes' : 'no'));
})->purpose('Send platform alert notifications (SSL, HTTP/3, backups)');

Artisan::command('analytics:collect', function (TrafficAnalyticsService $analytics) {
    $summary = $analytics->collect();
    $this->info('Analytics collected for ' . $summary['domains'] . ' domains.');
    $this->info('Lines processed: ' . $summary['lines']);
})->purpose('Collect traffic analytics from Nginx access logs');

Artisan::command('security:scan {--path=} {--type=}', function (SecurityScanService $scan) {
    $path = $this->option('path') ?: base_path();
    $type = $this->option('type') ?: 'malware';
    $run = $scan->run($path, null, $type);
    $this->info('Security scan status: ' . $run->status);
})->purpose('Run malware/security scan on a path');

Artisan::command('firewall:apply', function (FirewallService $firewall) {
    $results = $firewall->applyAll();
    $success = collect($results)->every(fn ($item) => $item['success']);
    $this->info('Firewall rules applied: ' . ($success ? 'yes' : 'with errors'));
})->purpose('Apply firewall rules (UFW)');

Artisan::command('uptime:check', function (UptimeMonitorService $monitor) {
    $summary = $monitor->run();
    $this->info('Uptime checks: ' . $summary['checks']);
    $this->info('Failures: ' . $summary['failures']);
})->purpose('Run uptime checks for configured URLs');

Artisan::command('integrity:check', function (FileIntegrityService $integrity) {
    $baseline = SecurityBaseline::orderByDesc('id')->first();
    if (!$baseline) {
        $this->info('No integrity baseline found.');
        return;
    }
    $check = $integrity->check($baseline, null);
    $this->info('Integrity check status: ' . $check->status);
})->purpose('Run file integrity check against latest baseline');

Artisan::command('audit:cleanup {--days=}', function (AuditExportService $exports) {
    $settings = PlatformSetting::getData();
    $days = $this->option('days');
    $days = $days !== null ? (int) $days : (int) ($settings['audit_export_retention_days'] ?? 30);
    $deleted = $exports->cleanup($days);
    $this->info("Audit exports cleaned: {$deleted}");
})->purpose('Cleanup old audit exports based on retention days');
