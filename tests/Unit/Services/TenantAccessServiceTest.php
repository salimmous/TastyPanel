<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\TenantAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TenantAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_access_fails_when_instance_root_is_missing(): void
    {
        // Issue: Untested Instance Root Check
        // Rationale: Simple conditional check, easy to test with a tenant object missing this property.

        $tenant = Tenant::create([
            'name' => 'Test Tenant Missing Root',
            'slug' => 'test-tenant-missing-root',
            // instance_root is intentionally left null
        ]);

        $service = new TenantAccessService;
        $result = $service->ensureAccess($tenant);

        $this->assertFalse($result['success']);
        $this->assertEquals('Instance root is missing.', $result['output']);
    }

    public function test_ensure_access_succeeds_when_instance_root_is_present(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'instance_root' => '/var/www/test-tenant',
            'instance_ssh_port' => 2222,
        ]);

        // Partial mock to avoid executing actual shell commands
        $service = Mockery::mock(TenantAccessService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods();

        // Expect the 'run' method to be called with 'provision' action
        $service->shouldReceive('run')
            ->once()
            ->withArgs(function ($action, $passedTenant, $username, $publicKey = null) use ($tenant) {
                return $action === 'provision' &&
                       $passedTenant->id === $tenant->id &&
                       $username === ('tb'.$tenant->id);
            })
            ->andReturn([
                'success' => true,
                'meta' => [
                    'SSH_USER' => 'deploy_user',
                    'SSH_HOME' => '/home/deploy_user',
                ],
            ]);

        $result = $service->ensureAccess($tenant);

        $this->assertTrue($result['success']);

        // Verify that metadata was persisted to the tenant
        $tenant->refresh();
        $this->assertEquals('deploy_user', $tenant->instance_ssh_user);
        $this->assertEquals('/home/deploy_user', $tenant->instance_ssh_home);
        // Port should remain unchanged as it wasn't returned in meta
        $this->assertEquals(2222, $tenant->instance_ssh_port);
    }

    public function test_ensure_access_updates_port_from_meta(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant Port',
            'slug' => 'test-tenant-port',
            'instance_root' => '/var/www/test-tenant',
            'instance_ssh_port' => 22,
        ]);

        $service = Mockery::mock(TenantAccessService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('run')
            ->once()
            ->andReturn([
                'success' => true,
                'meta' => [
                    'SSH_PORT' => '2222',
                ],
            ]);

        $result = $service->ensureAccess($tenant);

        $this->assertTrue($result['success']);

        $tenant->refresh();
        $this->assertEquals(2222, $tenant->instance_ssh_port);
    }
}
