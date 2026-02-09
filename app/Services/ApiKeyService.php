<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\Tenant;

class ApiKeyService
{
    public function create(
        Tenant $tenant,
        string $name,
        array $scopes = [],
        ?int $userId = null,
        int $rateLimit = 0,
        ?string $expiresAt = null
    ): array
    {
        $plain = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plain);
        $prefix = substr($plain, 0, 12);

        $key = ApiKey::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'token_prefix' => $prefix,
            'token_hash' => $hash,
            'scopes' => $scopes,
            'rate_limit_per_minute' => $rateLimit,
            'expires_at' => $expiresAt,
            'created_by' => $userId,
        ]);

        return [$key, $plain];
    }

    public function verify(string $token): ?ApiKey
    {
        if (!$token) {
            return null;
        }

        $prefix = substr($token, 0, 12);
        $hash = hash('sha256', $token);

        $key = ApiKey::where('token_prefix', $prefix)
            ->where('token_hash', $hash)
            ->first();
        if (!$key || !$key->isActive()) {
            return null;
        }

        return $key;
    }
}
