<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Tenant;
use App\Services\ApiKeyService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index(Request $request, Tenant $tenant)
    {
        $this->authorizeTenant($request, $tenant);

        $keys = ApiKey::where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $keys,
        ]);
    }

    public function store(Request $request, Tenant $tenant, ApiKeyService $service)
    {
        $this->authorizeTenant($request, $tenant);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scopes' => ['nullable', 'array'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:0'],
            'expires_at' => ['nullable', 'date'],
        ]);

        [$key, $plain] = $service->create(
            $tenant,
            $data['name'],
            $data['scopes'] ?? [],
            $request->user()?->id,
            (int) ($data['rate_limit_per_minute'] ?? 0),
            $data['expires_at'] ?? null
        );

        return response()->json([
            'data' => $key,
            'plain' => $plain,
        ], 201);
    }

    public function revoke(Request $request, Tenant $tenant, ApiKey $apiKey)
    {
        $this->authorizeTenant($request, $tenant);
        if ($apiKey->tenant_id !== $tenant->id) {
            abort(404);
        }

        $apiKey->revoked_at = now();
        $apiKey->save();

        return response()->json([
            'data' => $apiKey,
        ]);
    }

    public function rotate(Request $request, Tenant $tenant, ApiKey $apiKey, ApiKeyService $service)
    {
        $this->authorizeTenant($request, $tenant);
        if ($apiKey->tenant_id !== $tenant->id) {
            abort(404);
        }

        $apiKey->revoked_at = now();
        $apiKey->save();

        [$newKey, $plain] = $service->create(
            $tenant,
            $apiKey->name,
            $apiKey->scopes ?? [],
            $request->user()?->id,
            (int) ($apiKey->rate_limit_per_minute ?? 0),
            $apiKey->expires_at?->toDateString()
        );

        return response()->json([
            'data' => $newKey,
            'plain' => $plain,
        ]);
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
