<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Tenant;
use Carbon\Carbon;

class RealtimeAnalyticsService
{
    public function snapshotForTenant(Tenant $tenant, string $environment = 'production', int $lines = 1200): array
    {
        $lines = max(200, min($lines, 5000));
        $domains = Domain::where('tenant_id', $tenant->id)
            ->when($environment === 'staging', function ($q) {
                $q->where('environment', 'staging');
            }, function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('environment')->orWhere('environment', 'production');
                });
            })
            ->get();

        $now = now();
        $cutoff5 = $now->copy()->subMinutes(5);
        $cutoff60 = $now->copy()->subMinutes(60);

        $total = 0;
        $last5 = 0;
        $last60 = 0;
        $status4xx = 0;
        $status5xx = 0;
        $paths = [];

        $reader = app(LogReaderService::class);

        foreach ($domains as $domain) {
            $path = $this->accessLogPath($domain->hostname);
            if (! $path || ! file_exists($path)) {
                continue;
            }

            $content = $reader->tail($path, $lines);
            if (! $content) {
                continue;
            }

            $linesArray = explode("\n", trim($content));
            foreach ($linesArray as $line) {
                $parsed = $this->parseLine($line);
                if (! $parsed) {
                    continue;
                }
                [$date, $status, $pathValue] = $parsed;
                $total++;
                if ($status >= 400 && $status < 500) {
                    $status4xx++;
                } elseif ($status >= 500) {
                    $status5xx++;
                }
                if ($date->greaterThanOrEqualTo($cutoff60)) {
                    $last60++;
                }
                if ($date->greaterThanOrEqualTo($cutoff5)) {
                    $last5++;
                }
                if ($pathValue) {
                    $paths[$pathValue] = ($paths[$pathValue] ?? 0) + 1;
                }
            }
        }

        arsort($paths);
        $topPaths = [];
        foreach (array_slice($paths, 0, 5, true) as $path => $count) {
            $topPaths[] = ['path' => $path, 'count' => $count];
        }

        return [
            'generated_at' => $now->toDateTimeString(),
            'domains' => $domains->count(),
            'requests_total' => $total,
            'requests_5m' => $last5,
            'requests_60m' => $last60,
            'status_4xx' => $status4xx,
            'status_5xx' => $status5xx,
            'top_paths' => $topPaths,
        ];
    }

    private function accessLogPath(string $hostname): ?string
    {
        $template = config('services.logs.nginx_access_template', '/var/log/nginx/%s-access.log');
        if (! str_contains($template, '%s')) {
            return null;
        }

        return sprintf($template, $hostname);
    }

    private function parseLine(string $line): ?array
    {
        if (! preg_match('/^[^\\[]+\\[([^\\]]+)\\] "[A-Z]+ ([^\\s"]+)/', $line, $matches)) {
            return null;
        }

        $date = $this->parseDate($matches[1]);
        if (! $date) {
            return null;
        }

        $status = null;
        if (preg_match('/"\\s(\\d{3})\\s/', $line, $statusMatch)) {
            $status = (int) $statusMatch[1];
        } else {
            $status = 0;
        }

        $path = $matches[2] ?? null;

        return [$date, $status, $path];
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
