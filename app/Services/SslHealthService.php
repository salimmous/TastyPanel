<?php

namespace App\Services;

use App\Models\SslCertificate;
use Illuminate\Support\Collection;

class SslHealthService
{
    public function updateExpiry(SslCertificate $certificate): ?\Carbon\Carbon
    {
        $meta = $certificate->meta ?? [];
        $certPath = $meta['cert_path'] ?? null;
        if (! $certPath || ! file_exists($certPath)) {
            return null;
        }

        $content = file_get_contents($certPath);
        if (! $content) {
            return null;
        }

        $parsed = openssl_x509_parse($content);
        if (! $parsed || empty($parsed['validTo_time_t'])) {
            return null;
        }

        $expiresAt = \Carbon\Carbon::createFromTimestamp($parsed['validTo_time_t']);
        $certificate->expires_at = $expiresAt;
        $certificate->save();

        return $expiresAt;
    }

    public function expiringSoon(int $days = 14): Collection
    {
        $cutoff = now()->addDays($days);
        $certs = SslCertificate::with('domain')
            ->where('status', 'issued')
            ->get();

        $results = collect();
        foreach ($certs as $cert) {
            if (! $cert->expires_at) {
                $this->updateExpiry($cert);
            }
            if ($cert->expires_at && $cert->expires_at->lessThanOrEqualTo($cutoff)) {
                $results->push($cert);
            }
        }

        return $results;
    }
}
