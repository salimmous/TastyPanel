<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\SslCertificate;

class SslProvisioningService
{
    public function requestCertificate(Domain $domain): SslCertificate
    {
        $certificate = SslCertificate::updateOrCreate(
            ['domain_id' => $domain->id],
            [
                'status' => config('services.ssl.auto') ? 'queued' : 'pending',
                'provider' => config('services.ssl.provider', 'letsencrypt'),
                'last_error' => null,
            ]
        );

        return $certificate;
    }

    public function provisionCertificate(Domain $domain, bool $force = false): SslCertificate
    {
        $certificate = $this->requestCertificate($domain);

        if (!$force && !config('services.ssl.auto')) {
            return $certificate;
        }

        $certbotPath = config('services.ssl.certbot_path', 'certbot');
        $email = config('services.ssl.certbot_email');
        $dnsToken = config('services.cloudflare.dns_token');

        if (!$email || !$dnsToken) {
            $certificate->status = 'error';
            $certificate->last_error = 'Missing SSL_CERTBOT_EMAIL or CLOUDFLARE_DNS_TOKEN.';
            $certificate->save();
            return $certificate;
        }

        $configDir = storage_path('app/certbot/config');
        $workDir = storage_path('app/certbot/work');
        $logsDir = storage_path('app/certbot/logs');
        $credentialsPath = storage_path('app/certbot/cloudflare.ini');

        foreach ([$configDir, $workDir, $logsDir, dirname($credentialsPath)] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0700, true);
            }
        }

        if (!file_exists($credentialsPath)) {
            file_put_contents($credentialsPath, "dns_cloudflare_api_token = {$dnsToken}\n");
            chmod($credentialsPath, 0600);
        }

        $domains = [$domain->hostname];
        if (substr_count($domain->hostname, '.') === 1) {
            $domains[] = 'www.' . $domain->hostname;
        }

        $certificate->status = 'provisioning';
        $certificate->last_error = null;
        $certificate->save();

        $command = array_merge([
            $certbotPath,
            'certonly',
            '--non-interactive',
            '--agree-tos',
            '--email', $email,
            '--dns-cloudflare',
            '--dns-cloudflare-credentials', $credentialsPath,
            '--dns-cloudflare-propagation-seconds', (string) config('services.ssl.propagation_seconds', 30),
            '--config-dir', $configDir,
            '--work-dir', $workDir,
            '--logs-dir', $logsDir,
        ], array_map(fn ($item) => ['-d', $item], $domains));

        $flatCommand = [];
        foreach ($command as $part) {
            if (is_array($part)) {
                $flatCommand = array_merge($flatCommand, $part);
            } else {
                $flatCommand[] = $part;
            }
        }
        $escaped = implode(' ', array_map('escapeshellarg', $flatCommand));

        try {
            $output = [];
            $exitCode = 0;
            exec($escaped . ' 2>&1', $output, $exitCode);
            if ($exitCode !== 0) {
                throw new \RuntimeException(implode("\n", $output));
            }

            $livePath = $configDir . '/live/' . $domain->hostname;
            $certificate->status = 'issued';
            $certificate->issued_at = now();
            $certificate->last_error = null;
            $certificate->meta = [
                'cert_path' => $livePath . '/fullchain.pem',
                'key_path' => $livePath . '/privkey.pem',
                'chain_path' => $livePath . '/chain.pem',
                'domains' => $domains,
            ];
            $certificate->save();
        } catch (\Throwable $e) {
            $certificate->status = 'error';
            $certificate->last_error = $e->getMessage();
            $certificate->save();
        }

        return $certificate;
    }

    public function markIssued(SslCertificate $certificate, ?string $expiresAt = null): void
    {
        $certificate->status = 'issued';
        $certificate->issued_at = now();
        $certificate->expires_at = $expiresAt ? \Carbon\Carbon::parse($expiresAt) : null;
        $certificate->last_error = null;
        $certificate->save();
    }
}
