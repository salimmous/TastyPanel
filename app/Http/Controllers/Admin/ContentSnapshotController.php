<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentSnapshot;
use App\Services\ContentSnapshotService;
use App\Support\AdminEnvironmentResolver;
use App\Support\AdminPermissions;
use App\Support\AdminTenantResolver;
use Illuminate\Http\Request;

class ContentSnapshotController extends Controller
{
    public function __construct(private ContentSnapshotService $snapshots)
    {
    }

    public function index(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $environment = $this->resolveEnvironment($request);

        $query = ContentSnapshot::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->orderByDesc('id');

        $limit = (int) $request->query('limit', 20);
        $limit = max(1, min(100, $limit));

        return response()->json([
            'data' => $query->limit($limit)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'environment' => ['nullable', 'string', 'in:production,staging,preview'],
        ]);

        $environment = $data['environment'] ?? $this->resolveEnvironment($request);
        $snapshot = $this->snapshots->createSnapshot(
            $tenantId,
            $environment,
            $request->user()?->id,
            $data['label'] ?? null,
            $data['description'] ?? null
        );

        return response()->json([
            'data' => $snapshot,
        ], 201);
    }

    public function restore(Request $request, ContentSnapshot $snapshot)
    {
        $tenantId = $this->resolveTenantId($request);
        if ($snapshot->tenant_id !== $tenantId) {
            abort(404);
        }

        $data = $request->validate([
            'target_environment' => ['required', 'string', 'in:production,staging,preview'],
        ]);

        $this->snapshots->restoreSnapshot($snapshot, $data['target_environment']);

        return response()->json([
            'data' => $snapshot->fresh(),
        ]);
    }

    public function destroy(Request $request, ContentSnapshot $snapshot)
    {
        $tenantId = $this->resolveTenantId($request);
        if ($snapshot->tenant_id !== $tenantId) {
            abort(404);
        }

        $snapshot->delete();

        return response()->json([
            'message' => 'Snapshot deleted',
        ]);
    }

    private function resolveTenantId(Request $request): int
    {
        $user = $request->user();
        if (!AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }

        $tenantId = AdminTenantResolver::resolveId($request);
        if (!$tenantId) {
            abort(422, 'Tenant required.');
        }

        return $tenantId;
    }

    private function resolveEnvironment(Request $request): string
    {
        $value = $request->query('environment') ?? $request->input('environment');
        if ($value) {
            $value = strtolower(trim((string) $value));
            if (in_array($value, ['production', 'staging', 'preview'], true)) {
                return $value;
            }
        }

        return AdminEnvironmentResolver::resolve($request);
    }
}
