<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTenantProvisioningJob;
use App\Models\Domain;
use App\Models\ProvisioningJob;
use App\Models\Tenant;
use App\Services\ProvisioningService;
use App\Services\InstanceProvisioningService;
use App\Services\TenantOrchestrationService;
use App\Services\TenantCloneService;
use App\Services\TenantAccessService;
use App\Services\BlueprintService;
use App\Support\AdminTenantResolver;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function __construct(private ProvisioningService $provisioning)
    {
    }

    public function provisionInstance(Request $request, Tenant $tenant, InstanceProvisioningService $instances)
    {
        abort_unless(AdminPermissions::canManageTenantInfrastructure($request->user()), 403);
        if ($request->user() && !AdminPermissions::isSuperadmin($request->user()) && $request->user()->tenant_id !== $tenant->id) {
            abort(403);
        }

        $domainId = $request->input('domain_id');
        $domain = null;
        if ($domainId) {
            $domain = $tenant->domains()->whereKey($domainId)->first();
        }
        if (!$domain) {
            $domain = $tenant->domains()->where('is_primary', true)->where('environment', 'production')->first();
        }

        $tenant = $instances->provisionTenant($tenant, $domain);

        return response()->json([
            'data' => $tenant->load(['theme', 'stagingTheme', 'previewTheme', 'domains.sslCertificate', 'settings', 'stagingSettings', 'previewSettings', 'activeSubscription.plan']),
        ]);
    }

    public function orchestrationStatus(Request $request, Tenant $tenant, TenantOrchestrationService $orchestration)
    {
        abort_unless(AdminPermissions::canManageTenantInfrastructure($request->user()), 403);
        if ($request->user() && !AdminPermissions::isSuperadmin($request->user()) && $request->user()->tenant_id !== $tenant->id) {
            abort(403);
        }

        return response()->json([
            'data' => $orchestration->status($tenant),
        ]);
    }

    public function orchestrate(Request $request, Tenant $tenant, TenantOrchestrationService $orchestration)
    {
        abort_unless(AdminPermissions::canManageTenantInfrastructure($request->user()), 403);
        if ($request->user() && !AdminPermissions::isSuperadmin($request->user()) && $request->user()->tenant_id !== $tenant->id) {
            abort(403);
        }

        $data = $request->validate([
            'action' => ['required', 'string', Rule::in(['start', 'stop', 'restart'])],
        ]);

        $result = $orchestration->runAction($tenant, $data['action']);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['output'] ?: 'Orchestration failed.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['output'] ?: 'Action completed.',
            'status' => $orchestration->status($tenant),
        ]);
    }

    public function index()
    {
        $user = request()->user();
        $query = Tenant::with(['theme', 'stagingTheme', 'previewTheme', 'domains.sslCertificate', 'settings', 'stagingSettings', 'previewSettings', 'activeSubscription.plan'])
            ->orderByDesc('created_at');
        $query->with('latestProvisioningJob');

        if ($user && !AdminPermissions::isSuperadmin($user)) {
            $query->where('id', $user->tenant_id);
        }

        if ($user && AdminPermissions::isSuperadmin($user)) {
            $tenantId = AdminTenantResolver::resolveId(request());
            if ($tenantId) {
                $query->where('id', $tenantId);
            }
        }

        $tenants = $query->get();

        return response()->json([
            'data' => $tenants,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(AdminPermissions::canManageTenants($request->user()), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:tenants,slug'],
            'theme_id' => ['nullable', 'exists:themes,id'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'archived'])],
            'domain' => ['required', 'string', 'max:255', 'unique:domains,hostname'],
            'domain_zone_id' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
            'blueprint_id' => ['nullable', 'string', 'max:120'],
        ]);

        $slug = $data['slug'] ?? $this->uniqueSlug($data['name']);

        $blueprintService = app(BlueprintService::class);
        $blueprint = $data['blueprint_id'] ? $blueprintService->resolve($data['blueprint_id']) : null;
        $settings = $data['settings'] ?? [];
        if ($blueprint) {
            $blueprintSettings = $blueprint['settings'] ?? [];
            $settings = array_replace_recursive($blueprintSettings, $settings);

            if (!empty($blueprint['automation'])) {
                $settings['automation'] = array_replace_recursive(
                    $blueprint['automation'],
                    $settings['automation'] ?? []
                );
            }
        }

        $tenant = Tenant::create([
            'name' => $data['name'],
            'slug' => $slug,
            // Never auto-attach a theme unless explicitly provided by the admin.
            'theme_id' => $data['theme_id'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        $tenant->settings()->create([
            'environment' => 'production',
            'data' => $settings,
        ]);
        $tenant->securityProfile()->firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'mode' => 'block',
                'waf_enabled' => true,
                'waf_mode' => 'block',
                'waf_block_sqli' => true,
                'waf_block_xss' => true,
                'waf_block_lfi' => true,
                'updated_by' => $request->user()?->id,
            ]
        );

        $hostname = $this->normalizeHost($data['domain']);

        $domain = $tenant->domains()->create([
            'hostname' => $hostname,
            'is_primary' => true,
            'status' => 'pending',
            'cf_zone_id' => $data['domain_zone_id'] ?? null,
            'environment' => 'production',
        ]);

        $provisioningJob = null;
        $message = 'Tenant created. Provisioning is disabled by default.';
        if ((bool) config('services.provisioning.auto_on_create', false)) {
            $provisioningJob = $this->enqueueProvisioning($tenant, $domain, 'create');
            $message = 'Tenant created. Provisioning is running in queue.';
        }

        return response()->json([
            'data' => $tenant->load(['theme', 'stagingTheme', 'previewTheme', 'domains', 'settings', 'stagingSettings', 'previewSettings', 'latestProvisioningJob']),
            'provisioning_job' => $provisioningJob,
            'message' => $message,
        ], 201);
    }

    public function show(Tenant $tenant)
    {
        return response()->json([
            'data' => $tenant->load(['theme', 'stagingTheme', 'previewTheme', 'domains', 'settings', 'stagingSettings', 'previewSettings', 'activeSubscription.plan', 'latestProvisioningJob']),
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        abort_unless(AdminPermissions::canManageTenants($request->user()), 403);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['sometimes', 'string', 'max:120', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'theme_id' => ['sometimes', 'nullable', 'exists:themes,id'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'archived'])],
            'settings' => ['sometimes', 'array'],
        ]);

        $tenant->fill($data);
        $tenant->save();

        if (array_key_exists('settings', $data)) {
            $tenant->settings()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'environment' => 'production'],
                ['data' => $data['settings']]
            );
        }

        return response()->json([
            'data' => $tenant->load(['theme', 'stagingTheme', 'previewTheme', 'domains', 'settings', 'stagingSettings', 'previewSettings', 'latestProvisioningJob']),
        ]);
    }

    public function destroy(Tenant $tenant, InstanceProvisioningService $instances, TenantAccessService $tenantAccess)
    {
        abort_unless(AdminPermissions::canManageTenants(request()->user()), 403);

        $cleanup = $instances->deprovisionTenant($tenant);
        $accessCleanup = $cleanup['access'] ?? $tenantAccess->removeAccess($tenant);
        $tenant->delete();

        return response()->json([
            'message' => 'Tenant deleted',
            'frontend_cleanup' => $cleanup,
            'access_cleanup' => $accessCleanup,
        ]);
    }

    public function archive(Request $request, Tenant $tenant)
    {
        abort_unless(AdminPermissions::canManageTenants($request->user()), 403);
        $tenant->status = 'archived';
        $tenant->save();

        return response()->json([
            'data' => $tenant->load(['theme', 'stagingTheme', 'previewTheme', 'domains', 'settings', 'stagingSettings', 'previewSettings', 'latestProvisioningJob']),
        ]);
    }

    public function unarchive(Request $request, Tenant $tenant)
    {
        abort_unless(AdminPermissions::canManageTenants($request->user()), 403);
        $tenant->status = 'active';
        $tenant->save();

        return response()->json([
            'data' => $tenant->load(['theme', 'stagingTheme', 'domains', 'settings', 'stagingSettings', 'latestProvisioningJob']),
        ]);
    }

    public function clone(Request $request, Tenant $tenant, InstanceProvisioningService $instances, TenantCloneService $cloner)
    {
        abort_unless(AdminPermissions::canManageTenants($request->user()), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:tenants,slug'],
            'domain' => ['required', 'string', 'max:255', 'unique:domains,hostname'],
            'domain_zone_id' => ['nullable', 'string', 'max:255'],
            'theme_id' => ['nullable', 'exists:themes,id'],
            'copy_settings' => ['nullable', 'boolean'],
            'copy_staging' => ['nullable', 'boolean'],
            'full_clone' => ['nullable', 'boolean'],
        ]);

        $source = $tenant->load(['settings', 'stagingSettings', 'previewSettings', 'theme', 'stagingTheme', 'previewTheme']);
        $slug = $data['slug'] ?? $this->uniqueSlug($data['name']);

        $newTenant = Tenant::create([
            'name' => $data['name'],
            'slug' => $slug,
            'theme_id' => $data['theme_id'] ?? $source->theme_id,
            'staging_theme_id' => $source->staging_theme_id,
            'preview_theme_id' => $source->preview_theme_id,
            'status' => 'active',
            'staging_enabled' => $source->staging_enabled,
            'preview_enabled' => $source->preview_enabled,
        ]);

        $copySettings = $data['copy_settings'] ?? true;
        $copyStaging = $data['copy_staging'] ?? true;

        $newTenant->settings()->create([
            'environment' => 'production',
            'data' => $copySettings ? ($source->settings?->data ?? []) : [],
        ]);

        if ($copyStaging && $source->stagingSettings) {
            $newTenant->stagingSettings()->create([
                'environment' => 'staging',
                'data' => $source->stagingSettings->data ?? [],
            ]);
        }

        if ($copyStaging && $source->previewSettings) {
            $newTenant->previewSettings()->create([
                'environment' => 'preview',
                'data' => $source->previewSettings->data ?? [],
            ]);
        }

        $hostname = $this->normalizeHost($data['domain']);
        $domain = $newTenant->domains()->create([
            'hostname' => $hostname,
            'is_primary' => true,
            'status' => 'pending',
            'cf_zone_id' => $data['domain_zone_id'] ?? null,
            'environment' => 'production',
        ]);

        $this->provisioning->provisionDomain($domain);

        if (!empty($data['full_clone'])) {
            if ($tenant->instance_status !== 'ready') {
                return response()->json([
                    'message' => 'Source instance must be ready before cloning.',
                ], 422);
            }

            $newTenant->instance_status = 'cloning';
            $newTenant->instance_last_error = null;
            $newTenant->save();

            $newTenant = $instances->provisionTenant($newTenant, $domain);
            if ($newTenant->instance_status !== 'ready') {
                return response()->json([
                    'message' => 'Target instance provisioning failed.',
                    'data' => $newTenant->load(['theme', 'stagingTheme', 'previewTheme', 'domains', 'settings', 'stagingSettings', 'previewSettings']),
                ], 422);
            }

            $result = $cloner->cloneInstance($tenant, $newTenant, $domain);
            if (!$result['success']) {
                $newTenant->instance_status = 'error';
                $newTenant->instance_last_error = $result['output'] ?: 'Clone failed.';
                $newTenant->save();
                return response()->json([
                    'message' => 'Clone failed.',
                    'data' => $newTenant->load(['theme', 'stagingTheme', 'previewTheme', 'domains', 'settings', 'stagingSettings', 'previewSettings']),
                ], 422);
            }

            $newTenant->instance_status = 'ready';
            $newTenant->instance_last_error = null;
            $newTenant->save();
        }

        return response()->json([
            'data' => $newTenant->load(['theme', 'stagingTheme', 'previewTheme', 'domains', 'settings', 'stagingSettings', 'previewSettings']),
        ], 201);
    }

    public function provisioningJobs(Request $request, Tenant $tenant)
    {
        $this->authorizeProvisioningAccess($request, $tenant);

        $limit = (int) $request->query('limit', 20);
        $limit = max(1, min($limit, 100));

        $jobs = $tenant->provisioningJobs()->latest('id')->limit($limit)->get();

        return response()->json([
            'data' => $jobs,
        ]);
    }

    public function retryProvisioning(Request $request, Tenant $tenant)
    {
        $this->authorizeProvisioningAccess($request, $tenant);

        $activeJob = $tenant->provisioningJobs()
            ->whereIn('status', ['queued', 'running'])
            ->latest('id')
            ->first();
        if ($activeJob) {
            return response()->json([
                'message' => 'Provisioning already in progress.',
                'data' => $activeJob,
            ], 422);
        }

        $domain = $this->resolveProvisioningDomain($tenant, $request->input('domain_id'));
        if (!$domain) {
            return response()->json([
                'message' => 'No domain found for provisioning.',
            ], 422);
        }

        $latest = $tenant->provisioningJobs()->latest('id')->first();
        $job = $this->enqueueProvisioning($tenant, $domain, 'retry', $latest?->id);

        return response()->json([
            'message' => 'Provisioning retry queued.',
            'data' => $job,
            'tenant' => $tenant->load(['domains.sslCertificate', 'latestProvisioningJob']),
        ], 202);
    }

    public function rollbackProvisioning(Request $request, Tenant $tenant)
    {
        $this->authorizeProvisioningAccess($request, $tenant);

        $domain = $this->resolveProvisioningDomain($tenant, $request->input('domain_id'));
        if (!$domain) {
            return response()->json([
                'message' => 'No domain found for rollback.',
            ], 422);
        }

        $job = $tenant->provisioningJobs()->create([
            'status' => 'running',
            'message' => 'Rollback started.',
            'meta' => [
                'action' => 'rollback',
                'domain_id' => $domain->id,
                'step' => 'rollback_started',
            ],
            'started_at' => now(),
        ]);

        $result = $this->provisioning->rollbackDomain($domain);
        $job->status = $result['success'] ? 'rolled_back' : 'failed';
        $job->message = $result['success']
            ? 'Rollback completed.'
            : implode(' | ', $result['errors'] ?: ['Rollback failed.']);
        $job->meta = array_merge($job->meta ?? [], [
            'step' => $result['success'] ? 'rollback_done' : 'rollback_failed',
            'result' => $result,
        ]);
        $job->finished_at = now();
        $job->save();

        return response()->json([
            'success' => $result['success'],
            'data' => $job,
            'tenant' => $tenant->load(['domains.sslCertificate', 'latestProvisioningJob']),
            'rollback' => $result,
        ], $result['success'] ? 200 : 422);
    }

    public function accessInfo(Request $request, Tenant $tenant, TenantAccessService $tenantAccess)
    {
        $this->authorizeProvisioningAccess($request, $tenant);

        return response()->json([
            'data' => $tenantAccess->connectionInfo($tenant),
        ]);
    }

    public function provisionAccess(Request $request, Tenant $tenant, TenantAccessService $tenantAccess)
    {
        $this->authorizeProvisioningAccess($request, $tenant);

        $result = $tenantAccess->ensureAccess($tenant);
        if (!$result['success']) {
            return response()->json([
                'message' => $result['output'] ?: 'Tenant access provisioning failed.',
            ], 422);
        }

        $meta = $result['meta'] ?? [];
        $connectionInfo = $tenantAccess->connectionInfo($tenant->fresh());
        return response()->json([
            'data' => [
                'user' => $meta['SSH_USER'] ?? $tenant->instance_ssh_user,
                'home' => $meta['SSH_HOME'] ?? $tenant->instance_ssh_home,
                'port' => (int) ($meta['SSH_PORT'] ?? ($tenant->instance_ssh_port ?: 22)),
                'password' => $meta['TEMP_PASSWORD'] ?? null,
                'auth_mode' => $meta['AUTH_MODE'] ?? ($connectionInfo['auth_mode'] ?? 'both'),
                'sftp_only' => (string) ($meta['SFTP_ONLY'] ?? (($connectionInfo['sftp_only'] ?? false) ? '1' : '0')) === '1',
            ],
        ]);
    }

    public function rotateAccessPassword(Request $request, Tenant $tenant, TenantAccessService $tenantAccess)
    {
        $this->authorizeProvisioningAccess($request, $tenant);

        $result = $tenantAccess->rotatePassword($tenant);
        if (!$result['success']) {
            return response()->json([
                'message' => $result['output'] ?: 'Password rotation failed.',
            ], 422);
        }

        $meta = $result['meta'] ?? [];
        $connectionInfo = $tenantAccess->connectionInfo($tenant->fresh());
        return response()->json([
            'data' => [
                'user' => $meta['SSH_USER'] ?? $tenant->instance_ssh_user,
                'port' => (int) ($meta['SSH_PORT'] ?? ($tenant->instance_ssh_port ?: 22)),
                'password' => $meta['TEMP_PASSWORD'] ?? null,
                'auth_mode' => $meta['AUTH_MODE'] ?? ($connectionInfo['auth_mode'] ?? 'both'),
                'sftp_only' => (string) ($meta['SFTP_ONLY'] ?? (($connectionInfo['sftp_only'] ?? false) ? '1' : '0')) === '1',
            ],
        ]);
    }

    public function installAccessKey(Request $request, Tenant $tenant, TenantAccessService $tenantAccess)
    {
        $this->authorizeProvisioningAccess($request, $tenant);

        $data = $request->validate([
            'public_key' => ['required', 'string', 'min:40'],
        ]);

        $result = $tenantAccess->installPublicKey($tenant, trim($data['public_key']));
        if (!$result['success']) {
            return response()->json([
                'message' => $result['output'] ?: 'Failed to install SSH public key.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'installed' => true,
                'user' => $tenant->fresh()->instance_ssh_user,
            ],
        ]);
    }

    public function blueprints(Request $request, BlueprintService $blueprints)
    {
        abort_unless(AdminPermissions::canManageTenantInfrastructure($request->user()), 403);

        return response()->json([
            'data' => $blueprints->all(),
        ]);
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('#^https?://#', '', $host);
        return rtrim($host, '/');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $counter += 1;
            $slug = "{$base}-{$counter}";
        }

        return $slug;
    }

    private function authorizeProvisioningAccess(Request $request, Tenant $tenant): void
    {
        abort_unless(AdminPermissions::canManageTenantInfrastructure($request->user()), 403);
        if ($request->user() && !AdminPermissions::isSuperadmin($request->user()) && $request->user()->tenant_id !== $tenant->id) {
            abort(403);
        }
    }

    private function resolveProvisioningDomain(Tenant $tenant, mixed $domainId): ?Domain
    {
        if (!empty($domainId)) {
            $domain = $tenant->domains()->whereKey($domainId)->first();
            if ($domain) {
                return $domain;
            }
        }

        return $tenant->domains()
            ->where('is_primary', true)
            ->where('environment', 'production')
            ->first();
    }

    private function enqueueProvisioning(Tenant $tenant, Domain $domain, string $action, ?int $retryOf = null): ProvisioningJob
    {
        $existing = $tenant->provisioningJobs()
            ->whereIn('status', ['queued', 'running'])
            ->where('meta->domain_id', $domain->id)
            ->latest('id')
            ->first();
        if ($existing) {
            return $existing;
        }

        $idempotencyKey = sha1(implode('|', [
            'tenant',
            (string) $tenant->id,
            'domain',
            (string) $domain->id,
            'action',
            $action,
        ]));

        $job = $tenant->provisioningJobs()->create([
            'status' => 'queued',
            'message' => 'Provisioning queued.',
            'meta' => array_filter([
                'action' => $action,
                'domain_id' => $domain->id,
                'retry_of' => $retryOf,
                'idempotency_key' => $idempotencyKey,
                'step' => 'queued',
            ], static fn ($value) => $value !== null),
        ]);

        ProcessTenantProvisioningJob::dispatch($tenant->id, $domain->id, $job->id);

        return $job->fresh();
    }
}
