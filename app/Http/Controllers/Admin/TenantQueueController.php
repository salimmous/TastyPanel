<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantQueueService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class TenantQueueController extends Controller
{
    public function show(Request $request, Tenant $tenant, TenantQueueService $queue)
    {
        $this->authorizeTenant($request, $tenant);

        return response()->json([
            'data' => $queue->stats($tenant),
        ]);
    }

    public function restart(Request $request, Tenant $tenant, TenantQueueService $queue)
    {
        $this->authorizeTenant($request, $tenant);

        return $this->respond($queue->restart($tenant));
    }

    public function flushFailed(Request $request, Tenant $tenant, TenantQueueService $queue)
    {
        $this->authorizeTenant($request, $tenant);

        return $this->respond($queue->flushFailed($tenant));
    }

    public function retryFailed(Request $request, Tenant $tenant, TenantQueueService $queue)
    {
        $this->authorizeTenant($request, $tenant);

        return $this->respond($queue->retryFailed($tenant));
    }

    private function respond(array $result)
    {
        if (!($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['output'] ?? 'Queue action failed.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['output'] ?? 'Queue action completed.',
        ]);
    }

    private function authorizeTenant(Request $request, Tenant $tenant): void
    {
        $user = $request->user();
        if (!AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && !AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $tenant->id) {
            abort(403);
        }
    }
}
