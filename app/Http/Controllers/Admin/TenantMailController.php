<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantMailbox;
use App\Services\TenantMailboxService;
use App\Services\TenantMailService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantMailController extends Controller
{
    public function settings(Request $request, Tenant $tenant, TenantMailService $mailService)
    {
        $this->authorizeAccess($request, $tenant);

        return response()->json([
            'data' => $mailService->settingsPayload($tenant),
        ]);
    }

    public function updateSettings(Request $request, Tenant $tenant, TenantMailService $mailService)
    {
        $this->authorizeAccess($request, $tenant);

        $data = $request->validate([
            'mail_driver' => ['nullable', Rule::in(['smtp'])],
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:500'],
            'mail_encryption' => ['nullable', Rule::in(['tls', 'ssl', 'none'])],
            'mail_from_address' => ['required', 'email', 'max:190'],
            'mail_from_name' => ['nullable', 'string', 'max:190'],
            'mail_local_enabled' => ['nullable', 'boolean'],
            'mail_provider' => ['nullable', 'string', 'max:32'],
            'mail_daily_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'mail_per_minute_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'mail_configured' => ['nullable', 'boolean'],
        ]);

        $tenant = $mailService->updateTenantSettings($tenant, $data);

        return response()->json([
            'data' => $mailService->settingsPayload($tenant),
            'message' => 'Tenant mail settings updated.',
        ]);
    }

    public function test(Request $request, Tenant $tenant, TenantMailService $mailService)
    {
        $this->authorizeAccess($request, $tenant);

        $data = $request->validate([
            'to_email' => ['required', 'email', 'max:190'],
        ]);

        $result = $mailService->sendTestEmailWithResult($tenant, $data['to_email']);
        unset($result['exception']);

        return response()->json([
            'success' => (bool) ($result['success'] ?? false),
            'data' => $result,
        ], ($result['success'] ?? false) ? 200 : 422);
    }

    public function mailboxes(Request $request, Tenant $tenant, TenantMailboxService $mailboxes)
    {
        $this->authorizeAccess($request, $tenant);

        return response()->json([
            'data' => $mailboxes->listForTenant($tenant),
        ]);
    }

    public function createMailbox(Request $request, Tenant $tenant, TenantMailboxService $mailboxes)
    {
        $this->authorizeAccess($request, $tenant);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'quota_mb' => ['nullable', 'integer', 'min:128', 'max:1048576'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        $result = $mailboxes->createMailbox($tenant, $data);
        if (! ($result['success'] ?? false)) {
            return response()->json([
                'message' => $result['output'] ?? 'Failed to create mailbox.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'mailbox' => $result['mailbox'],
                'password' => $result['password'] ?? null,
            ],
        ], 201);
    }

    public function resetMailboxPassword(Request $request, Tenant $tenant, TenantMailbox $mailbox, TenantMailboxService $mailboxes)
    {
        $this->authorizeAccess($request, $tenant);
        abort_unless((int) $mailbox->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        $result = $mailboxes->resetPassword($tenant, $mailbox, $data['password'] ?? null);
        if (! ($result['success'] ?? false)) {
            return response()->json([
                'message' => $result['output'] ?? 'Failed to reset mailbox password.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'mailbox_id' => $mailbox->id,
                'password' => $result['password'] ?? null,
            ],
        ]);
    }

    public function deleteMailbox(Request $request, Tenant $tenant, TenantMailbox $mailbox, TenantMailboxService $mailboxes)
    {
        $this->authorizeAccess($request, $tenant);
        abort_unless((int) $mailbox->tenant_id === (int) $tenant->id, 404);

        $result = $mailboxes->deleteMailbox($tenant, $mailbox);
        if (! ($result['success'] ?? false)) {
            return response()->json([
                'message' => $result['output'] ?? 'Failed to delete mailbox.',
            ], 422);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function refreshMailboxUsage(Request $request, Tenant $tenant, TenantMailbox $mailbox, TenantMailboxService $mailboxes)
    {
        $this->authorizeAccess($request, $tenant);
        abort_unless((int) $mailbox->tenant_id === (int) $tenant->id, 404);

        $result = $mailboxes->refreshUsage($tenant, $mailbox);
        if (! ($result['success'] ?? false)) {
            return response()->json([
                'message' => $result['output'] ?? 'Failed to refresh usage.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'mailbox' => $result['mailbox'] ?? null,
                'usage_bytes' => $result['usage_bytes'] ?? null,
            ],
        ]);
    }

    public function events(Request $request, Tenant $tenant)
    {
        $this->authorizeAccess($request, $tenant);

        $limit = max(1, min((int) $request->query('limit', 50), 200));
        $events = $tenant->mailEvents()->latest('id')->limit($limit)->get();

        $today = now()->startOfDay();
        $summary = [
            'sent_today' => $tenant->mailEvents()
                ->where('created_at', '>=', $today)
                ->where('status', 'success')
                ->count(),
            'failed_today' => $tenant->mailEvents()
                ->where('created_at', '>=', $today)
                ->whereIn('status', ['failed', 'throttled'])
                ->count(),
        ];

        return response()->json([
            'data' => $events,
            'summary' => $summary,
        ]);
    }

    private function authorizeAccess(Request $request, Tenant $tenant): void
    {
        abort_unless(AdminPermissions::canManageTenantInfrastructure($request->user()), 403);
        if ($request->user() && ! AdminPermissions::isSuperadmin($request->user()) && (int) $request->user()->tenant_id !== (int) $tenant->id) {
            abort(403);
        }
    }
}
