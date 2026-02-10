<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    public function listDnsRecords(string $zoneId, array $query = []): array
    {
        $params = array_filter(array_merge([
            'per_page' => 100,
            'page' => 1,
        ], $query), fn ($v) => $v !== null && $v !== '');

        $response = Http::withToken(config('services.cloudflare.token'))
            ->acceptJson()
            ->get("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records", $params);

        $payload = $response->json();
        if (!$response->ok() || !($payload['success'] ?? false)) {
            $message = 'Cloudflare DNS list failed';
            if (!empty($payload['errors'][0]['message'])) {
                $message = $payload['errors'][0]['message'];
            }
            throw new \RuntimeException($message);
        }

        return $payload['result'] ?? [];
    }

    public function getDnsRecord(string $zoneId, string $recordId): ?array
    {
        $response = Http::withToken(config('services.cloudflare.token'))
            ->acceptJson()
            ->get("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records/{$recordId}");

        if ($response->status() === 404) {
            return null;
        }

        $payload = $response->json();
        if (!$response->ok() || !($payload['success'] ?? false)) {
            $message = 'Cloudflare DNS read failed';
            if (!empty($payload['errors'][0]['message'])) {
                $message = $payload['errors'][0]['message'];
            }
            throw new \RuntimeException($message);
        }

        return $payload['result'] ?? null;
    }

    public function createARecord(string $zoneId, string $hostname, string $ip, bool $proxied = true): string
    {
        $response = Http::withToken(config('services.cloudflare.token'))
            ->acceptJson()
            ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records", [
                'type' => 'A',
                'name' => $hostname,
                'content' => $ip,
                'ttl' => 120,
                'proxied' => $proxied,
            ]);

        $payload = $response->json();

        if (!$response->ok() || !($payload['success'] ?? false)) {
            $errorMessage = 'Cloudflare DNS creation failed';
            if (!empty($payload['errors'][0]['message'])) {
                $errorMessage = $payload['errors'][0]['message'];
            }
            throw new \RuntimeException($errorMessage);
        }

        return $payload['result']['id'] ?? '';
    }

    public function purgeCache(string $zoneId, array $hosts = []): array
    {
        $payload = $hosts ? ['hosts' => $hosts] : ['purge_everything' => true];

        $response = Http::withToken(config('services.cloudflare.token'))
            ->acceptJson()
            ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", $payload);

        $data = $response->json();
        if (!$response->ok() || !($data['success'] ?? false)) {
            $message = 'Cloudflare cache purge failed';
            if (!empty($data['errors'][0]['message'])) {
                $message = $data['errors'][0]['message'];
            }
            throw new \RuntimeException($message);
        }

        return $data['result'] ?? [];
    }

    /**
     * Enable aggressive caching for zone
     */
    public function enableAggressiveCaching(string $zoneId): bool
    {
        try {
            $response = Http::withToken(config('services.cloudflare.token'))
                ->acceptJson()
                ->patch("https://api.cloudflare.com/client/v4/zones/{$zoneId}/settings/cache_level", [
                    'value' => 'aggressive'
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('CloudFlare cache level update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create page rule
     */
    public function createPageRule(string $zoneId, array $config): bool
    {
        try {
            $response = Http::withToken(config('services.cloudflare.token'))
                ->acceptJson()
                ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/pagerules", $config);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('CloudFlare page rule creation failed: ' . $e->getMessage());
            return false;
        }
    }

    public function purgeUrls(string $zoneId, array $urls): bool
    {
        $response = Http::withToken(config('services.cloudflare.token'))
            ->acceptJson()
            ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                'files' => $urls,
            ]);

        $data = $response->json();
        return $response->ok() && ($data['success'] ?? false);
    }

    public function deleteDnsRecord(string $zoneId, string $recordId): bool
    {
        $response = Http::withToken(config('services.cloudflare.token'))
            ->acceptJson()
            ->delete("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records/{$recordId}");

        if ($response->status() === 404) {
            return true;
        }

        $payload = $response->json();
        if (!$response->ok() || !($payload['success'] ?? false)) {
            $message = 'Cloudflare DNS deletion failed';
            if (!empty($payload['errors'][0]['message'])) {
                $message = $payload['errors'][0]['message'];
            }
            throw new \RuntimeException($message);
        }

        return true;
    }
}
