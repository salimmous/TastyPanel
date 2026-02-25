<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FirewallRule;
use App\Services\FirewallService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class FirewallController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $rules = FirewallRule::orderByDesc('id')->get();

        return response()->json(['data' => $rules]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperadmin($request);

        $data = $request->validate([
            'action' => ['required', 'string'],
            'protocol' => ['required', 'string'],
            'port' => ['required', 'string'],
            'source' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule = FirewallRule::create([
            'action' => strtolower($data['action']),
            'protocol' => strtolower($data['protocol']),
            'port' => $data['port'],
            'source' => $data['source'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json(['data' => $rule], 201);
    }

    public function update(Request $request, FirewallRule $rule)
    {
        $this->authorizeSuperadmin($request);

        $data = $request->validate([
            'action' => ['required', 'string'],
            'protocol' => ['required', 'string'],
            'port' => ['required', 'string'],
            'source' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule->update([
            'action' => strtolower($data['action']),
            'protocol' => strtolower($data['protocol']),
            'port' => $data['port'],
            'source' => $data['source'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? $rule->is_active,
        ]);

        return response()->json(['data' => $rule]);
    }

    public function destroy(Request $request, FirewallRule $rule)
    {
        $this->authorizeSuperadmin($request);
        $rule->delete();

        return response()->json(['message' => 'Firewall rule deleted.']);
    }

    public function apply(Request $request, FirewallService $firewall)
    {
        $this->authorizeSuperadmin($request);
        $results = $firewall->applyAll();

        return response()->json(['data' => $results]);
    }

    public function status(Request $request, FirewallService $firewall)
    {
        $this->authorizeSuperadmin($request);

        return response()->json(['data' => $firewall->status()]);
    }

    private function authorizeSuperadmin(Request $request): void
    {
        if (! AdminPermissions::isSuperadmin($request->user())) {
            abort(403);
        }
    }
}
