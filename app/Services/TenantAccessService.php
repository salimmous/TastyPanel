<?php

namespace App\Services;

use App\Models\Tenant;

class TenantAccessService
{
    public function ensureAccess(Tenant $tenant): array
    {
        if (! $tenant->instance_root) {
            return [
                'success' => false,
                'output' => 'Instance root is missing.',
            ];
        }

        $username = $this->resolveUsername($tenant);
        $result = $this->run('provision', $tenant, $username);
        if (($result['success'] ?? false) === true) {
            $this->persistAccessMetadata($tenant, $username, $result['meta'] ?? []);
        }

        return $result;
    }

    public function rotatePassword(Tenant $tenant): array
    {
        if (! $tenant->instance_root) {
            return [
                'success' => false,
                'output' => 'Instance root is missing.',
            ];
        }

        $username = $this->resolveUsername($tenant);
        $result = $this->run('rotate-password', $tenant, $username);
        if (($result['success'] ?? false) === true) {
            $this->persistAccessMetadata($tenant, $username, $result['meta'] ?? []);
        }

        return $result;
    }

    public function installPublicKey(Tenant $tenant, string $publicKey): array
    {
        if (! $tenant->instance_root) {
            return [
                'success' => false,
                'output' => 'Instance root is missing.',
            ];
        }

        $username = $this->resolveUsername($tenant);
        $result = $this->run('install-key', $tenant, $username, $publicKey);
        if (($result['success'] ?? false) === true) {
            $this->persistAccessMetadata($tenant, $username, $result['meta'] ?? []);
        }

        return $result;
    }

    public function removeAccess(Tenant $tenant): array
    {
        $username = $this->resolveUsername($tenant);
        if (! $username) {
            return [
                'success' => false,
                'output' => 'Access user is not configured.',
            ];
        }

        $tenantForRun = $tenant;
        if (! $tenant->instance_root) {
            $tenantForRun = clone $tenant;
            $tenantForRun->instance_root = sys_get_temp_dir();
        }

        return $this->run('remove', $tenantForRun, $username);
    }

    public function connectionInfo(Tenant $tenant): array
    {
        return [
            'user' => $tenant->instance_ssh_user ?: $this->resolveUsername($tenant),
            'home' => $tenant->instance_ssh_home ?: ('/home/'.$this->resolveUsername($tenant)),
            'port' => $tenant->instance_ssh_port ?: 22,
            'host' => $this->resolveHost(),
            'site_path' => $tenant->instance_root,
            'protocols' => ['ssh', 'sftp'],
            'auth_mode' => (string) config('services.instances.access_auth_mode', 'both'),
            'sftp_only' => (bool) config('services.instances.access_sftp_only', false),
        ];
    }

    private function resolveUsername(Tenant $tenant): string
    {
        if (! empty($tenant->instance_ssh_user)) {
            return (string) $tenant->instance_ssh_user;
        }

        return 'tb'.$tenant->id;
    }

    private function resolveHost(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            return $host;
        }

        return '127.0.0.1';
    }

    private function persistAccessMetadata(Tenant $tenant, string $username, array $meta): void
    {
        $tenant->instance_ssh_user = $meta['SSH_USER'] ?? $username;
        $tenant->instance_ssh_home = $meta['SSH_HOME'] ?? ("/home/{$username}");
        $tenant->instance_ssh_port = (int) ($meta['SSH_PORT'] ?? ($tenant->instance_ssh_port ?: 22));
        $tenant->save();
    }

    protected function run(string $action, Tenant $tenant, string $username, ?string $publicKey = null): array
    {
        $script = (string) config('services.instances.access_script');
        if ($script === '' || ! file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Tenant access script not found.',
            ];
        }

        $commandParts = [];
        if (config('services.instances.access_use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = 'env';
        $commandParts[] = 'TENANT_ACCESS_AUTH_MODE='.(string) config('services.instances.access_auth_mode', 'both');
        $commandParts[] = 'TENANT_ACCESS_SFTP_ONLY='.(config('services.instances.access_sftp_only', false) ? '1' : '0');
        $allowedRoot = (string) config('services.instances.root', '');
        if ($allowedRoot !== '') {
            $commandParts[] = 'TENANT_ACCESS_ALLOWED_ROOT='.$allowedRoot;
        }
        if ($publicKey !== null) {
            $commandParts[] = 'SSH_PUBLIC_KEY='.$publicKey;
        }
        $commandParts[] = $script;
        $commandParts[] = $action;
        $commandParts[] = (string) ($tenant->instance_key ?: ('tenant-'.$tenant->id));
        $commandParts[] = (string) $tenant->instance_root;
        $commandParts[] = $username;

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));
        $output = [];
        $exitCode = 0;
        exec($escaped.' 2>&1', $output, $exitCode);
        $outputText = implode("\n", $output);

        return [
            'success' => $exitCode === 0,
            'output' => $outputText,
            'exit_code' => $exitCode,
            'meta' => $this->parseMetadata($output),
        ];
    }

    private function parseMetadata(array $lines): array
    {
        $meta = [];
        foreach ($lines as $line) {
            if (! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            if ($key === '') {
                continue;
            }
            $meta[$key] = trim($value);
        }

        return $meta;
    }
}
