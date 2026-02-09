<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CanvaService
{
    private const CACHE_PREFIX = 'canva_oauth:';

    public function buildAuthorizationUrl(int $tenantId, string $environment, int $userId): string
    {
        $clientId = $this->clientId();
        if (!$clientId) {
            throw new \RuntimeException('Canva client ID is missing.');
        }

        $state = Str::uuid()->toString();
        $verifier = $this->makeVerifier();
        $challenge = $this->codeChallenge($verifier);
        $redirect = $this->redirectUri();

        Cache::put($this->cacheKey($state), [
            'tenant_id' => $tenantId,
            'environment' => $environment,
            'user_id' => $userId,
            'code_verifier' => $verifier,
            'redirect_uri' => $redirect,
        ], now()->addMinutes(10));

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirect,
            'scope' => $this->scopes(),
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 's256',
        ], '', '&', PHP_QUERY_RFC3986);

        return rtrim($this->authUrl(), '?') . '?' . $query;
    }

    public function consumeState(string $state): ?array
    {
        return Cache::pull($this->cacheKey($state));
    }

    public function exchangeCode(string $code, array $stateData): array
    {
        $clientId = $this->clientId();
        $clientSecret = $this->clientSecret();

        $client = Http::asForm()->timeout(30);
        if ($clientId && $clientSecret) {
            $client = $client->withBasicAuth($clientId, $clientSecret);
        }

        $response = $client->post($this->tokenUrl(), [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'code_verifier' => $stateData['code_verifier'] ?? '',
            'redirect_uri' => $stateData['redirect_uri'] ?? $this->redirectUri(),
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (!$response->successful()) {
            $message = data_get($response->json(), 'error_description')
                ?: data_get($response->json(), 'message')
                ?: 'Failed to exchange Canva code.';
            throw new \RuntimeException($message);
        }

        return $response->json();
    }

    public function refreshToken(string $refreshToken): array
    {
        $clientId = $this->clientId();
        $clientSecret = $this->clientSecret();
        $client = Http::asForm()->timeout(30);
        if ($clientId && $clientSecret) {
            $client = $client->withBasicAuth($clientId, $clientSecret);
        }

        $response = $client->post($this->tokenUrl(), [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (!$response->successful()) {
            $message = data_get($response->json(), 'error_description')
                ?: data_get($response->json(), 'message')
                ?: 'Failed to refresh Canva token.';
            throw new \RuntimeException($message);
        }

        return $response->json();
    }

    public function getUserProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(20)
            ->get($this->apiBase() . '/users/me');

        if (!$response->successful()) {
            $message = data_get($response->json(), 'message') ?: 'Failed to fetch Canva profile.';
            throw new \RuntimeException($message);
        }

        return $response->json();
    }

    private function authUrl(): string
    {
        return config('services.canva.auth_url', 'https://www.canva.com/api/oauth/authorize');
    }

    private function tokenUrl(): string
    {
        return config('services.canva.token_url', 'https://api.canva.com/rest/v1/oauth/token');
    }

    private function apiBase(): string
    {
        return rtrim(config('services.canva.api_base', 'https://api.canva.com/rest/v1'), '/');
    }

    private function clientId(): ?string
    {
        return config('services.canva.client_id');
    }

    private function clientSecret(): ?string
    {
        return config('services.canva.client_secret');
    }

    private function redirectUri(): string
    {
        return config('services.canva.redirect_uri') ?: url('/admin/automation/canva/callback');
    }

    private function scopes(): string
    {
        return config('services.canva.scopes', '');
    }

    private function cacheKey(string $state): string
    {
        return self::CACHE_PREFIX . $state;
    }

    private function makeVerifier(): string
    {
        return $this->base64UrlEncode(random_bytes(64));
    }

    private function codeChallenge(string $verifier): string
    {
        return $this->base64UrlEncode(hash('sha256', $verifier, true));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
