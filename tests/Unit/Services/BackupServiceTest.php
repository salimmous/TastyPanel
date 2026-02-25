<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\BackupService;
use App\Services\Shell\ShellResult;
use App\Services\Shell\ShellRunnerInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    protected $shell;
    protected $files;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shell = Mockery::mock(ShellRunnerInterface::class);
        $this->files = Mockery::mock(Filesystem::class);

        // Pass mocks to constructor
        $this->service = new BackupService($this->shell, $this->files);

        // Mock config defaults
        Config::set('database.connections.mysql.host', '127.0.0.1');
    }

    public function test_restore_tenant_success()
    {
        $tenant = new Tenant();
        $tenant->id = 1;
        $tenant->instance_db_name = 'test_db';
        $tenant->instance_db_user = 'test_user';
        $tenant->instance_db_password = 'test_pass';

        $backupFile = '/path/to/backup.zip';

        // 1. Create extract dir
        $this->files->shouldReceive('makeDirectory')
            ->once()
            ->with(Mockery::pattern('#backups/restore_#'), 0755, true);

        // 2. Unzip
        $this->shell->shouldReceive('run')
            ->once()
            ->with(Mockery::on(function ($cmd) use ($backupFile) {
                return str_contains($cmd, 'unzip -q') && str_contains($cmd, $backupFile);
            }))
            ->andReturn(new ShellResult(0, []));

        // 3. Check for database.sql
        $this->files->shouldReceive('exists')
            ->once()
            ->with(Mockery::pattern('#/database\.sql$#'))
            ->andReturn(true);

        // 4. Restore database (mysql)
        $this->shell->shouldReceive('run')
            ->once()
            ->with(Mockery::on(function ($cmd) {
                return str_contains($cmd, 'mysql -h') && str_contains($cmd, 'test_db');
            }))
            ->andReturn(new ShellResult(0, []));

        // 5. Check for files.tar.gz
        $this->files->shouldReceive('exists')
            ->once()
            ->with(Mockery::pattern('#/files\.tar\.gz$#'))
            ->andReturn(true);

        // 6. Restore files (tar)
        // Check if tenant files dir exists
        $this->files->shouldReceive('isDirectory')
            ->once()
            ->with(Mockery::pattern('#tenant-files/1$#'))
            ->andReturn(true);

        $this->shell->shouldReceive('run')
            ->once()
            ->with(Mockery::on(function ($cmd) {
                return str_contains($cmd, 'tar -xzf') && str_contains($cmd, 'files.tar.gz');
            }))
            ->andReturn(new ShellResult(0, []));

        // 7. Cleanup
        $this->files->shouldReceive('deleteDirectory')
            ->once()
            ->with(Mockery::pattern('#backups/restore_#'));

        // 8. Log success
        Log::shouldReceive('info')
            ->once()
            ->with("Tenant restore completed: 1");

        // Execute
        $result = $this->service->restoreTenant($tenant, $backupFile);

        $this->assertTrue($result);
    }

    public function test_restore_tenant_unzip_failure()
    {
        $tenant = new Tenant();
        $tenant->id = 1;

        $backupFile = '/tmp/bad_backup.zip';

        // 1. Create extract dir
        $this->files->shouldReceive('makeDirectory')->once();

        // 2. Unzip fails
        $this->shell->shouldReceive('run')
            ->once()
            ->with(Mockery::on(function ($cmd) {
                return str_contains($cmd, 'unzip');
            }))
            ->andReturn(new ShellResult(1, ['Archive: error', 'End-of-central-directory signature not found']));

        // 3. Log error
        Log::shouldReceive('error')
            ->once()
            ->with("Tenant restore failed: 1", Mockery::on(function ($context) {
                return isset($context['error']) &&
                       str_contains($context['error'], 'Failed to unzip backup file') &&
                       str_contains($context['error'], 'End-of-central-directory signature not found');
            }));

        // Log::info should NOT be called
        Log::shouldReceive('info')->never();

        // Cleanup NOT called inside catch block
        $this->files->shouldReceive('deleteDirectory')->never();

        // Execute
        $result = $this->service->restoreTenant($tenant, $backupFile);

        $this->assertFalse($result);
    }

    public function test_restore_tenant_db_restore_failure()
    {
        $tenant = new Tenant();
        $tenant->id = 1;
        $tenant->instance_db_name = 'test_db';
        $tenant->instance_db_user = 'test_user';
        $tenant->instance_db_password = 'test_pass';

        $backupFile = '/tmp/backup.zip';

        $this->files->shouldReceive('makeDirectory')->once();

        // Unzip success
        $this->shell->shouldReceive('run')
            ->once()
            ->with(Mockery::on(function ($cmd) {
                return str_contains($cmd, 'unzip');
            }))
            ->andReturn(new ShellResult(0, []));

        // DB exists
        $this->files->shouldReceive('exists')
            ->once()
            ->with(Mockery::pattern('#/database\.sql$#'))
            ->andReturn(true);

        // Mysql restore fails
        $this->shell->shouldReceive('run')
            ->once()
            ->with(Mockery::on(function ($cmd) {
                return str_contains($cmd, 'mysql');
            }))
            ->andReturn(new ShellResult(1, ['Access denied for user']));

        // Log error
        Log::shouldReceive('error')
            ->once()
            ->with("Tenant restore failed: 1", Mockery::on(function ($context) {
                return str_contains($context['error'], 'Database restore failed');
            }));

        $result = $this->service->restoreTenant($tenant, $backupFile);

        $this->assertFalse($result);
    }

    public function test_restore_tenant_files_restore_failure()
    {
        $tenant = new Tenant();
        $tenant->id = 1;
        $tenant->instance_db_name = 'test_db';

        $backupFile = '/tmp/backup.zip';

        $this->files->shouldReceive('makeDirectory')->once();

        // Unzip success
        $this->shell->shouldReceive('run')
            ->once()
            ->with(Mockery::on(function ($cmd) {
                return str_contains($cmd, 'unzip');
            }))
            ->andReturn(new ShellResult(0, []));

        // DB exists -> false (skip DB restore to isolate files restore test)
        $this->files->shouldReceive('exists')
            ->once()
            ->with(Mockery::pattern('#/database\.sql$#'))
            ->andReturn(false);

        // Files exists -> true
        $this->files->shouldReceive('exists')
            ->once()
            ->with(Mockery::pattern('#/files\.tar\.gz$#'))
            ->andReturn(true);

        // Files Dir exists
        $this->files->shouldReceive('isDirectory')->andReturn(true);

        // Tar restore fails
        $this->shell->shouldReceive('run')
            ->once()
            ->with(Mockery::on(function ($cmd) {
                return str_contains($cmd, 'tar -xzf');
            }))
            ->andReturn(new ShellResult(1, ['tar: Error exit delayed']));

        // Log error
        Log::shouldReceive('error')
            ->once()
            ->with("Tenant restore failed: 1", Mockery::on(function ($context) {
                return str_contains($context['error'], 'Files restore failed');
            }));

        $result = $this->service->restoreTenant($tenant, $backupFile);

        $this->assertFalse($result);
    }
}
