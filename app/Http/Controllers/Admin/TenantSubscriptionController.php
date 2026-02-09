<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class TenantSubscriptionController extends Controller
{
    public function assign(Request $request, Tenant $tenant)
    {
        abort_unless(AdminPermissions::isSuperadmin($request->user()), 403);

        $data = $request->validate([
            'plan_id' => ['nullable', 'exists:plans,id'],
            'status' => ['nullable', 'string'],
        ]);

        $plan = $data['plan_id'] ? Plan::find($data['plan_id']) : null;

        $subscription = TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan?->id,
            'status' => $data['status'] ?? ($plan ? 'active' : 'inactive'),
            'started_at' => now(),
        ]);

        return response()->json([
            'data' => $subscription->load('plan'),
        ], 201);
    }
}
