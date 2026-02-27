<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Tenant;
use App\Services\SslProvisioningService;
use App\Services\NginxProvisioningService;
use Illuminate\Support\Facades\Cache;

class ProvisioningService
{
    public function __construct(
        private CloudflareService $cloudflare,
        private SslProvisioningService $sslProvisioning,
        private NginxProvisioningService $nginxProvisioning,
        private InstanceProvisioningService $instances
    )
    {
    }

    public function provisionDomain(Domain $domain, ?string $targetIp = null, ?callable $progress = null, array $adminConfig = []): Domain
    {
        $result = $this->provisionDomainWithState($domain, $targetIp, $progress, $adminConfig);
        return $result['domain'];
    }

    public function provisionDomainWithState(Domain $domain, ?string $targetIp = null, ?callable $progress = null, array $adminConfig = []): array
    {
        $state = [
            'success' => false,
            'blocked' => false,
            'lock_contended' => false,
            'idempotent' => false,
            'steps' => [],
            'completed_steps' => [],
            'failed_step' => null,
            'errors' => [],
            'rollback' => [
                'performed' => false,
                'success' => null,
                'steps' => [],
                'errors' => [],
            ],
            'context' => [],
        ];

        $tenant = $domain->tenant()->first();
        if ($this->isProvisioningAlreadySatisfied($domain, $tenant)) {
            $state['success'] = true;
            $state['idempotent'] = true;
            $state['steps']['prepare'] = [
                'success' => true,
                'skipped' => true,
                'message' => 'Provisioning already satisfied.',
            ];
            $this->emitProgress($progress, 'Provisioning skipped (already satisfied).', [
                'step' => 'prepare',
                'state' => 'skipped',
                'idempotent' => true,
            ]);
            $state['domain'] = $domain->fresh();
            return $state;
        }

        $lock = $this->acquireProvisioningLock($domain);
        if ($lock === null) {
            $state['success'] = true;
            $state['blocked'] = true;
            $state['lock_contended'] = true;
            $state['steps']['lock'] = [
                'success' => true,
                'skipped' => true,
                'message' => 'Provisioning is already running for this domain.',
            ];
            $this->emitProgress($progress, 'Provisioning skipped (lock is held by another worker).', [
                'step' => 'lock',
                'state' => 'blocked',
                'lock_contended' => true,
            ]);
            $state['domain'] = $domain->fresh();
            return $state;
        }

        try {
        $domain->status = 'provisioning';
        $domain->last_error = null;
        $domain->save();

        $this->emitProgress($progress, 'Provisioning workflow started.', [
            'step' => 'prepare',
            'state' => 'running',
            'completed_steps' => $state['completed_steps'],
        ]);

        if ($tenant) {
            $this->emitProgress($progress, 'Provisioning tenant instance.', [
                'step' => 'instance',
                'state' => 'running',
                'completed_steps' => $state['completed_steps'],
            ]);

            $instanceResult = $this->instances->provisionTenantWithResult($tenant, $domain, $adminConfig);
            $tenant = $instanceResult['tenant'];

            if (!($instanceResult['success'] ?? false)) {
                $state['steps']['instance'] = [
                    'success' => false,
                    'message' => $instanceResult['output'] ?? 'Instance provisioning failed.',
                ];
                $state['failed_step'] = 'instance';
                $state['errors'][] = $state['steps']['instance']['message'];

                $rollback = $this->rollbackProvisioningSteps(
                    $domain,
                    $tenant,
                    $state['completed_steps'],
                    $state['context'],
                    $progress
                );
                $state['rollback'] = $rollback;
                $state['rollback']['performed'] = true;

                $domain->status = 'error';
                $domain->last_error = $state['steps']['instance']['message'];
                $domain->save();

                $state['domain'] = $domain->fresh();
                return $state;
            }

            $state['steps']['instance'] = [
                'success' => true,
                'message' => 'Tenant instance is ready.',
            ];
            $state['context']['instance_created'] = (bool) ($instanceResult['fresh_provisioned'] ?? false);
            $state['completed_steps'][] = 'instance';
            $domain->load('tenant'); // Refresh relationship cache
            $this->emitProgress($progress, 'Tenant instance provisioned.', [
                'step' => 'instance',
                'state' => 'done',
                'completed_steps' => $state['completed_steps'],
                'instance_created' => $state['context']['instance_created'],
            ]);
        }

        $token = config('services.cloudflare.token');
        $zoneId = $domain->cf_zone_id ?: config('services.cloudflare.zone_id');
        $ip = $targetIp ?: config('services.cloudflare.target_ip', '127.0.0.1');

        if (!$token || !$zoneId) {
            $domain->status = 'pending';
            $domain->last_error = 'Missing Cloudflare token or zone id.';
            $domain->save();

            $state['blocked'] = true;
            $state['success'] = true;
            $state['steps']['dns'] = [
                'success' => true,
                'skipped' => true,
                'message' => 'Cloudflare token/zone missing. Skipping DNS.',
            ];
            $this->emitProgress($progress, 'Cloudflare DNS skipped (missing token/zone).', [
                'step' => 'dns',
                'state' => 'skipped',
                'completed_steps' => $state['completed_steps'],
            ]);
            // $state['domain'] = $domain->fresh();
            // return $state; // DONT RETURN, CONTINUE TO NGINX
        }

        $currentStep = 'dns';

        try {
            if ($token && $zoneId) {
                $this->emitProgress($progress, 'Creating Cloudflare DNS record.', [
                    'step' => 'dns',
                    'state' => 'running',
                    'completed_steps' => $state['completed_steps'],
                ]);
                $recordId = $this->cloudflare->createARecord($zoneId, $domain->hostname, $ip, true);
                $domain->cf_record_id = $recordId;
                $domain->status = 'active';
                $domain->save();
                $state['context']['zone_id'] = $zoneId;
                $state['context']['record_id'] = $recordId;
                $state['steps']['dns'] = ['success' => true, 'message' => 'DNS record created.'];
                $state['completed_steps'][] = 'dns';
                $this->emitProgress($progress, 'Cloudflare DNS record created.', [
                    'step' => 'dns',
                    'state' => 'done',
                    'completed_steps' => $state['completed_steps'],
                ]);
            }

            if (config('services.ssl.auto')) {
                $currentStep = 'ssl';
                $this->emitProgress($progress, 'Requesting SSL certificate.', [
                    'step' => 'ssl',
                    'state' => 'running',
                    'completed_steps' => $state['completed_steps'],
                ]);
                $certificate = $this->sslProvisioning->provisionCertificate($domain);
                if ($certificate->status === 'error') {
                    throw new \RuntimeException($certificate->last_error ?: 'SSL provisioning failed.');
                }
                $state['steps']['ssl'] = ['success' => true, 'message' => 'SSL certificate issued/queued successfully.'];
                $state['completed_steps'][] = 'ssl';
                $this->emitProgress($progress, 'SSL step completed.', [
                    'step' => 'ssl',
                    'state' => 'done',
                    'completed_steps' => $state['completed_steps'],
                ]);
            }

            if (config('services.infrastructure.auto_nginx')) {
                $currentStep = 'nginx';
                $this->emitProgress($progress, 'Applying Nginx config.', [
                    'step' => 'nginx',
                    'state' => 'running',
                    'completed_steps' => $state['completed_steps'],
                ]);
                $domain = $this->nginxProvisioning->provisionDomain($domain);
                if ($domain->nginx_status === 'error') {
                    throw new \RuntimeException($domain->nginx_error ?: 'Nginx apply failed.');
                }
                $state['steps']['nginx'] = ['success' => true, 'message' => 'Nginx config applied.'];
                $state['completed_steps'][] = 'nginx';
                $this->emitProgress($progress, 'Nginx step completed.', [
                    'step' => 'nginx',
                    'state' => 'done',
                    'completed_steps' => $state['completed_steps'],
                ]);
            }
        } catch (\Throwable $e) {
            $state['failed_step'] = $currentStep;
            $state['errors'][] = $e->getMessage();
            if ($state['failed_step'] !== null) {
                $state['steps'][$state['failed_step']] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }

            $this->emitProgress($progress, 'Provisioning failed: ' . $e->getMessage(), [
                'step' => $state['failed_step'] ?: 'unknown',
                'state' => 'failed',
                'completed_steps' => $state['completed_steps'],
            ]);

            $rollback = $this->rollbackProvisioningSteps(
                $domain,
                $tenant,
                $state['completed_steps'],
                $state['context'],
                $progress
            );
            $state['rollback'] = $rollback;
            $state['rollback']['performed'] = true;

            if ($rollback['success']) {
                $domain->status = 'pending';
                $domain->last_error = 'Provisioning failed at step '
                    . ($state['failed_step'] ?: 'unknown')
                    . ' and rollback completed: '
                    . $e->getMessage();
            } else {
                $domain->status = 'error';
                $domain->last_error = 'Provisioning failed at step '
                    . ($state['failed_step'] ?: 'unknown')
                    . ': '
                    . $e->getMessage()
                    . ' | Rollback errors: '
                    . implode(' | ', $rollback['errors']);
            }

            $domain->save();
            $state['domain'] = $domain->fresh();
            return $state;
        }

        $domain->status = $domain->status ?: 'active';
        $domain->last_error = null;
        $domain->save();
        $state['success'] = true;
        $this->emitProgress($progress, 'Domain provisioning completed.', [
            'step' => 'done',
            'state' => 'done',
            'completed_steps' => $state['completed_steps'],
        ]);
        $state['domain'] = $domain->fresh();

        return $state;
        } finally {
            $this->releaseProvisioningLock($lock);
        }
    }

