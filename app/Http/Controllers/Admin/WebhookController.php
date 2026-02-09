<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchWebhookJob;
use App\Models\Tenant;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request, Tenant $tenant)
    {
        $this->authorizeTenant($request, $tenant);

        $webhooks = Webhook::where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $webhooks,
        ]);
    }

    public function store(Request $request, Tenant $tenant)
    {
        $this->authorizeTenant($request, $tenant);

        $data = $request->validate([
            'event' => ['required', 'string', 'max:120'],
            'url' => ['required', 'string', 'max:255'],
            'secret' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $webhook = Webhook::create([
            'tenant_id' => $tenant->id,
            'event' => $data['event'],
            'url' => $data['url'],
            'secret' => $data['secret'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'data' => $webhook,
        ], 201);
    }

    public function update(Request $request, Tenant $tenant, Webhook $webhook)
    {
        $this->authorizeTenant($request, $tenant);
        if ($webhook->tenant_id !== $tenant->id) {
            abort(404);
        }

        $data = $request->validate([
            'event' => ['required', 'string', 'max:120'],
            'url' => ['required', 'string', 'max:255'],
            'secret' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $webhook->update([
            'event' => $data['event'],
            'url' => $data['url'],
            'secret' => $data['secret'] ?? null,
            'is_active' => $data['is_active'] ?? $webhook->is_active,
        ]);

        return response()->json([
            'data' => $webhook,
        ]);
    }

    public function destroy(Request $request, Tenant $tenant, Webhook $webhook)
    {
        $this->authorizeTenant($request, $tenant);
        if ($webhook->tenant_id !== $tenant->id) {
            abort(404);
        }

        $webhook->delete();

        return response()->json(['message' => 'Webhook deleted.']);
    }

    public function test(Request $request, Tenant $tenant, Webhook $webhook)
    {
        $this->authorizeTenant($request, $tenant);
        if ($webhook->tenant_id !== $tenant->id) {
            abort(404);
        }

        $payload = [
            'message' => 'Test webhook from TastyPanel',
            'tenant_id' => $tenant->id,
        ];

        try {
            $job = new DispatchWebhookJob($webhook->id, 'webhook.test', $payload);
            $job->handle();
        } catch (\Throwable $e) {
            // ignore to allow viewing delivery log
        }

        $delivery = WebhookDelivery::where('webhook_id', $webhook->id)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'data' => $delivery,
        ]);
    }

    public function deliveries(Request $request, Tenant $tenant, Webhook $webhook)
    {
        $this->authorizeTenant($request, $tenant);
        if ($webhook->tenant_id !== $tenant->id) {
            abort(404);
        }

        $deliveries = WebhookDelivery::where('webhook_id', $webhook->id)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($deliveries);
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
