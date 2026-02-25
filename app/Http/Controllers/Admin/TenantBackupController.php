<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantBackupRun;
use App\Services\TenantBackupRestoreService;
use App\Services\TenantBackupService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantBackupController extends Controller
{
    public function index(Request $request, Tenant $tenant)
    {
        $this->authorizeTenant($request, $tenant);

        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min($limit, 50));

        $runs = TenantBackupRun::where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $runs,
        ]);
    }

    public function store(Request $request, Tenant $tenant, TenantBackupService $backupService)
    {
        $this->authorizeTenant($request, $tenant);

        $run = $backupService->run($tenant, $request->user()?->id, 'manual');

        return response()->json([
            'data' => $run,
        ]);
    }

    public function updateSettings(Request $request, Tenant $tenant)
    {
        $this->authorizeTenant($request, $tenant);

        $data = $request->validate([
            'backup_enabled' => ['nullable', 'boolean'],
            'backup_interval_hours' => ['nullable', 'integer', 'min:0'],
            'backup_retention_days' => ['nullable', 'integer', 'min:0'],
            'backup_s3_enabled' => ['nullable', 'boolean'],
            'backup_keep_local' => ['nullable', 'boolean'],
            'backup_s3_prefix' => ['nullable', 'string', 'max:255'],
        ]);

        $tenant->fill($data);
        $tenant->save();

        return response()->json([
            'data' => $tenant->refresh(),
        ]);
    }

    public function download(Request $request, Tenant $tenant, TenantBackupRun $backup)
    {
        $this->authorizeTenant($request, $tenant);
        if ($backup->tenant_id !== $tenant->id) {
            abort(404);
        }
        if ($backup->status !== 'completed') {
            abort(409, 'Backup is not completed yet.');
        }

        $localZip = $backup->path ? $backup->path.'/backup.zip' : null;
        if ($localZip && file_exists($localZip)) {
            return response()->download($localZip, "tenant-{$tenant->id}-backup-{$backup->id}.zip");
        }

        if ($backup->disk === 's3' && $backup->remote_path) {
            $stream = Storage::disk('s3')->readStream($backup->remote_path);
            if (! $stream) {
                abort(404);
            }

            return new StreamedResponse(function () use ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="tenant-'.$tenant->id.'-backup-'.$backup->id.'.zip"',
            ]);
        }

        abort(404);
    }

    public function restore(Request $request, Tenant $tenant, TenantBackupRun $backup, TenantBackupRestoreService $restoreService)
    {
        $this->authorizeTenant($request, $tenant);
        if ($backup->tenant_id !== $tenant->id) {
            abort(404);
        }
        if ($backup->status !== 'completed') {
            return response()->json(['message' => 'Backup is not completed yet.'], 409);
        }

        $data = $request->validate([
            'confirm' => ['required', 'boolean'],
        ]);
        if (! $data['confirm']) {
            return response()->json(['message' => 'Confirmation required.'], 422);
        }

        $restore = $restoreService->restore($tenant, $backup, $request->user()?->id);

        return response()->json([
            'data' => $restore,
        ]);
    }

    private function authorizeTenant(Request $request, Tenant $tenant): void
    {
        $user = $request->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $tenant->id) {
            abort(403);
        }
    }
}
