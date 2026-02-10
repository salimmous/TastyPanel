<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Services\ContentSnapshotService;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreviewWebController extends Controller
{
    public function index($tenantId)
    {
        $tenant = $this->resolveTenant((int) $tenantId);

        $previewDomains = $tenant->domains->where('environment', 'preview')->values();
        $primary = $tenant->domains->firstWhere('is_primary', true) ?: $tenant->domains->first();

        return view('platform.preview.index', [
            'tenant' => $tenant,
            'primaryDomain' => $primary,
            'previewDomains' => $previewDomains,
        ]);
    }

    public function enable(Request $request, $tenantId, ContentSnapshotService $snapshots): RedirectResponse
    {
        $tenant = $this->resolveTenant((int) $tenantId);

        $old = $tenant->only(['preview_enabled', 'preview_theme_id']);

        $tenant->preview_enabled = true;
        if (!$tenant->preview_theme_id) {
            $tenant->preview_theme_id = $tenant->theme_id;
        }
        $tenant->save();

        $productionSettings = $tenant->settings?->data ?? [];
        $tenant->previewSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'preview'],
            ['data' => $productionSettings]
        );

        $syncOk = true;
        $syncError = null;
        try {
            $snapshots->syncEnvironment($tenant->id, 'production', 'preview');
        } catch (\Throwable $e) {
            $syncOk = false;
            $syncError = $e->getMessage();
        }

        $this->logAudit(
            $tenant,
            'preview_enable',
            $old,
            ['preview_enabled' => true, 'preview_theme_id' => $tenant->preview_theme_id, 'content_sync' => $syncOk],
            $syncOk ? null : ($syncError ?: 'Sync failed')
        );

        return redirect()
            ->route('platform.tenants.preview', $tenant->id)
            ->with($syncOk ? 'success' : 'error', $syncOk ? 'Preview enabled.' : 'Preview enabled, but content sync failed.');
    }

    public function sync(Request $request, $tenantId, ContentSnapshotService $snapshots): RedirectResponse
    {
        $tenant = $this->resolveTenant((int) $tenantId);

        $old = $tenant->only(['preview_enabled', 'preview_theme_id']);

        $tenant->preview_theme_id = $tenant->theme_id;
        $tenant->preview_enabled = true;
        $tenant->save();

        $productionSettings = $tenant->settings?->data ?? [];
        $tenant->previewSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'preview'],
            ['data' => $productionSettings]
        );

        $syncOk = true;
        $syncError = null;
        try {
            $snapshots->syncEnvironment($tenant->id, 'production', 'preview');
        } catch (\Throwable $e) {
            $syncOk = false;
            $syncError = $e->getMessage();
        }

        $this->logAudit(
            $tenant,
            'preview_sync_prod_to_preview',
            $old,
            ['preview_enabled' => true, 'preview_theme_id' => $tenant->preview_theme_id, 'content_sync' => $syncOk],
            $syncOk ? null : ($syncError ?: 'Sync failed')
        );

        return redirect()
            ->route('platform.tenants.preview', $tenant->id)
            ->with($syncOk ? 'success' : 'error', $syncOk ? 'Production synced to Preview.' : 'Sync failed.');
    }

    public function promote(Request $request, $tenantId, ContentSnapshotService $snapshots): RedirectResponse
    {
        $tenant = $this->resolveTenant((int) $tenantId);

        if (!$tenant->preview_theme_id) {
            return redirect()
                ->route('platform.tenants.preview', $tenant->id)
                ->with('error', 'Preview theme is not set. Sync production to preview first.');
        }

        $old = $tenant->only(['theme_id', 'preview_theme_id']);

        $tenant->theme_id = $tenant->preview_theme_id;

        $previewSettings = $tenant->previewSettings?->data ?? [];
        $tenant->settings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'production'],
            ['data' => $previewSettings]
        );
        $tenant->save();

        $syncOk = true;
        $syncError = null;
        try {
            $snapshots->syncEnvironment($tenant->id, 'preview', 'production');
        } catch (\Throwable $e) {
            $syncOk = false;
            $syncError = $e->getMessage();
        }

        $this->logAudit(
            $tenant,
            'preview_promote_to_prod',
            $old,
            ['theme_id' => $tenant->theme_id, 'content_sync' => $syncOk],
            $syncOk ? null : ($syncError ?: 'Sync failed')
        );

        return redirect()
            ->route('platform.tenants.preview', $tenant->id)
            ->with($syncOk ? 'success' : 'error', $syncOk ? 'Preview promoted to Production.' : 'Promotion completed, but content sync failed.');
    }

    public function destroy(Request $request, $tenantId): RedirectResponse
    {
        $tenant = $this->resolveTenant((int) $tenantId);

        $old = $tenant->only(['preview_enabled']);
        $tenant->preview_enabled = false;
        $tenant->save();

        $this->logAudit($tenant, 'preview_disable', $old, ['preview_enabled' => false], null);

        return redirect()
            ->route('platform.tenants.preview', $tenant->id)
            ->with('success', 'Preview disabled.');
    }

    private function resolveTenant(int $id): Tenant
    {
        if (!Auth::check()) {
            abort(403);
        }

        abort_unless(AdminPermissions::isSuperadmin(Auth::user()), 403);

        return Tenant::with([
            'theme',
            'previewTheme',
            'settings',
            'previewSettings',
            'domains',
            'primaryDomain',
        ])->findOrFail($id);
    }

    private function logAudit(Tenant $tenant, string $action, ?array $oldValues, ?array $newValues, ?string $error): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'tenant_id' => $tenant->id,
                'action' => $action,
                'resource_type' => Tenant::class,
                'resource_id' => $tenant->id,
                'description' => $action . ' for tenant #' . $tenant->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'method' => request()->method(),
                'url' => request()->fullUrl(),
                'status' => $error ? 'failed' : 'success',
                'error_message' => $error,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never break the UI for audit failures.
        }
    }
}

