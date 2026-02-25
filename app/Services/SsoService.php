<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SsoService
{
    public function settings(): array
    {
        $defaults = [
            'sso_enabled' => false,
            'sso_provider_label' => 'SSO',
            'sso_client_id' => '',
            'sso_client_secret' => '',
            'sso_auth_url' => '',
            'sso_token_url' => '',
            'sso_userinfo_url' => '',
            'sso_redirect_url' => config('app.url').'/admin/sso/callback',
            'sso_scopes' => 'openid email profile',
            'sso_allowed_domains' => '',
            'sso_auto_create' => false,
            'sso_enforce' => false,
            'sso_default_role' => 'tenant-admin',
            'sso_default_tenant_id' => null,
        ];

        return array_merge($defaults, PlatformSetting::getData());
    }

    public function buildAuthUrl(string $state): string
    {
        $settings = $this->settings();
        $params = http_build_query([
            'client_id' => $settings['sso_client_id'] ?? '',
            'redirect_uri' => $settings['sso_redirect_url'] ?? '',
            'response_type' => 'code',
            'scope' => $settings['sso_scopes'] ?? 'openid email profile',
            'state' => $state,
        ]);
        $authUrl = rtrim((string) ($settings['sso_auth_url'] ?? ''), '?');
        $separator = str_contains($authUrl, '?') ? '&' : '?';

        return $authUrl.$separator.$params;
    }

    public function exchangeCode(string $code): array
    {
        $settings = $this->settings();
        $response = Http::asForm()->post($settings['sso_token_url'], [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $settings['sso_redirect_url'],
            'client_id' => $settings['sso_client_id'],
            'client_secret' => $settings['sso_client_secret'],
        ]);

        return $response->json() ?? [];
    }

    public function fetchUser(array $tokenData): array
    {
        $settings = $this->settings();
        $accessToken = $tokenData['access_token'] ?? null;
        $userInfoUrl = $settings['sso_userinfo_url'] ?? null;

        if ($userInfoUrl && $accessToken) {
            $response = Http::withToken($accessToken)->get($userInfoUrl);
            $data = $response->json() ?? [];

            return $this->normalizeUser($data);
        }

        if (! empty($tokenData['id_token'])) {
            $payload = $this->decodeJwt($tokenData['id_token']);
            if ($payload) {
                return $this->normalizeUser($payload);
            }
        }

        return [];
    }

    public function allowedDomains(): array
    {
        $domains = (string) ($this->settings()['sso_allowed_domains'] ?? '');

        return array_values(array_filter(array_map('trim', explode(',', $domains))));
    }

    public function state(): string
    {
        return Str::random(40);
    }

    private function normalizeUser(array $data): array
    {
        return [
            'email' => $data['email'] ?? $data['upn'] ?? $data['preferred_username'] ?? null,
            'name' => $data['name'] ?? $data['given_name'] ?? $data['preferred_username'] ?? 'SSO User',
        ];
    }

    private function decodeJwt(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return null;
        }

        $payload = $this->base64UrlDecode($parts[1]);
        if (! $payload) {
            return null;
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            return null;
        }

        return $data;
    }

    private function base64UrlDecode(string $value): string
    {
        $value = str_replace(['-', '_'], ['+', '/'], $value);
        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($value) ?: '';
    }
}
