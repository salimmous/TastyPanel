<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\ContentSnapshotService;
use App\Support\AdminPermissions;
use App\Support\AdminTenantResolver;
use Illuminate\Http\Request;

class PreviewController extends Controller
{
    public function show(Request $request)
    {
        $tenant = $this->resolveTenant($request);

        return response()->json([
            'data' => $this->payload($tenant),
        ]);
    }

    public function enable(Request $request)
    {
        $tenant = $this->resolveTenant($request);

        $tenant->preview_enabled = true;
        if (!$tenant->preview_theme_id) {
            $tenant->preview_theme_id = $tenant->theme_id;
        }
        $tenant->save();

        $productionSettings = $tenant->settings?->data ?? [];
        $tenant->previewSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'preview'],
            ['data' => $productionSettings]
        );

        app(ContentSnapshotService::class)->syncEnvironment($tenant->id, 'production', 'preview');

        return response()->json([
            'data' => $this->payload($tenant->refresh()),
        ]);
    }

    public function update(Request $request)
    {
        $tenant = $this->resolveTenant($request);
        $data = $request->validate([
            'production_theme_id' => ['nullable', 'exists:themes,id'],
            'preview_theme_id' => ['nullable', 'exists:themes,id'],
            'preview_enabled' => ['nullable', 'boolean'],
            'preview_settings' => ['nullable', 'array'],
        ]);

        if (array_key_exists('production_theme_id', $data)) {
            $tenant->theme_id = $data['production_theme_id'];
        }
        if (array_key_exists('preview_theme_id', $data)) {
            $tenant->preview_theme_id = $data['preview_theme_id'];
        }
        if (array_key_exists('preview_enabled', $data)) {
            $tenant->preview_enabled = (bool) $data['preview_enabled'];
        }
        $tenant->save();

        if (array_key_exists('preview_settings', $data)) {
            $tenant->previewSettings()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'environment' => 'preview'],
                ['data' => $data['preview_settings'] ?? []]
            );
        }

        return response()->json([
            'data' => $this->payload($tenant->refresh()),
        ]);
    }

    public function sync(Request $request)
    {
        $tenant = $this->resolveTenant($request);

        $tenant->preview_theme_id = $tenant->theme_id;
        $tenant->preview_enabled = true;
        $tenant->save();

        $productionSettings = $tenant->settings?->data ?? [];
        $tenant->previewSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'preview'],
            ['data' => $productionSettings]
        );

        app(ContentSnapshotService::class)->syncEnvironment($tenant->id, 'production', 'preview');

        return response()->json([
            'data' => $this->payload($tenant->refresh()),
        ]);
    }

    public function promote(Request $request)
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant->preview_theme_id) {
            $tenant->theme_id = $tenant->preview_theme_id;
        }

        $previewSettings = $tenant->previewSettings?->data ?? [];
        $tenant->settings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'production'],
            ['data' => $previewSettings]
        );

        $tenant->save();

        app(ContentSnapshotService::class)->syncEnvironment($tenant->id, 'preview', 'production');

        return response()->json([
            'data' => $this->payload($tenant->refresh()),
        ]);
    }

    private function resolveTenant(Request $request): Tenant
    {
        $user = $request->user();
        if (!AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }

        $tenantId = AdminTenantResolver::resolveId($request);
        if (!$tenantId) {
            abort(422, 'Tenant required.');
        }

        $tenant = Tenant::with([
            'theme',
            'previewTheme',
            'settings',
            'previewSettings',
            'domains',
            'primaryDomain',
        ])->findOrFail($tenantId);

        if ($user && !AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $tenant->id) {
            abort(403);
        }

        return $tenant;
    }

    private function payload(Tenant $tenant): array
    {
        $previewDomains = $tenant->domains->where('environment', 'preview')->values();

        return [
            'tenant' => $tenant->only(['id', 'name', 'slug', 'preview_enabled']),
            'production' => [
                'theme' => $tenant->theme,
                'settings' => $tenant->settings?->data ?? [],
                'primary_domain' => $tenant->primaryDomain,
            ],
            'preview' => [
                'theme' => $tenant->previewTheme,
                'settings' => $tenant->previewSettings?->data ?? [],
                'domains' => $previewDomains,
            ],
        ];
    }
}
