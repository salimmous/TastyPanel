<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantEnvSyncService;
use App\Services\TenantSecretService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantSecretController extends Controller
{
    public function index(Request $request, Tenant $tenant, TenantSecretService $secrets)
    {
        $this->authorizeAccess($request, $tenant);

        return response()->json([
            'data' => $secrets->listMetadata($tenant),
        ]);
    }

    public function store(Request $request, Tenant $tenant, TenantSecretService $secrets, TenantEnvSyncService $envSync)
    {
        $this->authorizeAccess($request, $tenant);

        $data = $request->validate([
            'secret_key' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9._-]+$/i'],
            'secret_value' => ['required', 'string', 'min:1', 'not_regex:/[\r\n]/'],
            'sync_to_env' => ['nullable', 'boolean'],
            'env_key' => ['nullable', 'string', 'max:120', 'regex:/^[A-Z][A-Z0-9_]*$/'],
        ]);

        $secret = $secrets->setSecret(
            $tenant,
            trim((string) $data['secret_key']),
            (string) $data['secret_value'],
            $request->user()?->id
        );

        $sync = null;
        if (($data['sync_to_env'] ?? false) === true) {
            $syncKey = $data['env_key'] ?? $envSync->deriveEnvKey($secret->secret_key);
            $sync = $envSync->upsert($tenant, $syncKey, (string) $data['secret_value']);
        }

        return response()->json([
            'data' => [
                'secret_key' => $secret->secret_key,
                'version' => $secret->version,
                'rotated_at' => $secret->rotated_at,
                'updated_at' => $secret->updated_at,
                'sync' => $sync,
            ],
        ], ($sync && !$sync['success']) ? 422 : 201);
    }

    public function destroy(Request $request, Tenant $tenant, string $secretKey, TenantSecretService $secrets)
    {
        $this->authorizeAccess($request, $tenant);

        $request->validate([
            'confirm' => ['required', Rule::in(['DELETE_SECRET'])],
        ]);

        $deleted = $secrets->deleteSecret($tenant, $secretKey);
        if (!$deleted) {
            return response()->json([
                'message' => 'Secret not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function syncToEnv(Request $request, Tenant $tenant, TenantSecretService $secrets, TenantEnvSyncService $envSync)
    {
        $this->authorizeAccess($request, $tenant);

        $data = $request->validate([
            'secret_key' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9._-]+$/i'],
            'env_key' => ['nullable', 'string', 'max:120', 'regex:/^[A-Z][A-Z0-9_]*$/'],
        ]);

        $secretKey = trim((string) $data['secret_key']);
        $secretValue = $secrets->getSecretValue($tenant, $secretKey);
        if ($secretValue === null) {
            return response()->json([
                'message' => 'Secret not found.',
            ], 404);
        }

        $envKey = $data['env_key'] ?? $envSync->deriveEnvKey($secretKey);
        $sync = $envSync->upsert($tenant, $envKey, $secretValue);

        return response()->json([
            'success' => $sync['success'],
            'data' => $sync,
        ], $sync['success'] ? 200 : 422);
    }

    public function removeFromEnv(Request $request, Tenant $tenant, TenantEnvSyncService $envSync)
    {
        $this->authorizeAccess($request, $tenant);

        $data = $request->validate([
            'env_key' => ['required', 'string', 'max:120', 'regex:/^[A-Z][A-Z0-9_]*$/'],
            'confirm' => ['required', Rule::in(['DELETE_ENV_KEY'])],
        ]);

        $result = $envSync->remove($tenant, (string) $data['env_key']);

        return response()->json([
            'success' => $result['success'],
            'data' => $result,
        ], $result['success'] ? 200 : 422);
    }

    private function authorizeAccess(Request $request, Tenant $tenant): void
    {
        abort_unless(AdminPermissions::canManageTenantInfrastructure($request->user()), 403);
        if ($request->user() && !AdminPermissions::isSuperadmin($request->user()) && $request->user()->tenant_id !== $tenant->id) {
            abort(403);
        }
    }
}