    public function rollbackDomain(Domain $domain): array
    {
        $steps = [];
        $errors = [];
        $tenant = $domain->tenant;

        if ($tenant) {
            $instanceResult = $this->instances->deprovisionTenant($tenant);
            $steps['instance'] = $instanceResult;
            if (!($instanceResult['success'] ?? false)) {
                $errors[] = 'Instance rollback failed: ' . ($instanceResult['instance']['output'] ?? 'unknown error');
            }
            $tenant->instance_status = 'pending';
            $tenant->instance_last_error = null;
            $tenant->save();
        }

        $nginxResult = $this->nginxProvisioning->deprovisionDomain($domain);
        $steps['nginx'] = $nginxResult;
        if (!$nginxResult['success']) {
            $errors[] = 'Nginx rollback failed: ' . ($nginxResult['output'] ?: 'unknown error');
        }

        $zoneId = $domain->cf_zone_id ?: config('services.cloudflare.zone_id');
        if ($zoneId && $domain->cf_record_id) {
            try {
                $this->cloudflare->deleteDnsRecord($zoneId, $domain->cf_record_id);
                $steps['cloudflare'] = ['success' => true, 'output' => 'DNS record removed'];
                $domain->cf_record_id = null;
            } catch (\Throwable $e) {
                $steps['cloudflare'] = ['success' => false, 'output' => $e->getMessage()];
                $errors[] = 'Cloudflare rollback failed: ' . $e->getMessage();
            }
        }

        $domain->status = empty($errors) ? 'pending' : 'error';
        $domain->last_error = empty($errors) ? null : implode(' | ', $errors);
        $domain->save();

        return [
            'success' => empty($errors),
            'steps' => $steps,
            'errors' => $errors,
        ];
    }

