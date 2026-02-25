<?php

namespace App\Http\Controllers;

use App\Models\BackupRun;
use App\Models\DisasterRecoveryDrill;
use App\Models\ErrorLog;
use App\Models\PerformanceMetric;
use App\Models\ProvisioningJob;
use App\Services\MetricsCollector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MetricsController extends Controller
{
    public function index(Request $request, MetricsCollector $collector): Response
    {
        if (! config('monitoring.prometheus.enabled', true)) {
            return response('not found', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        if (! $this->isAuthorized($request)) {
            return response('forbidden', 403, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $platform = $collector->collectPlatformMetrics();
        $queuePending = 0;
        $queueFailed = 0;
        try {
            $queuePending = (int) DB::table('jobs')->count();
            $queueFailed = (int) DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
            $queuePending = 0;
            $queueFailed = 0;
        }

        $avgResponse = (float) PerformanceMetric::query()
            ->where('created_at', '>=', now()->subMinutes(5))
            ->avg('response_time');

        $p95Response = (float) PerformanceMetric::query()
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderBy('response_time')
            ->skip((int) floor(max(0, PerformanceMetric::where('created_at', '>=', now()->subMinutes(5))->count() * 0.95)))
            ->value('response_time');

        $failedProvisioning = (int) ProvisioningJob::query()->where('status', 'failed')->count();
        $runningProvisioning = (int) ProvisioningJob::query()->where('status', 'running')->count();

        $criticalErrors = (int) ErrorLog::query()
            ->where('level', 'critical')
            ->where('is_resolved', false)
            ->count();

        $drillPassed = (int) DisasterRecoveryDrill::query()->where('status', 'passed')->count();
        $drillFailed = (int) DisasterRecoveryDrill::query()->where('status', 'failed')->count();

        $lines = [];
        $this->metric($lines, 'tastypanel_tenants_total', 'gauge', (float) ($platform['tenants']['total'] ?? 0));
        $this->metric($lines, 'tastypanel_tenants_active', 'gauge', (float) ($platform['tenants']['active'] ?? 0));
        $this->metric($lines, 'tastypanel_tenants_inactive', 'gauge', (float) ($platform['tenants']['inactive'] ?? 0));
        $this->metric($lines, 'tastypanel_queue_pending_jobs', 'gauge', (float) $queuePending);
        $this->metric($lines, 'tastypanel_queue_failed_jobs', 'gauge', (float) $queueFailed);
        $this->metric($lines, 'tastypanel_provisioning_running', 'gauge', (float) $runningProvisioning);
        $this->metric($lines, 'tastypanel_provisioning_failed_total', 'counter', (float) $failedProvisioning);
        $this->metric($lines, 'tastypanel_backup_runs_total', 'counter', (float) BackupRun::query()->count());
        $this->metric($lines, 'tastypanel_errors_critical_unresolved', 'gauge', (float) $criticalErrors);
        $this->metric($lines, 'tastypanel_http_response_avg_ms_5m', 'gauge', $avgResponse > 0 ? $avgResponse : 0.0);
        $this->metric($lines, 'tastypanel_http_response_p95_ms_5m', 'gauge', $p95Response > 0 ? $p95Response : 0.0);
        $this->metric($lines, 'tastypanel_drill_passed_total', 'counter', (float) $drillPassed);
        $this->metric($lines, 'tastypanel_drill_failed_total', 'counter', (float) $drillFailed);

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=UTF-8',
        ]);
    }

    private function isAuthorized(Request $request): bool
    {
        $token = (string) config('monitoring.prometheus.token', '');
        if ($token === '') {
            return true;
        }

        $provided = trim((string) $request->bearerToken());
        if ($provided === '') {
            $provided = trim((string) $request->query('token', ''));
        }

        return hash_equals($token, $provided);
    }

    private function metric(array &$lines, string $name, string $type, float $value): void
    {
        $lines[] = "# TYPE {$name} {$type}";
        $lines[] = "{$name} ".sprintf('%.4f', $value);
    }
}
