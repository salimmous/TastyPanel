<?php

namespace Tests\Unit\Services;

use App\Services\TenantFileService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

class TenantFileServiceTest extends TestCase
{
    private TenantFileService $service;
    private string $rootPath = '/tmp/tenant-files';
    private int $tenantId = 123;
    private string $tenantRoot;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup the config to a known path
        Config::set('services.storage.tenant_files_root', $this->rootPath);
        $this->tenantRoot = $this->rootPath . '/' . $this->tenantId;

        // Create the service instance
        $this->service = new TenantFileService();

        // Common expectation: tenantRoot always checks if the root directory exists.
        // We put this in setUp because it runs before every test method.
        File::shouldReceive('isDirectory')
            ->with($this->tenantRoot)
            ->andReturn(true);
    }

    public function test_delete_throws_exception_if_path_is_empty()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Path required.');

        $this->service->delete($this->tenantId, '');
    }

    public function test_delete_throws_exception_for_invalid_path()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid path.');

        $this->service->delete($this->tenantId, '../secret');
    }

    public function test_delete_removes_directory_if_path_is_directory()
    {
        $relativePath = 'images';
        $absolutePath = $this->tenantRoot . '/' . $relativePath;

        // Simulate path is a directory
        File::shouldReceive('isDirectory')
            ->with($absolutePath)
            ->once()
            ->andReturn(true);

        // Expect directory deletion
        File::shouldReceive('deleteDirectory')
            ->with($absolutePath)
            ->once();

        // Ensure file delete is NOT called
        File::shouldReceive('delete')
            ->never();

        // Ensure file exists check is NOT called
        File::shouldReceive('exists')
            ->with($absolutePath)
            ->never();

        $this->service->delete($this->tenantId, $relativePath);
    }

    public function test_delete_removes_file_if_path_is_file()
    {
        $relativePath = 'document.pdf';
        $absolutePath = $this->tenantRoot . '/' . $relativePath;

        // Simulate path is NOT a directory
        File::shouldReceive('isDirectory')
            ->with($absolutePath)
            ->once()
            ->andReturn(false);

        // Simulate path exists (as a file)
        File::shouldReceive('exists')
            ->with($absolutePath)
            ->once()
            ->andReturn(true);

        // Expect file deletion
        File::shouldReceive('delete')
            ->with($absolutePath)
            ->once();

        // Ensure directory delete is NOT called
        File::shouldReceive('deleteDirectory')
            ->never();

        $this->service->delete($this->tenantId, $relativePath);
    }

    public function test_delete_does_nothing_if_path_does_not_exist()
    {
        $relativePath = 'missing.txt';
        $absolutePath = $this->tenantRoot . '/' . $relativePath;

        // Simulate path is NOT a directory
        File::shouldReceive('isDirectory')
            ->with($absolutePath)
            ->once()
            ->andReturn(false);

        // Simulate path does NOT exist
        File::shouldReceive('exists')
            ->with($absolutePath)
            ->once()
            ->andReturn(false);

        // Expect NO deletions
        File::shouldReceive('deleteDirectory')->never();
        File::shouldReceive('delete')->never();

        $this->service->delete($this->tenantId, $relativePath);
    }
}
