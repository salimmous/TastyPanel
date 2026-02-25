<?php

namespace Tests\Feature\Services;

use App\Models\BackupRun;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    use RefreshDatabase;

    public static $execCallback;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('backups');
        Storage::fake('tenants');
        Storage::fake('s3');
        Storage::fake('local');

        // Mock DB config to simulate MySQL for backup service
        // Use a separate connection so default 'sqlite' works for models
        config(['database.connections.mysql_fake' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'production_db',
            'username' => 'root',
            'password' => 'secret',
        ]]);
        config(['backup.database_connection' => 'mysql_fake']);

        // Reset exec callback
        self::$execCallback = null;
    }

    protected function tearDown(): void
    {
        self::$execCallback = null;
        parent::tearDown();
    }

    public function test_full_system_backup_success_local()
    {
        // Mock exec to simulate successful commands and create dummy files
        self::$execCallback = function ($command, &$output, &$returnCode) {
            $returnCode = 0;
            $output = ['Command success'];

            // Simulate file creation based on command
            if (str_contains($command, '--result-file=')) {
                // mysqldump
                preg_match('/--result-file=([^ ]+)/', $command, $matches);
                if (isset($matches[1])) {
                    $path = trim($matches[1], "'");
                    // Ensure directory exists for the file
                    $dir = dirname($path);
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    file_put_contents($path, 'DUMMY DATABASE CONTENT');
                }
            } elseif (str_contains($command, 'tar -czf')) {
                // tar
                $parts = explode(' ', $command);
                // tar -czf target source...
                // parts[2] should be target
                $path = trim($parts[2], "'");
                file_put_contents($path, 'DUMMY TAR CONTENT');
            } elseif (str_contains($command, 'zip -j')) {
                // zip
                $parts = explode(' ', $command);
                // zip -j target source...
                $path = trim($parts[2], "'");
                file_put_contents($path, 'DUMMY ZIP CONTENT');
            }
        };

        $service = new BackupService();
        $run = $service->run();

        $this->assertInstanceOf(BackupRun::class, $run);
        $this->assertEquals('completed', $run->status);
        $this->assertEquals('local', $run->disk);
        $this->assertNotNull($run->path);

        // Check files exist in the backup directory
        $this->assertDirectoryExists($run->path);
        $this->assertFileExists($run->path . '/backup.zip');

        // Verify database records
        $this->assertDatabaseHas('backup_runs', [
            'id' => $run->id,
            'status' => 'completed',
            'type' => 'manual',
        ]);
    }

    public function test_full_system_backup_success_s3()
    {
        // Enable S3 in settings
        PlatformSetting::create([
            'data' => [
                'backup_s3_enabled' => true,
                'backup_keep_local' => false,
                'backup_s3_prefix' => 'test-backups',
            ]
        ]);

        self::$execCallback = function ($command, &$output, &$returnCode) {
            $returnCode = 0;
            if (str_contains($command, 'zip -j')) {
                $parts = explode(' ', $command);
                $path = trim($parts[2], "'");
                file_put_contents($path, 'DUMMY ZIP CONTENT');
            }
             if (str_contains($command, '--result-file=')) {
                preg_match('/--result-file=([^ ]+)/', $command, $matches);
                if (isset($matches[1])) {
                    $path = trim($matches[1], "'");
                    $dir = dirname($path);
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    file_put_contents($path, 'DB');
                }
            }
            if (str_contains($command, 'tar -czf')) {
                $parts = explode(' ', $command);
                file_put_contents(trim($parts[2], "'"), 'TAR');
            }
        };

        $service = new BackupService();
        $run = $service->run();

        $this->assertEquals('completed', $run->status);
        $this->assertEquals('s3', $run->disk);
        $this->assertNotNull($run->remote_path);

        // Verify S3 upload
        Storage::disk('s3')->assertExists($run->remote_path);

        // Verify local cleanup
        $this->assertNull($run->path);
    }

    public function test_full_system_backup_failure()
    {
        // Simulate mysqldump failure
        self::$execCallback = function ($command, &$output, &$returnCode) {
            if (str_contains($command, 'mysqldump')) {
                $returnCode = 1;
                $output = ['mysqldump error'];
            }
        };

        $service = new BackupService();
        $run = $service->run();

        $this->assertEquals('failed', $run->status);
        $this->assertStringContainsString('mysqldump failed', $run->output);
    }

    public function test_tenant_backup_success()
    {
        $tenant = new Tenant();
        $tenant->id = 1;
        $tenant->instance_db_name = 'db';
        $tenant->instance_db_user = 'user';
        $tenant->instance_db_password = 'pass';

        self::$execCallback = function ($command, &$output, &$returnCode) {
            $returnCode = 0;
            if (str_contains($command, 'mysqldump')) {
                preg_match('/--result-file=([^ ]+)/', $command, $matches);
                 if (isset($matches[1])) {
                    $path = trim($matches[1], "'");
                    $dir = dirname($path);
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    file_put_contents($path, 'DB');
                }
            }
            if (str_contains($command, 'tar -czf')) {
                $parts = explode(' ', $command);
                file_put_contents(trim($parts[2], "'"), 'TAR');
            }
            if (str_contains($command, 'zip -j')) {
                $parts = explode(' ', $command);
                file_put_contents(trim($parts[2], "'"), 'ZIP');
            }
        };

        $service = new BackupService();
        $zipPath = $service->backupTenant($tenant);

        $this->assertFileExists($zipPath);
        $this->assertStringContainsString('tenants/tenant_1_', $zipPath);
    }

    public function test_tenant_restore_success()
    {
        $tenant = new Tenant();
        $tenant->id = 1;
        $tenant->instance_db_name = 'db';
        $tenant->instance_db_user = 'user';
        $tenant->instance_db_password = 'pass';

        // Create a fake backup file
        $backupPath = Storage::disk('backups')->path('test_restore.zip');
        // Ensure directory exists
        if (!is_dir(dirname($backupPath))) mkdir(dirname($backupPath), 0755, true);
        file_put_contents($backupPath, 'dummy zip content');

        self::$execCallback = function ($command, &$output, &$returnCode) {
            $returnCode = 0;
            // Simulate unzip
            if (str_contains($command, 'unzip')) {
                $parts = explode(' ', $command);
                // unzip -q source -d dest
                $dest = trim(end($parts), "'");

                if (!is_dir($dest)) mkdir($dest, 0755, true);
                file_put_contents($dest . '/database.sql', 'SQL');
                file_put_contents($dest . '/files.tar.gz', 'TAR');
            }
        };

        $service = new BackupService();
        $result = $service->restoreTenant($tenant, $backupPath);

        $this->assertTrue($result);
    }
}

namespace App\Services;

function exec($command, &$output = null, &$return_var = null) {
    if (\Tests\Feature\Services\BackupServiceTest::$execCallback) {
        return call_user_func_array(\Tests\Feature\Services\BackupServiceTest::$execCallback, [$command, &$output, &$return_var]);
    }
    return \exec($command, $output, $return_var);
}
