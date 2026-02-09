<?php

namespace App\Services;

use App\Models\Theme;

class BlueprintService
{
    public function all(): array
    {
        $raw = config('blueprints', []);
        $items = [];
        foreach ($raw as $key => $blueprint) {
            $items[] = [
                'id' => $key,
                'name' => $blueprint['name'] ?? $key,
                'description' => $blueprint['description'] ?? '',
            ];
        }
        return $items;
    }

    public function resolve(string $id): ?array
    {
        $raw = config('blueprints', []);
        return $raw[$id] ?? null;
    }

    public function resolveThemeId(?array $blueprint): ?int
    {
        if (!$blueprint) {
            return null;
        }
        if (!empty($blueprint['theme_id'])) {
            return (int) $blueprint['theme_id'];
        }
        if (!empty($blueprint['theme_key'])) {
            return Theme::where('key', $blueprint['theme_key'])->value('id');
        }
        return null;
    }
}
