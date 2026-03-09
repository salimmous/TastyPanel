<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\TenantBackupRun;
use App\Models\PlatformSetting;
use App\Services\TenantBackupCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TenantBackupCleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TenantBackupCleanupService();
    }

    private function createDummyBackupDirectory(): string
    {
        $dir = sys_get_temp_dir() . '/tastypanel_backup_test_' . uniqid();
        mkdir($dir);
        mkdir($dir . '/sub_dir');
        file_put_contents($dir . '/test_file.txt', 'dummy content');
        file_put_contents($dir . '/sub_dir/another_file.txt', 'dummy content');
        return $dir;
    }

    public function test_cleanup_uses_override_days()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'backup_retention_days' => 10,
        ]);

        $cutoff = now()->subDays(5);
        $oldRun = TenantBackupRun::create([
            'tenant_id' => $tenant->id,
            'finished_at' => $cutoff->copy()->subDay(),
            'path' => null,
            'disk' => 'local',
        ]);
        $recentRun = TenantBackupRun::create([
            'tenant_id' => $tenant->id,
            'finished_at' => $cutoff->copy()->addDay(),
            'path' => null,
            'disk' => 'local',
        ]);

        $deletedCount = $this->service->cleanup(5);

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('tenant_backup_runs', ['id' => $oldRun->id]);
        $this->assertDatabaseHas('tenant_backup_runs', ['id' => $recentRun->id]);
    }

    public function test_cleanup_uses_tenant_retention_days()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'backup_retention_days' => 3,
        ]);

        $cutoff = now()->subDays(3);
        $oldRun = TenantBackupRun::create([
            'tenant_id' => $tenant->id,
            'finished_at' => $cutoff->copy()->subDay(),
            'path' => null,
            'disk' => 'local',
        ]);
        $recentRun = TenantBackupRun::create([
            'tenant_id' => $tenant->id,
            'finished_at' => $cutoff->copy()->addDay(),
            'path' => null,
            'disk' => 'local',
        ]);

        $deletedCount = $this->service->cleanup();

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('tenant_backup_runs', ['id' => $oldRun->id]);
        $this->assertDatabaseHas('tenant_backup_runs', ['id' => $recentRun->id]);
    }

    public function test_cleanup_uses_platform_setting_retention_days()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'backup_retention_days' => null,
        ]);

        PlatformSetting::updateData([
            'tenant_backup_retention_days' => 4,
        ]);

        $cutoff = now()->subDays(4);
        $oldRun = TenantBackupRun::create([
            'tenant_id' => $tenant->id,
            'finished_at' => $cutoff->copy()->subDay(),
            'path' => null,
            'disk' => 'local',
        ]);
        $recentRun = TenantBackupRun::create([
            'tenant_id' => $tenant->id,
            'finished_at' => $cutoff->copy()->addDay(),
            'path' => null,
            'disk' => 'local',
        ]);

        $deletedCount = $this->service->cleanup();

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('tenant_backup_runs', ['id' => $oldRun->id]);
        $this->assertDatabaseHas('tenant_backup_runs', ['id' => $recentRun->id]);
    }

    public function test_cleanup_uses_default_retention_days()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'backup_retention_days' => null,
        ]);

        // Clear platform setting
        PlatformSetting::updateData([]);

        $cutoff = now()->subDays(7);
        $oldRun = TenantBackupRun::create([
            'tenant_id' => $tenant->id,
            'finished_at' => $cutoff->copy()->subDay(),
            'path' => null,
            'disk' => 'local',
        ]);
        $recentRun = TenantBackupRun::create([
            'tenant_id' => $tenant->id,
            'finished_at' => $cutoff->copy()->addDay(),
            'path' => null,
            'disk' => 'local',
        ]);

        $deletedCount = $this->service->cleanup();

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('tenant_backup_runs', ['id' => $oldRun->id]);
        $this->assertDatabaseHas('tenant_backup_runs', ['id' => $recentRun->id]);
    }

    public function test_cleanup_skips_when_retention_is_zero()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'backup_retention_days' => 0,
        ]);

        $cutoff = now()->subDays(100);
        $oldRun = TenantBackupRun::create([
            'tenant_id' => $tenant->id,
            'finished_at' => $cutoff->copy()->subDay(),
            'path' => null,
            'disk' => 'local',
        ]);

        $deletedCount = $this->service->cleanup();

        $this->assertEquals(0, $deletedCount);
        $this->assertDatabaseHas('tenant_backup_runs', ['id' => $oldRun->id]);
    }

    public function test_cleanup_deletes_local_directory()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'backup_retention_days' => 1,
        ]);

        $dir = $this->createDummyBackupDirectory();
        $this->assertTrue(is_dir($dir));

        $oldRun = TenantBackupRun::create([
            'tenant_id' => $tenant->id,
            'finished_at' => now()->subDays(2),
            'path' => $dir,
            'disk' => 'local',
        ]);

        $deletedCount = $this->service->cleanup();

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('tenant_backup_runs', ['id' => $oldRun->id]);
        $this->assertFalse(is_dir($dir));
    }

    public function test_cleanup_deletes_s3_file_and_ignores_exceptions()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'backup_retention_days' => 1,
        ]);

        $s3Mock = \Mockery::mock();
        $s3Mock->shouldReceive('delete')
               ->with('test/remote/path.zip')
               ->once()
               ->andThrow(new \Exception('S3 error'));

        Storage::shouldReceive('disk')
               ->with('s3')
               ->andReturn($s3Mock);

        $oldRun = TenantBackupRun::create([
            'tenant_id' => $tenant->id,
            'finished_at' => now()->subDays(2),
            'disk' => 's3',
            'remote_path' => 'test/remote/path.zip',
        ]);

        $deletedCount = $this->service->cleanup();

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('tenant_backup_runs', ['id' => $oldRun->id]);
    }
}
