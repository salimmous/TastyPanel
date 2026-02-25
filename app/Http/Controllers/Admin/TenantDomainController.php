<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\DomainNginxVersion;
use App\Models\Tenant;
use App\Services\Http3HealthService;
use App\Services\NginxProvisioningService;
use App\Services\ProvisioningService;
use App\Services\SslProvisioningService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;

class TenantDomainController extends Controller
{
    public function __construct(
        private ProvisioningService $provisioning,
        private SslProvisioningService $sslProvisioning,
        private NginxProvisioningService $nginxProvisioning,
        private Http3HealthService $http3Health
    ) {}

    public function store(Request $request, Tenant $tenant)
    {
        $user = $request->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $tenant->id) {
            abort(403);
        }
        $data = $request->validate([
            'hostname' => ['required', 'string', 'max:255', 'unique:domains,hostname'],
            'is_primary' => ['nullable', 'boolean'],
            'zone_id' => ['nullable', 'string', 'max:255'],
            'environment' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['production', 'staging', 'preview'])],
        ]);

        $hostname = $this->normalizeHost($data['hostname']);
        $environment = $data['environment'] ?? 'production';
        $isPrimary = $environment === 'production' ? ($data['is_primary'] ?? false) : false;

        $domain = $tenant->domains()->create([
            'hostname' => $hostname,
            'is_primary' => $isPrimary,
            'status' => 'pending',
            'cf_zone_id' => $data['zone_id'] ?? null,
            'environment' => $environment,
        ]);

        if ($isPrimary) {
            Domain::where('tenant_id', $tenant->id)
                ->where('id', '!=', $domain->id)
                ->where('environment', 'production')
                ->update(['is_primary' => false]);
        }

        $this->provisioning->provisionDomain($domain);

        return response()->json([
            'data' => $domain,
        ], 201);
    }

    public function provision(Domain $domain)
    {
        $user = request()->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $domain->tenant_id) {
            abort(403);
        }
        $domain = $this->provisioning->provisionDomain($domain);
        if ($domain->http3_enabled) {
            $domain = $this->http3Health->check($domain);
        }

        return response()->json([
            'data' => $domain,
        ]);
    }

    public function destroy(Domain $domain)
    {
        $user = request()->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $domain->tenant_id) {
            abort(403);
        }
        $domain->delete();

        return response()->json([
            'message' => 'Domain deleted',
        ]);
    }

    public function ssl(Domain $domain)
    {
        $user = request()->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $domain->tenant_id) {
            abort(403);
        }

        $certificate = $this->sslProvisioning->provisionCertificate($domain, true);

        return response()->json([
            'data' => $certificate,
        ]);
    }

    public function nginx(Domain $domain)
    {
        $user = request()->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $domain->tenant_id) {
            abort(403);
        }

        $domain = $this->nginxProvisioning->provisionDomain($domain, true);
        if ($domain->http3_enabled) {
            $domain = $this->http3Health->check($domain);
        }

        return response()->json([
            'data' => $domain,
        ]);
    }

    public function config(Domain $domain)
    {
        $user = request()->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $domain->tenant_id) {
            abort(403);
        }

        return response()->json([
            'data' => [
                'effective_config' => $this->nginxProvisioning->renderConfig($domain),
                'default_config' => $this->nginxProvisioning->renderDefaultConfig($domain),
                'custom_config' => $domain->nginx_custom_config,
                'is_custom' => ! empty($domain->nginx_custom_config),
                'http3_enabled' => (bool) $domain->http3_enabled,
                'http3_status' => $domain->http3_status,
                'http3_error' => $domain->http3_error,
                'http3_checked_at' => $domain->http3_checked_at,
                'http3_udp_status' => $domain->http3_udp_status,
                'http3_udp_error' => $domain->http3_udp_error,
                'http3_udp_checked_at' => $domain->http3_udp_checked_at,
                'ssl_status' => $domain->sslCertificate?->status ?? 'pending',
                'nginx_status' => $domain->nginx_status,
                'nginx_error' => $domain->nginx_error,
            ],
        ]);
    }

    public function updateConfig(Request $request, Domain $domain)
    {
        $user = $request->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $domain->tenant_id) {
            abort(403);
        }

        $data = $request->validate([
            'config' => ['required', 'string'],
        ]);

        $result = $this->deployConfigSafely(
            $domain,
            (string) $data['config'],
            'custom',
            $request->user()?->id
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function resetConfig(Domain $domain)
    {
        $user = request()->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $domain->tenant_id) {
            abort(403);
        }

        $result = $this->deployConfigSafely(
            $domain,
            null,
            'default',
            $user?->id
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function toggleHttp3(Request $request, Domain $domain)
    {
        $user = $request->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $domain->tenant_id) {
            abort(403);
        }

        if (! empty($domain->nginx_custom_config)) {
            return response()->json([
                'message' => 'HTTP/3 toggle is disabled when using a custom Nginx config.',
            ], 409);
        }

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $domain->http3_enabled = (bool) $data['enabled'];
        $domain->save();

        $domain = $this->nginxProvisioning->provisionDomain($domain, true);
        $domain = $this->http3Health->check($domain);

        return response()->json([
            'data' => $domain,
        ]);
    }

    public function checkHttp3(Domain $domain)
    {
        $user = request()->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $domain->tenant_id) {
            abort(403);
        }

        $domain = $this->http3Health->check($domain);

        return response()->json([
            'data' => $domain,
        ]);
    }

    public function testConfig(Request $request, Domain $domain)
    {
        $user = $request->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $domain->tenant_id) {
            abort(403);
        }

        $data = $request->validate([
            'config' => ['required', 'string'],
        ]);

        $result = $this->nginxProvisioning->testConfig($domain, $data['config']);

        return response()->json([
            'success' => $result['success'],
            'output' => $result['output'],
        ], $result['success'] ? 200 : 422);
    }

    public function versions(Domain $domain)
    {
        $user = request()->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $domain->tenant_id) {
            abort(403);
        }

        $limit = (int) request()->query('limit', 15);
        $limit = max(1, min(100, $limit));

        $versions = $domain->nginxVersions()
            ->with('creator:id,name,email')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $versions,
        ]);
    }

    public function restoreVersion(Domain $domain, DomainNginxVersion $version)
    {
        $user = request()->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }
        if ($user && ! AdminPermissions::isSuperadmin($user) && $user->tenant_id !== $domain->tenant_id) {
            abort(403);
        }
        if ($version->domain_id !== $domain->id) {
            abort(404);
        }

        $result = $this->deployConfigSafely(
            $domain,
            (string) $version->config,
            'restore',
            $user?->id
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function deployConfigSafely(Domain $domain, ?string $customConfig, string $source, ?int $userId): array
    {
        $previousCustom = $domain->nginx_custom_config;
        $targetConfig = $customConfig ?? $this->nginxProvisioning->renderDefaultConfig($domain);

        $test = $this->nginxProvisioning->testConfig($domain, $targetConfig);
        if (! $test['success']) {
            return [
                'success' => false,
                'message' => 'Nginx test failed. Configuration was not applied.',
                'output' => $test['output'],
                'data' => $domain->refresh(),
            ];
        }

        $domain->nginx_custom_config = $customConfig;
        $domain->save();

        $configPath = $this->nginxProvisioning->writeCustomConfig($domain, $targetConfig);
        $apply = $this->nginxProvisioning->applyConfig($domain, $configPath);
        $domain = $domain->refresh();

        if (! $apply['success']) {
            $domain->nginx_custom_config = $previousCustom;
            $domain->save();

            $rollbackConfig = $previousCustom ?? $this->nginxProvisioning->renderDefaultConfig($domain->refresh());
            $rollbackPath = $this->nginxProvisioning->writeCustomConfig($domain, $rollbackConfig);
            $rollbackApply = $this->nginxProvisioning->applyConfig($domain, $rollbackPath);

            return [
                'success' => false,
                'message' => 'Deploy failed. Previous config restored.',
                'output' => $apply['output'],
                'rollback_output' => $rollbackApply['output'] ?? null,
                'data' => $domain->refresh(),
            ];
        }

        $domain->nginxVersions()->create([
            'config' => $targetConfig,
            'source' => $source,
            'created_by' => $userId,
        ]);

        return [
            'success' => true,
            'message' => 'Config deployed safely.',
            'output' => $apply['output'],
            'data' => $domain->refresh(),
        ];
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('#^https?://#', '', $host);

        return rtrim($host, '/');
    }
}
