<?php

namespace Tests\Feature;

use App\Services\ThemePackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class ThemeSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_rejects_disallowed_extensions()
    {
        $zipPath = storage_path('app/testing/bad_ext.zip');
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('shell.php', '<?php echo "bad"; ?>');
            $zip->addFromString('home.blade.php', '<h1>Home</h1>');
            $zip->close();
        }

        $service = new ThemePackageService();
        $exceptionThrown = false;
        try {
            $service->extractThemeZip($zipPath, 'bad-ext-theme');
        } catch (\RuntimeException $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('Disallowed file type', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'Should reject .php file');
        $this->assertFalse(file_exists(storage_path('themes/bad-ext-theme/shell.php')));

        // Cleanup
        if (file_exists(storage_path('themes/bad-ext-theme'))) {
            File::deleteDirectory(storage_path('themes/bad-ext-theme'));
        }
        unlink($zipPath);
    }

    public function test_it_rejects_unsafe_blade_content()
    {
        $zipPath = storage_path('app/testing/bad_blade.zip');
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('exploit.blade.php', '@php system("id") @endphp');
            $zip->addFromString('home.blade.php', '<h1>Home</h1>');
            $zip->close();
        }

        $service = new ThemePackageService();
        $exceptionThrown = false;
        try {
            $service->extractThemeZip($zipPath, 'bad-blade-theme');
        } catch (\RuntimeException $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('Unsafe content', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'Should reject unsafe blade content');
        $this->assertFalse(file_exists(storage_path('themes/bad-blade-theme/exploit.blade.php')));

        // Cleanup
        if (file_exists(storage_path('themes/bad-blade-theme'))) {
            File::deleteDirectory(storage_path('themes/bad-blade-theme'));
        }
        unlink($zipPath);
    }

    public function test_it_allows_valid_theme()
    {
        $zipPath = storage_path('app/testing/valid.zip');
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('style.css', 'body { color: blue; }');
            $zip->addFromString('script.js', 'console.log("hello");');
            $zip->addFromString('home.blade.php', '<h1>Welcome {{ $name }}</h1>');
            $zip->close();
        }

        $service = new ThemePackageService();
        $view = $service->extractThemeZip($zipPath, 'valid-theme');

        $this->assertEquals('tenant::valid-theme.home', $view);
        $this->assertTrue(file_exists(storage_path('themes/valid-theme/style.css')));

        // Cleanup
        if (file_exists(storage_path('themes/valid-theme'))) {
            File::deleteDirectory(storage_path('themes/valid-theme'));
        }
        unlink($zipPath);
    }

    public function test_it_allows_theme_with_directories()
    {
        $zipPath = storage_path('app/testing/valid_dirs.zip');
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addEmptyDir('assets');
            $zip->addFromString('assets/style.css', 'body { color: blue; }');
            $zip->addFromString('home.blade.php', '<h1>Welcome</h1>');
            $zip->close();
        }

        $service = new ThemePackageService();
        $view = $service->extractThemeZip($zipPath, 'valid-dir-theme');

        $this->assertEquals('tenant::valid-dir-theme.home', $view);
        $this->assertTrue(file_exists(storage_path('themes/valid-dir-theme/assets/style.css')));

        // Cleanup
        if (file_exists(storage_path('themes/valid-dir-theme'))) {
            File::deleteDirectory(storage_path('themes/valid-dir-theme'));
        }
        unlink($zipPath);
    }
}