    private function rollbackProvisioningSteps(
        Domain $domain,
        ?\App\Models\Tenant $tenant,
        array $completedSteps,
        array $context,
        ?callable $progress = null
    ): array {
        $result = [
            'performed' => true,
            'success' => true,
            'steps' => [],
            'errors' => [],
        ];

        foreach (array_reverse($completedSteps) as $step) {
            try {
                if ($step === 'nginx') {
                    $nginx = $this->nginxProvisioning->deprovisionDomain($domain);
                    $result['steps']['nginx'] = $nginx;
                    if (!($nginx['success'] ?? false)) {
                        $result['success'] = false;
                        $result['errors'][] = 'Nginx rollback failed: ' . ($nginx['output'] ?? 'unknown');
                    }
                    $this->emitProgress($progress, 'Rollback nginx completed.', ['step' => 'rollback_nginx', 'state' => 'done']);
                    continue;
                }

                if ($step === 'dns') {
                    $zoneId = $context['zone_id'] ?? ($domain->cf_zone_id ?: config('services.cloudflare.zone_id'));
                    $recordId = $context['record_id'] ?? $domain->cf_record_id;
                    if ($zoneId && $recordId) {
                        $this->cloudflare->deleteDnsRecord((string) $zoneId, (string) $recordId);
                        $domain->cf_record_id = null;
                        $domain->save();
                        $result['steps']['dns'] = ['success' => true, 'output' => 'DNS record removed.'];
                    } else {
                        $result['steps']['dns'] = ['success' => true, 'output' => 'DNS rollback skipped (no record).'];
                    }
                    $this->emitProgress($progress, 'Rollback dns completed.', ['step' => 'rollback_dns', 'state' => 'done']);
                    continue;
                }

                if ($step === 'ssl') {
                    $certificate = $domain->sslCertificate;
                    if ($certificate) {
                        $certificate->status = 'pending';
                        $certificate->last_error = 'Provisioning rollback requested.';
                        $certificate->save();
                    }
                    $result['steps']['ssl'] = ['success' => true, 'output' => 'SSL rollback marker saved.'];
                    $this->emitProgress($progress, 'Rollback ssl completed.', ['step' => 'rollback_ssl', 'state' => 'done']);
                    continue;
                }

                if ($step === 'instance') {
                    if ($tenant && !empty($context['instance_created'])) {
                        $instance = $this->instances->deprovisionTenant($tenant, true);
                        $result['steps']['instance'] = $instance;
                        if (!($instance['success'] ?? false)) {
                            $result['success'] = false;
                            $result['errors'][] = 'Instance rollback failed: ' . ($instance['instance']['output'] ?? 'unknown');
                        }
                    } else {
                        $result['steps']['instance'] = ['success' => true, 'output' => 'Instance rollback skipped (pre-existing instance).'];
                    }
                    $this->emitProgress($progress, 'Rollback instance completed.', ['step' => 'rollback_instance', 'state' => 'done']);
                }
            } catch (\Throwable $e) {
                $result['success'] = false;
                $result['errors'][] = ucfirst($step) . ' rollback failed: ' . $e->getMessage();
                $result['steps'][$step] = ['success' => false, 'output' => $e->getMessage()];
                $this->emitProgress($progress, 'Rollback failed for ' . $step . ': ' . $e->getMessage(), [
                    'step' => 'rollback_' . $step,
                    'state' => 'failed',
                ]);
            }
        }

        return $result;
    }

