<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlatformDeployController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! PlatformInstallController::isInstalled()) {
            return redirect()->route('platform.install');
        }

        if (! Auth::check()) {
            return redirect()->route('platform.login');
        }

        if (! AdminPermissions::isSuperadmin(Auth::user())) {
            abort(403);
        }

        $tenants = Tenant::query()
            ->select(['id', 'name', 'slug', 'status', 'instance_status', 'instance_root'])
            ->orderBy('name')
            ->get();

        $recentDeploy = AuditLog::query()
            ->with('user:id,name')
            ->where('action', 'runbook')
            ->where('resource_type', 'runbook_action')
            ->where(function ($q) {
                $q->where('description', 'like', '%tenant_deploy_%')
                    ->orWhere('description', 'like', '%tenant_migrate%')
                    ->orWhere('description', 'like', '%tenant_orchestrate_%');
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('platform.deploy', [
            'tenants' => $tenants,
            'recentDeploy' => $recentDeploy,
            'lastAction' => session('runbook_action'),
            'lastActionId' => session('runbook_action_id'),
            'lastOutput' => session('runbook_output'),
            'lastSuccess' => session('runbook_success'),
        ]);
    }
}
