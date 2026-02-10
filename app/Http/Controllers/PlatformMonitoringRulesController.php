<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\TenantAlertRule;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlatformMonitoringRulesController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (!PlatformInstallController::isInstalled()) {
            return redirect()->route('platform.install');
        }

        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        if (!AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $q = trim((string) $request->query('q', ''));

        $tenantsQuery = Tenant::query()->with('alertRule');
        if ($q !== '') {
            $tenantsQuery->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%' . $q . '%')
                    ->orWhere('slug', 'like', '%' . $q . '%');
            });
        }

        $tenants = $tenantsQuery
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        $settings = PlatformSetting::getData();

        return view('platform.monitoring-rules', [
            'q' => $q,
            'tenants' => $tenants,
            'platformDefaults' => [
                'alerts_interval_hours' => (int) ($settings['alerts_interval_hours'] ?? 24),
                'ssl_alert_days' => (int) ($settings['ssl_alert_days'] ?? 14),
                'alerts_emails' => (string) ($settings['alerts_emails'] ?? ''),
                'alerts_slack_webhook' => (string) ($settings['alerts_slack_webhook'] ?? ''),
            ],
        ]);
    }

    public function upsert(Request $request, Tenant $tenant): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }
        if (!AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'emails' => ['nullable', 'string'],
            'slack_webhook' => ['nullable', 'url'],
            'interval_hours' => ['nullable', 'integer', 'min:1'],
            'ssl_days' => ['nullable', 'integer', 'min:1'],
            'notify_ssl' => ['nullable', 'boolean'],
            'notify_uptime' => ['nullable', 'boolean'],
            'notify_backup' => ['nullable', 'boolean'],
            'notify_http3' => ['nullable', 'boolean'],
            'notify_storage' => ['nullable', 'boolean'],
        ]);

        $rule = TenantAlertRule::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'enabled' => (bool) ($data['enabled'] ?? false),
                'emails' => $data['emails'] ?? null,
                'slack_webhook' => $data['slack_webhook'] ?? null,
                'interval_hours' => $data['interval_hours'] ?? null,
                'ssl_days' => $data['ssl_days'] ?? null,
                'notify_ssl' => (bool) ($data['notify_ssl'] ?? false),
                'notify_uptime' => (bool) ($data['notify_uptime'] ?? false),
                'notify_backup' => (bool) ($data['notify_backup'] ?? false),
                'notify_http3' => (bool) ($data['notify_http3'] ?? false),
                'notify_storage' => (bool) ($data['notify_storage'] ?? false),
            ]
        );

        $this->audit('tenant_alert_rule_upsert', [
            'tenant_id' => $tenant->id,
            'rule_id' => $rule->id,
            'enabled' => $rule->enabled,
            'interval_hours' => $rule->interval_hours,
            'ssl_days' => $rule->ssl_days,
            'notify_ssl' => $rule->notify_ssl,
            'notify_uptime' => $rule->notify_uptime,
            'notify_backup' => $rule->notify_backup,
            'notify_http3' => $rule->notify_http3,
            'notify_storage' => $rule->notify_storage,
            'has_emails' => !empty(trim((string) $rule->emails)),
            'has_slack' => !empty(trim((string) $rule->slack_webhook)),
        ]);

        return back()->with('success', 'Monitoring rule saved.');
    }

    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }
        if (!AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $deleted = TenantAlertRule::where('tenant_id', $tenant->id)->delete();
        $this->audit('tenant_alert_rule_delete', [
            'tenant_id' => $tenant->id,
            'deleted' => (int) $deleted,
        ]);

        return back()->with('success', 'Monitoring rule reset (tenant-scoped notifications will stop).');
    }

    private function audit(string $action, array $newValues = []): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'tenant_id' => null,
                'action' => $action,
                'resource_type' => 'monitoring_rules',
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