    private function emitProgress(?callable $progress, string $message, array $meta = []): void
    {
        if (!$progress) {
            return;
        }

        $progress($message, $meta);
    }

    private function lockKey(Domain $domain): string
    {
        return sprintf('provisioning:tenant:%d:domain:%d', (int) $domain->tenant_id, (int) $domain->id);
    }

    private function acquireProvisioningLock(Domain $domain): mixed
    {
        $key = $this->lockKey($domain);
        $ttlSeconds = max(60, (int) config('services.provisioning.lock_ttl_seconds', 1800));

        try {
            $lock = Cache::lock($key, $ttlSeconds);
            if ($lock->get()) {
                return $lock;
            }
        } catch (\Throwable $e) {
            // Some cache stores may not support lock() semantics.
        }

        if (Cache::add($key, 1, now()->addSeconds($ttlSeconds))) {
            return [
                'manual' => true,
                'key' => $key,
            ];
        }

        return null;
    }

    private function releaseProvisioningLock(mixed $lock): void
    {
        if ($lock === null) {
            return;
        }

        if (is_array($lock) && ($lock['manual'] ?? false) && !empty($lock['key'])) {
            Cache::forget((string) $lock['key']);
            return;
        }

        if (is_object($lock) && method_exists($lock, 'release')) {
            try {
                $lock->release();
            } catch (\Throwable $e) {
                // Ignore lock release errors.
            }
        }
    }

    private function isProvisioningAlreadySatisfied(Domain $domain, ?Tenant $tenant): bool
    {
        if ($domain->status !== 'active') {
            return false;
        }

        if ($tenant && $tenant->instance_status !== 'ready') {
            return false;
        }

        if (config('services.infrastructure.auto_nginx') && $domain->nginx_status === 'error') {
            return false;
        }

        $certificate = $domain->sslCertificate;
        if (config('services.ssl.auto') && $certificate && $certificate->status === 'error') {
            return false;
        }

        return true;
    }
}
