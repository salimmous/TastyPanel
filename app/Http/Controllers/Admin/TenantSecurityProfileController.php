<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantQuotaService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantSecurityProfileController extends Controller
{
    public function show(Request $request, Tenant $tenant, TenantQuotaService $quotaService)
    {
        $this->authorizeAccess($request, $tenant);

        $profile = $tenant->securityProfile()->firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['updated_by' => $request->user()?->id]
        );

        return response()->json([
            'data' => $profile,
            'quota' => [
                'limits' => $quotaService->limitsFor($tenant),
                'usage' => $quotaService->usageSnapshot($tenant),
            ],
        ]);
    }

    public function update(Request $request, Tenant $tenant, TenantQuotaService $quotaService)
    {
        $this->authorizeAccess($request, $tenant);

        $data = $request->validate([
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:0'],
            'blocked_user_agents' => ['nullable', 'array'],
            'blocked_user_agents.*' => ['string', 'max:255'],
            'blocked_paths' => ['nullable', 'array'],
            'blocked_paths.*' => ['string', 'max:255'],
            'mode' => ['nullable', Rule::in(['block', 'log'])],
            'waf_enabled' => ['nullable', 'boolean'],
            'waf_mode' => ['nullable', Rule::in(['block', 'log'])],
            'waf_block_sqli' => ['nullable', 'boolean'],
            'waf_block_xss' => ['nullable', 'boolean'],
            'waf_block_lfi' => ['nullable', 'boolean'],
            'max_monthly_requests' => ['nullable', 'integer', 'min:0'],
            'max_storage_mb' => ['nullable', 'integer', 'min:0'],
            'max_db_size_mb' => ['nullable', 'integer', 'min:0'],
            'max_cpu_percent' => ['nullable', 'integer', 'min:0'],
            'max_memory_mb' => ['nullable', 'integer', 'min:0'],
            'max_worker_processes' => ['nullable', 'integer', 'min:0'],
            'quota_alert_threshold_percent' => ['nullable', 'integer', 'min:50', 'max:99'],
        ]);

        $profile = $tenant->securityProfile()->firstOrNew([
            'tenant_id' => $tenant->id,
        ]);

        $profile->fill($data);
        $profile->updated_by = $request->user()?->id;
        $profile->save();

        return response()->json([
            'data' => $profile->fresh(),
            'quota' => [
                'limits' => $quotaService->limitsFor($tenant->fresh()),
                'usage' => $quotaService->usageSnapshot($tenant->fresh()),
            ],
        ]);
    }

    private function authorizeAccess(Request $request, Tenant $tenant): void
    {
        abort_unless(AdminPermissions::canManageTenantInfrastructure($request->user()), 403);
        if ($request->user() && ! AdminPermissions::isSuperadmin($request->user()) && $request->user()->tenant_id !== $tenant->id) {
            abort(403);
        }
    }
}
