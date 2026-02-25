<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAiService
{
    public function testConnection(string $apiKey, array $settings): array
    {
        $payload = $this->buildPayload($settings, 'Reply with "ok".');
        $response = $this->client($apiKey)->post('/responses', $payload);

        if ($response->successful()) {
            return ['ok' => true, 'message' => 'OpenAI responded successfully.'];
        }

        $message = data_get($response->json(), 'error.message') ?: 'OpenAI request failed.';

        return ['ok' => false, 'message' => $message];
    }

    public function generateDraft(string $apiKey, array $settings, string $title, string $summary = ''): ?string
    {
        $prompt = "Write a concise blog draft for: {$title}.";
        if ($summary) {
            $prompt .= "\n\nNotes:\n{$summary}";
        }
        $prompt .= "\n\nReturn 2 to 4 short paragraphs.";

        $payload = $this->buildPayload($settings, $prompt);
        $response = $this->client($apiKey)->post('/responses', $payload);

        if (! $response->successful()) {
            return null;
        }

        $text = $this->extractText($response->json());

        return $text ? trim($text) : null;
    }

    public function generateTitle(string $apiKey, array $settings, string $topic, string $language = 'en', string $voice = ''): ?string
    {
        $prompt = "Generate a short, catchy blog title about: {$topic}.";
        if ($language) {
            $prompt .= "\nLanguage: {$language}.";
        }
        if ($voice) {
            $prompt .= "\nTone: {$voice}.";
        }
        $prompt .= "\nReturn only the title.";

        $payload = $this->buildPayload($settings, $prompt);
        $response = $this->client($apiKey)->post('/responses', $payload);

        if (! $response->successful()) {
            return null;
        }

        $text = $this->extractText($response->json());

        return $text ? trim($text, "\" \n\t") : null;
    }

    public function generateArticle(
        string $apiKey,
        array $settings,
        string $title,
        string $topic,
        string $language = 'en',
        string $voice = '',
        int $minWords = 400,
        int $maxWords = 900
    ): ?string {
        $prompt = "Write a blog article titled \"{$title}\".";
        if ($topic && $topic !== $title) {
            $prompt .= "\nTopic: {$topic}.";
        }
        if ($language) {
            $prompt .= "\nLanguage: {$language}.";
        }
        if ($voice) {
            $prompt .= "\nTone: {$voice}.";
        }
        $prompt .= "\nTarget length: {$minWords} to {$maxWords} words.";
        $prompt .= "\nReturn plain text paragraphs.";

        $payload = $this->buildPayload($settings, $prompt);
        $response = $this->client($apiKey)->post('/responses', $payload);

        if (! $response->successful()) {
            return null;
        }

        $text = $this->extractText($response->json());

        return $text ? trim($text) : null;
    }

    private function buildPayload(array $settings, string $prompt): array
    {
        $input = [];
        $systemPrompt = $settings['openai']['system_prompt'] ?? '';
        if ($systemPrompt) {
            $input[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $input[] = ['role' => 'user', 'content' => $prompt];

        return [
            'model' => $settings['openai']['model'] ?? 'gpt-4o-mini',
            'input' => $input,
            'temperature' => $settings['openai']['temperature'] ?? 0.7,
            'max_output_tokens' => $settings['openai']['max_tokens'] ?? 1200,
        ];
    }

    private function extractText(array $payload): ?string
    {
        if (! empty($payload['output_text'])) {
            return $payload['output_text'];
        }

        $output = $payload['output'] ?? [];
        $chunks = [];

        foreach ($output as $item) {
            $content = $item['content'] ?? [];
            foreach ($content as $block) {
                if (($block['type'] ?? '') === 'output_text' && isset($block['text'])) {
                    $chunks[] = $block['text'];
                } elseif (isset($block['text'])) {
                    $chunks[] = $block['text'];
                }
            }
        }

        if (! $chunks) {
            return null;
        }

        return implode("\n", $chunks);
    }

    private function client(string $apiKey)
    {
        $base = rtrim(config('services.openai.base_url', 'https://api.openai.com/v1'), '/');

        return Http::withToken($apiKey)
            ->acceptJson()
            ->baseUrl($base)
            ->timeout(30);
    }
}
