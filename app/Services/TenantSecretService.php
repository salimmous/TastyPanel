<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSecret;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

class TenantSecretService
{
    public function listMetadata(Tenant $tenant): Collection
    {
        return $tenant->secrets()
            ->orderBy('secret_key')
            ->get(['id', 'tenant_id', 'secret_key', 'version', 'rotated_at', 'updated_by', 'updated_at', 'created_at']);
    }

    public function setSecret(Tenant $tenant, string $key, string $value, ?int $updatedBy = null): TenantSecret
    {
        $secret = $tenant->secrets()->where('secret_key', $key)->first();
        $isRotation = $secret !== null;

        if (!$secret) {
            $secret = new TenantSecret([
                'tenant_id' => $tenant->id,
                'secret_key' => $key,
                'version' => 1,
            ]);
        } else {
            $secret->version = (int) $secret->version + 1;
        }

        $secret->encrypted_value = Crypt::encryptString($value);
        $secret->updated_by = $updatedBy;
        $secret->rotated_at = $isRotation ? now() : null;
        $secret->save();

        return $secret->fresh();
    }

    public function deleteSecret(Tenant $tenant, string $key): bool
    {
        return (bool) $tenant->secrets()->where('secret_key', $key)->delete();
    }

    public function getSecretValue(Tenant $tenant, string $key): ?string
    {
        $secret = $tenant->secrets()->where('secret_key', $key)->first();
        if (!$secret) {
            return null;
        }

        try {
            return Crypt::decryptString((string) $secret->encrypted_value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function resolveProviderKey(Tenant $tenant, string $provider): ?string
    {
        $normalized = strtolower(trim($provider));
        if ($normalized === '') {
            return null;
        }

        $candidateKeys = [
            $normalized . '.api_key',
            $normalized . '.token',
            $normalized . '.key',
        ];

        foreach ($candidateKeys as $candidate) {
            $value = $this->getSecretValue($tenant, $candidate);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}

