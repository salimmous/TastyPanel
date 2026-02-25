<?php

namespace App\Services;

use App\Models\Domain;

class NginxProvisioningService
{
    public function provisionDomain(Domain $domain, bool $reload = true): Domain
    {
        $domain->nginx_status = 'writing';
        $domain->nginx_error = null;
        $domain->save();

        $configPath = $this->writeConfig($domain);

        $domain->nginx_status = 'written';
        $domain->nginx_updated_at = now();
        $domain->save();

        if (config('services.infrastructure.auto_nginx') && $reload) {
            $this->applyConfig($domain, $configPath);
        }

        return $domain->refresh();
    }

    public function writeConfig(Domain $domain): string
    {
        $configDir = storage_path('app/nginx/sites-available');
        if (! is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        $config = $this->renderConfig($domain);
        $configPath = $configDir.'/'.$domain->hostname.'.conf';
        file_put_contents($configPath, $config);

        return $configPath;
    }

    public function renderConfig(Domain $domain): string
    {
        if (! empty($domain->nginx_custom_config)) {
            return $domain->nginx_custom_config;
        }

        return $this->renderDefaultConfig($domain);
    }

    public function renderDefaultConfig(Domain $domain): string
    {
        $frontendUpstream = $this->frontendUpstream($domain);
        if ($frontendUpstream) {
            return $this->renderFrontendConfig($domain, $frontendUpstream);
        }

        $stubPath = base_path('resources/stubs/nginx-vhost.stub');
        $stub = file_get_contents($stubPath) ?: '';

        $sslBlock = '';
        $certificate = $domain->sslCertificate;
        $meta = $certificate?->meta ?? [];
        $http3Enabled = (bool) $domain->http3_enabled;
        if ($certificate && $certificate->status === 'issued' && ! empty($meta['cert_path']) && ! empty($meta['key_path'])) {
            $sslBlock = $this->renderSslBlock(
                $domain,
                $meta['cert_path'],
                $meta['key_path'],
                $meta['chain_path'] ?? null,
                $http3Enabled
            );
        }

        $serverName = $domain->hostname;
        $tenant = $domain->tenant;
        $webRoot = $tenant?->instance_public_root ?: config('services.infrastructure.web_root', '/var/www/tastypanel/public');
        $phpFpm = $tenant?->instance_php_socket ?: config('services.infrastructure.php_fpm_socket', '/run/php/php8.2-fpm.sock');
        if (str_starts_with($phpFpm, '/') && ! str_starts_with($phpFpm, 'unix:')) {
            $phpFpm = 'unix:'.$phpFpm;
        }
        $accessLog = $this->accessLogPath($serverName);
        $errorLog = $this->errorLogPath($serverName);

        return str_replace(
            ['{{SERVER_NAME}}', '{{WEB_ROOT}}', '{{PHP_FPM}}', '{{SSL_BLOCK}}', '{{ACCESS_LOG}}', '{{ERROR_LOG}}'],
            [$serverName, $webRoot, $phpFpm, $sslBlock, $accessLog, $errorLog],
            $stub
        );
    }

    private function renderFrontendConfig(Domain $domain, string $upstream): string
    {
        $stubPath = base_path('resources/stubs/nginx-vhost-frontend.stub');
        $stub = file_get_contents($stubPath) ?: '';

        $sslBlock = $this->renderFrontendSslBlock($domain, $upstream);

        $accessLog = $this->accessLogPath($domain->hostname);
        $errorLog = $this->errorLogPath($domain->hostname);

        return str_replace(
            ['{{SERVER_NAME}}', '{{UPSTREAM}}', '{{SSL_BLOCK}}', '{{ACCESS_LOG}}', '{{ERROR_LOG}}'],
            [$domain->hostname, $upstream, $sslBlock, $accessLog, $errorLog],
            $stub
        );
    }

    private function renderFrontendSslBlock(Domain $domain, string $upstream): string
    {
        $certificate = $domain->sslCertificate;
        $meta = $certificate?->meta ?? [];
        $http3Enabled = (bool) $domain->http3_enabled;
        if (! $certificate || $certificate->status !== 'issued' || empty($meta['cert_path']) || empty($meta['key_path'])) {
            return '';
        }

        $chainDirective = ! empty($meta['chain_path']) ? "ssl_trusted_certificate {$meta['chain_path']};" : '';
        $http3Block = '';
        if ($http3Enabled) {
            $http3Block = <<<'HTTP3'
    listen 443 quic reuseport;
    add_header Alt-Svc 'h3=":443"; ma=86400';
    add_header QUIC-Status $quic;
HTTP3;
        }

        $accessLog = $this->accessLogPath($domain->hostname);
        $errorLog = $this->errorLogPath($domain->hostname);

        return <<<SSL
server {
    listen 443 ssl http2;
{$http3Block}
    server_name {$domain->hostname};

    ssl_certificate {$meta['cert_path']};
    ssl_certificate_key {$meta['key_path']};
    {$chainDirective}

    client_max_body_size 50M;

    {$accessLog}
    {$errorLog}

    location / {
        proxy_pass http://{$upstream};
        proxy_set_header Host \$host;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}
SSL;
    }

    private function frontendUpstream(Domain $domain): ?string
    {
        if (! config('services.instances.frontend_auto', false)) {
            return null;
        }

        $tenant = $domain->tenant;
        $instanceRoot = $tenant?->instance_root;
        if (! $tenant || ! $instanceRoot) {
            return null;
        }
        $frontendPath = $instanceRoot.'/frontend';
        if (! is_dir($frontendPath)) {
            return null;
        }
        $port = 32000 + (int) $tenant->id;

        return "127.0.0.1:{$port}";
    }

    public function writeCustomConfig(Domain $domain, string $config): string
    {
        $configDir = storage_path('app/nginx/sites-available');
        if (! is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        $configPath = $configDir.'/'.$domain->hostname.'.conf';
        file_put_contents($configPath, $config);

        return $configPath;
    }

    public function testConfig(Domain $domain, string $config): array
    {
        $configPath = $this->writeCustomConfig($domain, $config);

        return $this->runScript($domain, $configPath, 'test');
    }

    private function renderSslBlock(
        Domain $domain,
        string $certPath,
        string $keyPath,
        ?string $chainPath,
        bool $http3Enabled
    ): string {
        $tenant = $domain->tenant;
        $webRoot = $tenant?->instance_public_root ?: config('services.infrastructure.web_root', '/var/www/tastypanel/public');
        $phpFpm = $tenant?->instance_php_socket ?: config('services.infrastructure.php_fpm_socket', '/run/php/php8.2-fpm.sock');
        $accessLog = $this->accessLogPath($domain->hostname);
        $errorLog = $this->errorLogPath($domain->hostname);

        $chainDirective = $chainPath ? "ssl_trusted_certificate {$chainPath};" : '';
        $http3Block = '';
        if ($http3Enabled) {
            $http3Block = <<<'HTTP3'
    listen 443 quic reuseport;
    add_header Alt-Svc 'h3=":443"; ma=86400';
    add_header QUIC-Status $quic;
HTTP3;
        }

        return <<<SSL
server {
    listen 443 ssl http2;
{$http3Block}
    server_name {$domain->hostname};
    root {$webRoot};
    index index.php index.html;

    ssl_certificate {$certPath};
    ssl_certificate_key {$keyPath};
    {$chainDirective}

    client_max_body_size 50M;

    {$accessLog}
    {$errorLog}

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass {$phpFpm};
    }

    location ~ /\\.ht {
        deny all;
    }
}
SSL;
    }

    private function accessLogPath(string $hostname): string
    {
        $template = config('services.logs.nginx_access_template', '/var/log/nginx/%s-access.log');
        if (! str_contains($template, '%s')) {
            return 'access_log /var/log/nginx/access.log;';
        }

        return sprintf('access_log %s;', sprintf($template, $hostname));
    }

    private function errorLogPath(string $hostname): string
    {
        $template = config('services.logs.nginx_error_template', '/var/log/nginx/%s-error.log');
        if (! str_contains($template, '%s')) {
            return 'error_log /var/log/nginx/error.log;';
        }

        return sprintf('error_log %s;', sprintf($template, $hostname));
    }

    public function applyConfig(Domain $domain, string $configPath): array
    {
        $result = $this->runScript($domain, $configPath, 'apply');

        if (! $result['success']) {
            $domain->nginx_status = 'error';
            $domain->nginx_error = $result['output'];
        } else {
            $domain->nginx_status = 'active';
            $domain->nginx_error = null;
            $domain->nginx_updated_at = now();
        }

        $domain->save();

        return $result;
    }

    public function deprovisionDomain(Domain $domain): array
    {
        $configPath = storage_path('app/nginx/sites-available/'.$domain->hostname.'.conf');
        $result = $this->runScript($domain, $configPath, 'remove');

        if (! $result['success']) {
            $domain->nginx_status = 'error';
            $domain->nginx_error = $result['output'];
        } else {
            $domain->nginx_status = 'pending';
            $domain->nginx_error = null;
            $domain->nginx_updated_at = now();
        }

        $domain->save();

        return $result;
    }

    private function runScript(Domain $domain, string $configPath, string $mode): array
    {
        $script = config('services.infrastructure.nginx_script');
        if (! $script || ! file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Nginx provision script not found.',
                'exit_code' => 1,
            ];
        }

        $commandParts = [];
        if (config('services.infrastructure.nginx_use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = $script;
        $commandParts[] = $domain->hostname;
        $commandParts[] = $configPath;
        $commandParts[] = $mode;
        $commandParts[] = config('services.infrastructure.nginx_available_dir', '/etc/nginx/sites-available');
        $commandParts[] = config('services.infrastructure.nginx_enabled_dir', '/etc/nginx/sites-enabled');

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));

        $output = [];
        $exitCode = 0;
        exec($escaped.' 2>&1', $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'output' => implode("\n", $output),
            'exit_code' => $exitCode,
        ];
    }
}
