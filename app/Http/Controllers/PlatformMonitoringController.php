<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BackupRun;
use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Models\SslCertificate;
use App\Models\Tenant;
use App\Models\UptimeCheck;
use App\Models\UptimeEvent;
use App\Services\UptimeMonitorService;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlatformMonitoringController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! PlatformInstallController::isInstalled()) {
            return redirect()->route('platform.install');
        }

        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }

        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $settings = PlatformSetting::getData();
        $sslDays = (int) ($settings['ssl_alert_days'] ?? 14);

        $uptimeChecks = UptimeCheck::query()
            ->with('tenant:id,name')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $tenants = Tenant::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $events = UptimeEvent::query()
            ->with(['check:id,tenant_id,name,url,expected_status', 'check.tenant:id,name'])
            ->orderByDesc('checked_at')
            ->limit(200)
            ->get();

        $sslExpiring = SslCertificate::query()
            ->with('domain:id,hostname')
            ->where('status', 'issued')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($sslDays))
            ->orderBy('expires_at')
            ->limit(200)
            ->get();

        $backupFailures = BackupRun::query()
            ->with('creator:id,name')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $http3Issues = Domain::query()
            ->where('http3_enabled', true)
            ->whereNotIn('http3_status', ['ok', 'advertised'])
            ->orderBy('hostname')
            ->limit(200)
            ->get(['id', 'hostname', 'http3_status', 'http3_error', 'http3_checked_at']);

        return view('platform.monitoring', [
            'sslDays' => $sslDays,
            'settings' => [
                'ssl_alert_days' => (int) ($settings['ssl_alert_days'] ?? 14),
                'alerts_emails' => (string) ($settings['alerts_emails'] ?? ''),
                'alerts_slack_webhook' => (string) ($settings['alerts_slack_webhook'] ?? ''),
                'alerts_interval_hours' => (int) ($settings['alerts_interval_hours'] ?? 24),
                'uptime_check_interval_minutes' => (int) ($settings['uptime_check_interval_minutes'] ?? 5),
            ],
            'uptimeChecks' => $uptimeChecks,
            'tenants' => $tenants,
            'events' => $events,
            'sslExpiring' => $sslExpiring,
            'backupFailures' => $backupFailures,
            'http3Issues' => $http3Issues,
            'lastAction' => session('runbook_action'),
            'lastOutput' => session('runbook_output'),
            'lastSuccess' => session('runbook_success'),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }

        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $data = $request->validate([
            'ssl_alert_days' => ['nullable', 'integer', 'min:1'],
            'alerts_emails' => ['nullable', 'string'],
            'alerts_slack_webhook' => ['nullable', 'url'],
            'alerts_interval_hours' => ['nullable', 'integer', 'min:1'],
            'uptime_check_interval_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        $current = PlatformSetting::getData();
        $next = array_merge($current, $data);
        PlatformSetting::updateData($next);

        $this->audit('monitoring_settings_update', $data);

        return back()->with('success', 'Monitoring settings updated.');
    }

    public function storeUptimeCheck(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }

        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
            'expected_status' => ['required', 'integer', 'min:100', 'max:599'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $check = UptimeCheck::create([
            'tenant_id' => (int) $data['tenant_id'],
            'name' => (string) $data['name'],
            'url' => (string) $data['url'],
            'expected_status' => (int) $data['expected_status'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'created_by' => Auth::id(),
        ]);

        $this->audit('uptime_check_create', [
            'id' => $check->id,
            'tenant_id' => $check->tenant_id,
            'name' => $check->name,
            'url' => $check->url,
            'expected_status' => $check->expected_status,
            'is_active' => $check->is_active,
        ]);

        return back()->with('success', 'Uptime check created.');
    }

    public function updateUptimeCheck(Request $request, UptimeCheck $check): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }

        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
            'expected_status' => ['required', 'integer', 'min:100', 'max:599'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $old = $check->toArray();

        $check->name = (string) $data['name'];
        $check->url = (string) $data['url'];
        $check->expected_status = (int) $data['expected_status'];
        $check->is_active = (bool) ($data['is_active'] ?? false);
        $check->save();

        $this->audit('uptime_check_update', [
            'id' => $check->id,
            'old' => [
                'name' => $old['name'] ?? null,
                'url' => $old['url'] ?? null,
                'expected_status' => $old['expected_status'] ?? null,
                'is_active' => $old['is_active'] ?? null,
            ],
            'new' => [
                'name' => $check->name,
                'url' => $check->url,
                'expected_status' => $check->expected_status,
                'is_active' => $check->is_active,
            ],
        ]);

        return back()->with('success', 'Uptime check updated.');
    }

    public function destroyUptimeCheck(Request $request, UptimeCheck $check): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }

        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $id = $check->id;
        try {
            UptimeEvent::where('uptime_check_id', $check->id)->delete();
        } catch (\Throwable $e) {
        }
        $check->delete();

        $this->audit('uptime_check_delete', ['id' => $id]);

        return back()->with('success', 'Uptime check deleted.');
    }

    public function runUptimeCheck(Request $request, UptimeCheck $check, UptimeMonitorService $monitor): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }

        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $result = $monitor->check($check);
        $output = json_encode([
            'check_id' => $check->id,
            'tenant_id' => $check->tenant_id,
            'name' => $check->name,
            'url' => $check->url,
            'expected_status' => $check->expected_status,
            'result' => $result,
        ], JSON_PRETTY_PRINT);

        $this->audit('uptime_check_run', [
            'id' => $check->id,
            'result' => $result,
        ]);

        return back()
            ->with('runbook_action', 'uptime_check_run')
            ->with('runbook_success', (bool) ($result['success'] ?? false))
            ->with('runbook_output', $output ?: '')
            ->with('success', 'Uptime check executed.');
    }

    private function audit(string $action, array $newValues = []): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'tenant_id' => null,
                'action' => $action,
                'resource_type' => 'monitoring',
                'resource_id' => null,
                'description' => $action,
                'old_values' => null,
                'new_values' => $newValues ?: null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'method' => request()->method(),
                'url' => request()->fullUrl(),
                'status' => 'success',
                'error_message' => null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
