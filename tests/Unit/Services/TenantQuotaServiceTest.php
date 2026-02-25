<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\TenantDatabaseService;
use App\Services\TenantQuotaService;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

class TenantQuotaServiceTest extends TestCase
{
    public function test_database_limit_check_is_cached()
    {
        $tenant = new Tenant();
        $tenant->id = 123;
        $tenant->instance_db_name = 'test_db';

        // Clear cache
        Cache::forget("tenant_quota:db_size_mb:{$tenant->id}");

        // Mock TenantDatabaseService
        $this->mock(TenantDatabaseService::class, function (MockInterface $mock) use ($tenant) {
            $mock->shouldReceive('size')
                ->with($tenant)
                ->once() // Expect only one call
                ->andReturn(104857600); // 100MB
        });

        $service = new TenantQuotaService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkDatabaseLimit');
        $method->setAccessible(true);

        // First call - should trigger service call
        $result1 = $method->invoke($service, $tenant, 1000);
        $this->assertTrue($result1['allowed']);
        $this->assertEquals(100, $result1['used_mb']);

        // Second call - should use cache (mock expects only 1 call)
        $result2 = $method->invoke($service, $tenant, 1000);
        $this->assertTrue($result2['allowed']);
        $this->assertEquals(100, $result2['used_mb']);
    }
}
