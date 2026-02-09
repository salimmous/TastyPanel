<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PinterestService
{
    public function testAccessToken(string $accessToken): array
    {
        $response = $this->client($accessToken)->get('/user_account');

        if ($response->successful()) {
            return ['ok' => true, 'message' => 'Pinterest access token is valid.'];
        }

        $message = data_get($response->json(), 'message') ?: 'Pinterest authentication failed.';

        return ['ok' => false, 'message' => $message];
    }

    private function client(string $accessToken)
    {
        $base = rtrim(config('services.pinterest.base_url', 'https://api.pinterest.com/v5'), '/');

        return Http::withToken($accessToken)
            ->acceptJson()
            ->baseUrl($base)
            ->timeout(20);
    }
}
