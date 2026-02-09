<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\UptimeCheck;
use App\Models\UptimeEvent;
use App\Services\UptimeMonitorService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class UptimeController extends Controller
{
    public function index(Request $request, Tenant $tenant)
    {
        $this->authorizeTenant($request, $tenant);
        $checks = UptimeCheck::where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->get();
        return response()->json(['data' => $checks]);
    }

    public function store(Request $request, Tenant $tenant)
    {
        $this->authorizeTenant($request, $tenant);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'string', 'max:255'],
            'expected_status' => ['nullable', 'integer', 'min:100', 'max:599'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $check = UptimeCheck::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'url' => $data['url'],
            'expected_status' => $data['expected_status'] ?? 200,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json(['data' => $check], 201);
    }

    public function update(Request $request, Tenant $tenant, UptimeCheck $check)
    {
        $this->authorizeTenant($request, $tenant);
        if ($check->tenant_id !== $tenant->id) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'string', 'max:255'],
            'expected_status' => ['nullable', 'integer', 'min:100', 'max:599'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $check->update([
            'name' => $data['name'],
            'url' => $data['url'],
            'expected_status' => $data['expected_status'] ?? $check->expected_status,
            'is_active' => $data['is_active'] ?? $check->is_active,
        ]);

        return response()->json(['data' => $check]);
    }

    public function destroy(Request $request, Tenant $tenant, UptimeCheck $check)
    {
        $this->authorizeTenant($request, $tenant);
        if ($check->tenant_id !== $tenant->id) {
            abort(404);
        }

        $check->delete();
        return response()->json(['message' => 'Uptime check deleted.']);
    }

    public function events(Request $request, Tenant $tenant, UptimeCheck $check)
    {
        $this->authorizeTenant($request, $tenant);
        if ($check->tenant_id !== $tenant->id) {
            abort(404);
        }

        $events = UptimeEvent::where('uptime_check_id', $check->id)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($events);
    }

    public function run(Request $request, Tenant $tenant, UptimeCheck $check, UptimeMonitorService $monitor)
    {
        $this->authorizeTenant($request, $tenant);
        if ($check->tenant_id !== $tenant->id) {
            abort(404);
        }

        $result = $monitor->check($check);
        return response()->json(['data' => $result]);
    }

    private function authorizeTenant(Request $request, Tenant $tenant): void
    {
        $user = $request->user();
        if (AdminPermissions::isSuperadmin($user)) {
            return;
        }
        if ((int) $user?->tenant_id !== (int) $tenant->id) {
            abort(403);
        }
    }
}
