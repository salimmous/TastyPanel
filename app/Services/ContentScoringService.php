<?php

namespace App\Services;

class ContentScoringService
{
    public function score(string $title, string $body): array
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($body)));
        $wordCount = $this->wordCount($text);
        $sentenceCount = $this->sentenceCount($text);
        $syllableCount = $this->syllableCount($text);

        $readability = $this->fleschScore($wordCount, $sentenceCount, $syllableCount);
        $seo = $this->seoScore($title, $text, $wordCount);
        $readingTime = $wordCount > 0 ? (int) max(1, ceil($wordCount / 200)) : null;

        return [
            'readability_score' => $readability,
            'seo_score' => $seo,
            'word_count' => $wordCount ?: null,
            'reading_time_minutes' => $readingTime,
            'language' => $this->detectLanguage($title . ' ' . $text),
        ];
    }

    private function wordCount(string $text): int
    {
        if ($text === '') {
            return 0;
        }
        return count(preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY));
    }

    private function sentenceCount(string $text): int
    {
        if ($text === '') {
            return 0;
        }
        $count = preg_match_all('/[.!?]+/', $text, $matches);
        return max(1, (int) $count);
    }

    private function syllableCount(string $text): int
    {
        if ($text === '') {
            return 0;
        }
        $words = preg_split('/\s+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        $count = 0;
        foreach ($words as $word) {
            $word = preg_replace('/[^a-z]/', '', $word);
            if ($word === '') {
                continue;
            }
            $count += $this->estimateSyllables($word);
        }
        return max(1, $count);
    }

    private function estimateSyllables(string $word): int
    {
        $word = preg_replace('/e$/', '', $word);
        $groups = preg_match_all('/[aeiouy]+/', $word, $matches);
        return max(1, (int) $groups);
    }

    private function fleschScore(int $words, int $sentences, int $syllables): int
    {
        if ($words === 0 || $sentences === 0) {
            return 0;
        }
        $score = 206.835 - 1.015 * ($words / $sentences) - 84.6 * ($syllables / max(1, $words));
        $score = (int) round($score);
        return max(0, min(100, $score));
    }

    private function seoScore(string $title, string $body, int $wordCount): int
    {
        $score = 0;

        $titleLength = mb_strlen(trim($title));
        if ($titleLength >= 35 && $titleLength <= 70) {
            $score += 30;
        } elseif ($titleLength >= 20 && $titleLength <= 90) {
            $score += 20;
        } elseif ($titleLength > 0) {
            $score += 10;
        }

        if ($wordCount >= 600 && $wordCount <= 2000) {
            $score += 40;
        } elseif ($wordCount >= 300 && $wordCount < 600) {
            $score += 30;
        } elseif ($wordCount >= 150) {
            $score += 20;
        }

        $keywords = $this->extractKeywords($title);
        $bodyLower = strtolower($body);
        foreach ($keywords as $keyword) {
            if ($keyword && str_contains($bodyLower, $keyword)) {
                $score += 10;
            }
        }

        return max(0, min(100, $score));
    }

    private function extractKeywords(string $title): array
    {
        $stop = [
            'the', 'and', 'or', 'for', 'with', 'from', 'that', 'this', 'your', 'you', 'are',
            'to', 'in', 'of', 'on', 'at', 'by', 'an', 'a', 'how', 'why', 'what', 'when', 'where',
        ];
        $tokens = preg_split('/\s+/', strtolower($title), -1, PREG_SPLIT_NO_EMPTY);
        $keywords = [];
        foreach ($tokens as $token) {
            $token = preg_replace('/[^a-z0-9]/', '', $token);
            if ($token === '' || in_array($token, $stop, true)) {
                continue;
            }
            $keywords[] = $token;
        }
        return array_slice(array_unique($keywords), 0, 3);
    }

    private function detectLanguage(string $text): ?string
    {
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'ar';
        }
        if (preg_match('/[a-zA-Z]/', $text)) {
            return 'en';
        }
        return null;
    }
}
