<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\ContentSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StagingController extends Controller
{
    public function index($tenantId)
    {
        $tenant = $this->resolveTenant($tenantId);

        return view('platform.staging.index', compact('tenant'));
    }

    public function enable(Request $request, $tenantId)
    {
        $tenant = $this->resolveTenant($tenantId);

        $tenant->staging_enabled = true;
        if (! $tenant->staging_theme_id) {
            $tenant->staging_theme_id = $tenant->theme_id;
        }
        $tenant->save();

        $productionSettings = $tenant->settings?->data ?? [];
        $tenant->stagingSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'staging'],
            ['data' => $productionSettings]
        );

        // Optional: snapshot sync
        // app(ContentSnapshotService::class)->syncEnvironment($tenant->id, 'production', 'staging');

        return redirect()->route('platform.tenants.staging', $tenant->id)->with('success', 'Staging environment enabled.');
    }

    public function sync(Request $request, $tenantId)
    {
        $tenant = $this->resolveTenant($tenantId);
        $direction = $request->input('direction', 'prod_to_staging');

        if ($direction === 'prod_to_staging') {
            $tenant->staging_theme_id = $tenant->theme_id;
            $productionSettings = $tenant->settings?->data ?? [];
            $tenant->stagingSettings()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'environment' => 'staging'],
                ['data' => $productionSettings]
            );
            // app(ContentSnapshotService::class)->syncEnvironment($tenant->id, 'production', 'staging');
            $message = 'Production synced to Staging.';
        } else {
            // staging_to_prod (Promote)
            $tenant->theme_id = $tenant->staging_theme_id;
            $stagingSettings = $tenant->stagingSettings?->data ?? [];
            $tenant->settings()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'environment' => 'production'],
                ['data' => $stagingSettings]
            );
            // app(ContentSnapshotService::class)->syncEnvironment($tenant->id, 'staging', 'production');
            $message = 'Staging promoted to Production.';
        }

        $tenant->save();

        return redirect()->route('platform.tenants.staging', $tenant->id)->with('success', $message);
    }

    public function destroy(Request $request, $tenantId)
    {
        $tenant = $this->resolveTenant($tenantId);

        $tenant->staging_enabled = false;
        $tenant->save();

        // We generally keep the data but disable the access

        return redirect()->route('platform.tenants.staging', $tenant->id)->with('success', 'Staging environment disabled.');
    }

    private function resolveTenant($id): Tenant
    {
        if (! Auth::check()) {
            abort(403); // Should be handled by middleware but safety first
        }

        $tenant = Tenant::with([
            'theme',
            'stagingTheme',
            'settings',
            'stagingSettings',
            'domains',
        ])->findOrFail($id);

        return $tenant;
    }
}
