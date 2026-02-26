<?php

namespace Tests\Unit\Jobs;

use App\Jobs\UninstallTenantAppJob;
use App\Models\Tenant;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Str;

class UninstallTenantAppJobTest extends TestCase
{
    protected $testRoot = '/tmp/tastypanel-sites-test';

    protected function setUp(): void
    {
        parent::setUp();
        // Set the base root for testing
        Config::set('services.instances.root', $this->testRoot);

        // Ensure clean state
        if (is_dir($this->testRoot)) {
            $this->recursiveRemove($this->testRoot);
        }
        mkdir($this->testRoot, 0755, true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        if (is_dir($this->testRoot)) {
            $this->recursiveRemove($this->testRoot);
        }
        parent::tearDown();
    }

    protected function recursiveRemove($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveRemove("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public function test_it_runs_uninstall_command_for_valid_path()
    {
        Process::fake();
        Log::spy();

        $root = $this->testRoot . '/tenant-valid';
        if (!is_dir($root)) {
            mkdir($root, 0755, true);
        }

        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->shouldReceive('save')->once()->andReturn(true);
        $tenant->id = 1;
        $tenant->instance_root = $root;
        $tenant->instance_system_user = 'tbapp-valid';

        $job = new UninstallTenantAppJob($tenant);
        $job->handle();

        Process::assertRan(function ($process) use ($root) {
            $escaped = escapeshellarg($root);
            return str_contains($process->command, "rm -rf {$escaped}/*");
        });
    }

    public function test_it_proceeds_to_db_update_if_directory_missing()
    {
        Process::fake();
        Log::spy();

        $root = $this->testRoot . '/tenant-missing';
        // Ensure directory does NOT exist
        if (is_dir($root)) {
            rmdir($root);
        }

        $tenant = Mockery::mock(Tenant::class)->makePartial();
        // Expect save() to be called because we proceed to cleanup
        $tenant->shouldReceive('save')->once()->andReturn(true);
        $tenant->id = 5;
        $tenant->instance_root = $root;
        $tenant->instance_system_user = 'tbapp-missing';

        $job = new UninstallTenantAppJob($tenant);
        $job->handle();

        // Should check that NO process ran
        Process::assertNothingRan();
    }

    public function test_it_does_not_run_uninstall_for_root_path_and_aborts()
    {
        Process::fake();
        Log::spy();

        $tenant = Mockery::mock(Tenant::class)->makePartial();
        // Security violation -> abort -> save() NOT called
        $tenant->shouldReceive('save')->never();
        $tenant->id = 2;
        $tenant->instance_root = '/';
        $tenant->instance_system_user = 'root';

        $job = new UninstallTenantAppJob($tenant);
        $job->handle();

        Process::assertNothingRan();
    }

    public function test_it_does_not_run_uninstall_for_parent_path_and_aborts()
    {
        Process::fake();
        Log::spy();

        $tenant = Mockery::mock(Tenant::class)->makePartial();
        // Security violation -> abort -> save() NOT called
        $tenant->shouldReceive('save')->never();
        $tenant->id = 3;
        $tenant->instance_root = '/tmp';
        $tenant->instance_system_user = 'root';

        $job = new UninstallTenantAppJob($tenant);
        $job->handle();

        Process::assertNothingRan();
    }

    public function test_it_does_not_run_uninstall_for_base_directory_itself_and_aborts()
    {
        Process::fake();
        Log::spy();

        $base = realpath($this->testRoot);

        $tenant = Mockery::mock(Tenant::class)->makePartial();
        // Security violation -> abort -> save() NOT called
        $tenant->shouldReceive('save')->never();
        $tenant->id = 4;
        $tenant->instance_root = $base;
        $tenant->instance_system_user = 'tbapp-base';

        $job = new UninstallTenantAppJob($tenant);
        $job->handle();

        Process::assertNothingRan();
    }
}
