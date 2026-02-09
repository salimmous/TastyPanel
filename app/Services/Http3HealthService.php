<?php

namespace App\Services;

use App\Models\Domain;

class Http3HealthService
{
    public function check(Domain $domain): Domain
    {
        $udp = $this->checkUdp($domain);

        if (!$domain->http3_enabled) {
            $domain->http3_status = 'disabled';
            $domain->http3_error = null;
            $domain->http3_checked_at = now();
            $domain->http3_udp_status = $udp['status'];
            $domain->http3_udp_error = $udp['error'];
            $domain->http3_udp_checked_at = now();
            $domain->save();
            return $domain;
        }

        if ($domain->sslCertificate && $domain->sslCertificate->status !== 'issued') {
            $domain->http3_status = 'error';
            $domain->http3_error = 'SSL certificate is not issued yet.';
            $domain->http3_checked_at = now();
            $domain->http3_udp_status = $udp['status'];
            $domain->http3_udp_error = $udp['error'];
            $domain->http3_udp_checked_at = now();
            $domain->save();
            return $domain;
        }

        $url = 'https://' . $domain->hostname;
        $timeout = 8;

        $result = $this->runCurl("--http3 -I --max-time {$timeout} --connect-timeout 5", $url);
        if ($result['supported'] && $result['exitCode'] === 0) {
            $domain->http3_status = 'ok';
            $domain->http3_error = null;
            $domain->http3_checked_at = now();
            $domain->save();
            return $domain;
        }

        $fallback = $this->runCurl("-I --max-time {$timeout} --connect-timeout 5", $url);
        if ($fallback['exitCode'] === 0 && $this->hasAltSvcHeader($fallback['output'])) {
            $domain->http3_status = 'advertised';
            $domain->http3_error = null;
        } else {
            $domain->http3_status = 'error';
            $domain->http3_error = $result['error'] ?: $fallback['error'] ?: 'HTTP/3 check failed.';
        }

        $domain->http3_checked_at = now();
        $domain->http3_udp_status = $udp['status'];
        $domain->http3_udp_error = $udp['error'];
        $domain->http3_udp_checked_at = now();
        $domain->save();

        return $domain;
    }

    private function runCurl(string $args, string $url): array
    {
        $cmd = sprintf('curl %s %s 2>&1', $args, escapeshellarg($url));
        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);
        $text = implode("\n", $output);

        $supported = true;
        if (str_contains($text, 'unknown option') || str_contains($text, 'not built with')) {
            $supported = false;
        }

        return [
            'exitCode' => $exitCode,
            'output' => $text,
            'supported' => $supported,
            'error' => $exitCode === 0 ? null : $text,
        ];
    }

    private function hasAltSvcHeader(string $output): bool
    {
        foreach (explode("\n", strtolower($output)) as $line) {
            if (str_starts_with(trim($line), 'alt-svc') && str_contains($line, 'h3')) {
                return true;
            }
        }

        return false;
    }

    private function checkUdp(Domain $domain): array
    {
        if (!$domain->http3_enabled) {
            return [
                'status' => 'disabled',
                'error' => null,
            ];
        }

        $host = $domain->hostname;
        $hostArg = escapeshellarg($host);
        $cmd = sprintf(
            'sh -c %s',
            escapeshellarg('if command -v nc >/dev/null 2>&1; then nc -zu -w2 ' . $hostArg . ' 443; else exit 127; fi')
        );

        $output = [];
        $exitCode = 0;
        exec($cmd . ' 2>&1', $output, $exitCode);

        if ($exitCode === 0) {
            return [
                'status' => 'ok',
                'error' => null,
            ];
        }

        if ($exitCode === 127) {
            return [
                'status' => 'unsupported',
                'error' => 'netcat (nc) not installed',
            ];
        }

        return [
            'status' => 'error',
            'error' => trim(implode("\n", $output)) ?: 'UDP 443 check failed.',
        ];
    }
}
