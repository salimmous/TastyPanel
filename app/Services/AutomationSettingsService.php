<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class AutomationSettingsService
{
    public function defaults(): array
    {
        return [
            'openai' => [
                'enabled' => false,
                'api_key' => '',
                'model' => 'gpt-4o-mini',
                'temperature' => 0.7,
                'max_tokens' => 1200,
                'system_prompt' => '',
            ],
            'midjourney' => [
                'enabled' => false,
                'bot_token' => '',
                'guild_id' => '',
                'channel_id' => '',
                'webhook_url' => '',
                'default_style' => '',
            ],
            'canva' => [
                'enabled' => false,
                'template_id' => '',
                'brand_kit_id' => '',
                'auto_brand' => true,
                'profile' => [
                    'display_name' => '',
                ],
                'oauth' => [
                    'access_token' => '',
                    'refresh_token' => '',
                    'expires_at' => null,
                    'token_type' => '',
                    'scopes' => [],
                ],
            ],
            'pinterest' => [
                'enabled' => false,
                'access_token' => '',
                'board_id' => '',
                'default_title' => '',
                'default_description' => '',
                'link_format' => 'canonical',
            ],
            'pipeline' => [
                'generate_title' => true,
                'generate_excerpt' => true,
                'generate_image' => true,
                'auto_tag' => true,
                'auto_category' => true,
                'auto_publish' => false,
            ],
            'content' => [
                'topics' => [],
                'language' => 'en',
                'voice' => '',
                'min_words' => 400,
                'max_words' => 900,
            ],
            'schedule' => [
                'enabled' => false,
                'posts_per_day' => 1,
                'publish_status' => 'draft',
                'timezone' => 'UTC',
                'window_start' => '08:00',
                'window_end' => '22:00',
                'environment' => 'production',
            ],
        ];
    }

    public function mergeWithDefaults(array $data): array
    {
        return array_replace_recursive($this->defaults(), $data);
    }

    public function revealSecrets(array $data): array
    {
        $data['openai']['api_key'] = $this->decryptSecret($data['openai']['api_key'] ?? '');
        $data['midjourney']['bot_token'] = $this->decryptSecret($data['midjourney']['bot_token'] ?? '');
        $data['pinterest']['access_token'] = $this->decryptSecret($data['pinterest']['access_token'] ?? '');
        $data['canva']['oauth']['access_token'] = $this->decryptSecret($data['canva']['oauth']['access_token'] ?? '');
        $data['canva']['oauth']['refresh_token'] = $this->decryptSecret($data['canva']['oauth']['refresh_token'] ?? '');

        return $data;
    }

    public function sealSecrets(array $data): array
    {
        $data['openai']['api_key'] = $this->encryptSecret($data['openai']['api_key'] ?? '');
        $data['midjourney']['bot_token'] = $this->encryptSecret($data['midjourney']['bot_token'] ?? '');
        $data['pinterest']['access_token'] = $this->encryptSecret($data['pinterest']['access_token'] ?? '');
        $data['canva']['oauth']['access_token'] = $this->encryptSecret($data['canva']['oauth']['access_token'] ?? '');
        $data['canva']['oauth']['refresh_token'] = $this->encryptSecret($data['canva']['oauth']['refresh_token'] ?? '');

        return $data;
    }

    public function maskSecrets(array $data): array
    {
        $data['openai']['api_key'] = '';
        $data['midjourney']['bot_token'] = '';
        $data['pinterest']['access_token'] = '';

        $data['canva']['oauth']['access_token'] = '';
        $data['canva']['oauth']['refresh_token'] = '';

        return $data;
    }

    public function preserveSecrets(array $current, array $incoming): array
    {
        $secrets = [
            ['openai', 'api_key'],
            ['midjourney', 'bot_token'],
            ['pinterest', 'access_token'],
            ['canva', 'oauth', 'access_token'],
            ['canva', 'oauth', 'refresh_token'],
        ];

        foreach ($secrets as $path) {
            $existing = data_get($current, $path);
            $next = data_get($incoming, $path);
            if ($this->shouldPreserve($existing, $next)) {
                data_set($incoming, $path, $existing);
            }
        }

        return $incoming;
    }

    public function statusSummary(array $data): array
    {
        return [
            'openai' => $this->hasEnabledSecret($data['openai'] ?? [], 'api_key'),
            'midjourney' => $this->hasEnabledSecret($data['midjourney'] ?? [], 'bot_token')
                && ! empty($data['midjourney']['channel_id']),
            'canva' => $this->hasCanvaToken($data['canva'] ?? []),
            'pinterest' => $this->hasEnabledSecret($data['pinterest'] ?? [], 'access_token'),
        ];
    }

    private function shouldPreserve($existing, $next): bool
    {
        if (empty($existing)) {
            return false;
        }
        if ($next === null) {
            return true;
        }
        if ($next === '') {
            return true;
        }

        return false;
    }

    private function hasEnabledSecret(array $config, string $key): bool
    {
        return ! empty($config['enabled']) && ! empty($config[$key]);
    }

    private function hasCanvaToken(array $config): bool
    {
        $oauth = $config['oauth'] ?? [];

        return ! empty($config['enabled']) && ! empty($oauth['access_token']);
    }

    private function encryptSecret(?string $value): ?string
    {
        if (! $value) {
            return $value;
        }
        if (str_starts_with($value, 'enc:')) {
            return $value;
        }

        return 'enc:'.Crypt::encryptString($value);
    }

    private function decryptSecret(?string $value): ?string
    {
        if (! $value) {
            return $value;
        }
        if (! str_starts_with($value, 'enc:')) {
            return $value;
        }
        $payload = substr($value, 4);
        try {
            return Crypt::decryptString($payload);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
