<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeVersion extends Model
{
    protected $fillable = [
        'recipe_id',
        'user_id',
        'version_number',
        'content',
        'change_summary',
        'is_current',
    ];

    protected $casts = [
        'content' => 'array',
        'is_current' => 'boolean',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Create version from recipe
    public static function createFromRecipe(Recipe $recipe, ?int $userId = null, ?string $summary = null): self
    {
        // Get next version number
        $lastVersion = self::where('recipe_id', $recipe->id)
            ->orderByDesc('version_number')
            ->first();

        $versionNumber = ($lastVersion?->version_number ?? 0) + 1;

        // Mark old versions as not current
        self::where('recipe_id', $recipe->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // Create snapshot
        return self::create([
            'recipe_id' => $recipe->id,
            'user_id' => $userId,
            'version_number' => $versionNumber,
            'content' => $recipe->toVersionSnapshot(),
            'change_summary' => $summary,
            'is_current' => true,
        ]);
    }

    // Restore this version to recipe
    public function restore(): void
    {
        $recipe = $this->recipe;
        $content = $this->content;

        // Exclude certain fields
        unset($content['id'], $content['created_at'], $content['updated_at']);

        $recipe->update($content);

        // Create new version marking the restore
        self::createFromRecipe(
            $recipe,
            auth()->id(),
            "Restored from version {$this->version_number}"
        );
    }

    // Get diff with another version
    public function diff(self $other): array
    {
        $changes = [];
        $current = $this->content;
        $previous = $other->content;

        $keys = array_unique(array_merge(array_keys($current), array_keys($previous)));

        foreach ($keys as $key) {
            $oldValue = $previous[$key] ?? null;
            $newValue = $current[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
