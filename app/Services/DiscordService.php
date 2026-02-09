<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DiscordService
{
    public function testBotToken(string $botToken): array
    {
        $response = $this->client($botToken)->get('/users/@me');

        if ($response->successful()) {
            return ['ok' => true, 'message' => 'Discord bot token is valid.'];
        }

        $message = data_get($response->json(), 'message') ?: 'Discord authentication failed.';

        return ['ok' => false, 'message' => $message];
    }

    public function sendMessage(string $botToken, string $channelId, string $content): array
    {
        $response = $this->client($botToken)->post("/channels/{$channelId}/messages", [
            'content' => $content,
        ]);

        if ($response->successful()) {
            return ['ok' => true, 'message' => 'Message delivered.'];
        }

        $message = data_get($response->json(), 'message') ?: 'Discord message failed.';

        return ['ok' => false, 'message' => $message];
    }

    private function client(string $botToken)
    {
        $base = rtrim(config('services.discord.base_url', 'https://discord.com/api/v10'), '/');

        return Http::withToken($botToken, 'Bot')
            ->acceptJson()
            ->baseUrl($base)
            ->timeout(20);
    }
}
