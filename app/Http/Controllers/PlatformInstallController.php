<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PlatformInstallController extends Controller
{
    public static function isInstalled(): bool
    {
        try {
            if (Schema::hasTable('platform_settings')) {
                $data = PlatformSetting::getData();
                if (($data['installed'] ?? false) === true) {
                    return true;
                }
            }

            if (Schema::hasTable('users')) {
                return User::query()
                    ->where('role', 'superadmin')
                    ->orWhere('is_superadmin', true)
                    ->exists();
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }

    public function show(Request $request): View|RedirectResponse
    {
        if (self::isInstalled()) {
            return redirect()->route('platform.login');
        }

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: $request->getHost();
        $port = parse_url((string) config('app.url'), PHP_URL_PORT) ?: $request->getPort();

        $checks = [
            'env_file' => file_exists(base_path('.env')),
            'app_key' => !empty(config('app.key')),
            'database' => $this->databaseReachable(),
            'users_table' => $this->tableExists('users'),
            'settings_table' => $this->tableExists('platform_settings'),
        ];

        return view('platform.install', [
            'checks' => $checks,
            'panelScheme' => $scheme,
            'panelHost' => $host,
            'panelPort' => $port,
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        if (self::isInstalled()) {
            return redirect()->route('platform.login');
        }

        $data = $request->validate([
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:190'],
            'admin_password' => ['required', 'string', 'min:8', 'max:190'],
            'panel_scheme' => ['required', 'string', 'in:http,https'],
            'panel_host' => ['required', 'string', 'max:190'],
            'panel_port' => ['required', 'integer', 'min:1', 'max:65535'],
        ]);

        if (!$this->databaseReachable()) {
            return back()->withErrors([
                'admin_email' => 'Database is not reachable. Configure DB in .env first, then reload this page.',
            ])->withInput();
        }

        if (!$this->tableExists('users')) {
            return back()->withErrors([
                'admin_email' => 'Tables are not migrated yet. Run: php artisan migrate --force',
            ])->withInput();
        }

        $user = User::query()->where('email', $data['admin_email'])->first();
        if (!$user) {
            $user = new User();
            $user->email = $data['admin_email'];
        }

        $user->name = $data['admin_name'];
        $user->password = Hash::make($data['admin_password']);
        $user->role = 'superadmin';
        $user->is_superadmin = true;
        $user->tenant_id = null;
        $user->save();

        $url = $this->buildPanelUrl($data['panel_scheme'], $data['panel_host'], (int) $data['panel_port']);
        $this->writeEnv('APP_NAME', '"TastyPanel"');
        $this->writeEnv('APP_URL', $url);
        $this->writeEnv('APP_MODE', 'platform');
        $this->writeEnv('TENANT_MODE', 'false');

        if ($this->tableExists('platform_settings')) {
            $current = PlatformSetting::getData();
            PlatformSetting::updateData(array_merge($current, [
                'installed' => true,
                'installed_at' => now()->toIso8601String(),
                'panel' => [
                    'scheme' => $data['panel_scheme'],
                    'host' => $data['panel_host'],
                    'port' => (int) $data['panel_port'],
                    'url' => $url,
                ],
            ]));
        }

        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
        } catch (\Throwable $e) {
            // Safe to ignore here; install should still complete.
        }

        return redirect()->route('platform.login')->with('success', 'Install completed. Login with the admin account you just created.');
    }

    private function databaseReachable(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function buildPanelUrl(string $scheme, string $host, int $port): string
    {
        $host = trim($host);
        $isDefaultPort = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
        if ($isDefaultPort) {
            return $scheme . '://' . $host;
        }

        return $scheme . '://' . $host . ':' . $port;
    }

    private function writeEnv(string $key, string $value): void
    {
        $path = base_path('.env');
        if (!file_exists($path) || !is_writable($path)) {
            return;
        }

        $content = (string) file_get_contents($path);
        $line = $key . '=' . $value;
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $line, $content) ?: $content;
        } else {
            $content = rtrim($content) . PHP_EOL . $line . PHP_EOL;
        }

        file_put_contents($path, $content);
    }
}
