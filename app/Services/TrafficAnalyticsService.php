<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\TenantTrafficMetric;
use App\Models\TrafficLogOffset;
use Carbon\Carbon;

class TrafficAnalyticsService
{
    public function collect(): array
    {
        $summary = [
            'domains' => 0,
            'lines' => 0,
            'metrics' => 0,
        ];

        Domain::with('tenant')->chunkById(50, function ($domains) use (&$summary) {
            foreach ($domains as $domain) {
                $summary['domains']++;
                $processed = $this->processDomain($domain);
                $summary['lines'] += $processed['lines'];
                $summary['metrics'] += $processed['metrics'];
            }
        });

        return $summary;
    }

    private function processDomain(Domain $domain): array
    {
        $path = $this->accessLogPath($domain->hostname);
        if (!$path || !file_exists($path)) {
            return ['lines' => 0, 'metrics' => 0];
        }

        $offset = TrafficLogOffset::firstOrCreate(
            ['domain_id' => $domain->id, 'file_path' => $path],
            ['position' => 0]
        );

        $size = filesize($path) ?: 0;
        if ($size < $offset->position) {
            $offset->position = 0;
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            return ['lines' => 0, 'metrics' => 0];
        }

        fseek($handle, $offset->position);

        $bucket = [];
        $lines = 0;

        while (($line = fgets($handle)) !== false) {
            $lines++;
            $parsed = $this->parseLine($line);
            if (!$parsed) {
                continue;
            }

            [$ip, $date, $status, $bytes] = $parsed;
            $day = $date->toDateString();

            if (!isset($bucket[$day])) {
                $bucket[$day] = [
                    'requests' => 0,
                    'bytes' => 0,
                    'status_4xx' => 0,
                    'status_5xx' => 0,
                    'ips' => [],
                ];
            }

            $bucket[$day]['requests']++;
            $bucket[$day]['bytes'] += $bytes;
            if ($status >= 400 && $status < 500) {
                $bucket[$day]['status_4xx']++;
            }
            if ($status >= 500) {
                $bucket[$day]['status_5xx']++;
            }
            $bucket[$day]['ips'][$ip] = true;
        }

        $offset->position = ftell($handle) ?: $offset->position;
        fclose($handle);
        $offset->save();

        $metrics = 0;
        foreach ($bucket as $day => $data) {
            $metric = TenantTrafficMetric::firstOrNew([
                'tenant_id' => $domain->tenant_id,
                'date' => $day,
            ]);

            $metric->requests = ($metric->requests ?? 0) + $data['requests'];
            $metric->bytes = ($metric->bytes ?? 0) + $data['bytes'];
            $metric->status_4xx = ($metric->status_4xx ?? 0) + $data['status_4xx'];
            $metric->status_5xx = ($metric->status_5xx ?? 0) + $data['status_5xx'];
            $metric->unique_ips = ($metric->unique_ips ?? 0) + count($data['ips']);
            $metric->save();
            $metrics++;
        }

        return ['lines' => $lines, 'metrics' => $metrics];
    }

    private function accessLogPath(string $hostname): ?string
    {
        $template = config('services.logs.nginx_access_template', '/var/log/nginx/%s-access.log');
        if (!str_contains($template, '%s')) {
            return null;
        }
        return sprintf($template, $hostname);
    }

    private function parseLine(string $line): ?array
    {
        if (!preg_match('/^(\S+) \S+ \S+ \[([^\]]+)\] "[^"]*" (\d{3}) (\d+|-)/', $line, $matches)) {
            return null;
        }

        $ip = $matches[1];
        $date = $this->parseDate($matches[2]);
        if (!$date) {
            return null;
        }
        $status = (int) $matches[3];
        $bytes = $matches[4] === '-' ? 0 : (int) $matches[4];

        return [$ip, $date, $status, $bytes];
    }

    private function parseDate(string $value): ?Carbon
    {
        try {
            return Carbon::createFromFormat('d/M/Y:H:i:s O', $value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
