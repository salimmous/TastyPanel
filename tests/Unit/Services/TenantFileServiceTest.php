<?php

namespace Tests\Unit\Services;

use App\Services\TenantFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantFileServiceTest extends TestCase
{
    private TenantFileService $service;
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = storage_path('framework/testing/tenant-files');
        Config::set('services.storage.tenant_files_root', $this->tempRoot);

        // Ensure clean state
        if (File::isDirectory($this->tempRoot)) {
            File::deleteDirectory($this->tempRoot);
        }

        $this->service = new TenantFileService();
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->tempRoot)) {
            File::deleteDirectory($this->tempRoot);
        }

        parent::tearDown();
    }

    public function test_upload_file_success()
    {
        $tenantId = 1;
        $path = 'uploads';
        $fileName = 'test-image.jpg';
        $file = UploadedFile::fake()->image($fileName);

        $result = $this->service->upload($tenantId, $path, [$file]);

        $this->assertCount(1, $result);
        $this->assertEquals($fileName, $result[0]['name']);
        $this->assertEquals("$path/$fileName", $result[0]['path']);

        $absolutePath = $this->tempRoot . "/$tenantId/$path/$fileName";
        $this->assertFileExists($absolutePath);
    }

    public function test_upload_creates_directory_if_missing()
    {
        $tenantId = 2;
        $path = 'new/nested/folder';
        $fileName = 'doc.pdf';
        $file = UploadedFile::fake()->create($fileName, 100);

        $absoluteDir = $this->tempRoot . "/$tenantId/$path";
        $this->assertDirectoryDoesNotExist($absoluteDir);

        $result = $this->service->upload($tenantId, $path, [$file]);

        $this->assertDirectoryExists($absoluteDir);
        $this->assertFileExists("$absoluteDir/$fileName");
    }

    public function test_upload_sanitizes_filename()
    {
        $tenantId = 3;
        $path = '';
        // Filename with special chars that should be replaced.
        // UploadedFile::fake() internally strips path components from the name,
        // so we use characters that remain but should be sanitized.
        $dirtyName = 'file name?.txt';
        $expectedName = 'file_name_.txt';

        $file = UploadedFile::fake()->create($dirtyName, 50);

        $result = $this->service->upload($tenantId, $path, [$file]);

        $this->assertEquals($expectedName, $result[0]['name']);
        $this->assertFileExists($this->tempRoot . "/$tenantId/$expectedName");
    }

    public function test_upload_generates_random_name_for_empty_sanitized_name()
    {
        $tenantId = 4;
        $path = 'random';
        // ".." sanitizes to ""
        $badName = '..';

        $file = UploadedFile::fake()->create($badName, 10);

        $result = $this->service->upload($tenantId, $path, [$file]);

        $generatedName = $result[0]['name'];
        $this->assertNotEmpty($generatedName);
        $this->assertNotEquals($badName, $generatedName);
        $this->assertEquals(12, strlen($generatedName)); // Str::random(12)

        $this->assertFileExists($this->tempRoot . "/$tenantId/$path/$generatedName");
    }

    public function test_upload_skips_invalid_input()
    {
        $tenantId = 5;
        $path = 'mixed';
        $validFile = UploadedFile::fake()->create('valid.txt');

        $files = [
            'not_a_file',
            123,
            $validFile,
            null
        ];

        $result = $this->service->upload($tenantId, $path, $files);

        $this->assertCount(1, $result);
        $this->assertEquals('valid.txt', $result[0]['name']);
        $this->assertFileExists($this->tempRoot . "/$tenantId/$path/valid.txt");
    }
}
