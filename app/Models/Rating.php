<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $fillable = [
        'user_id',
        'recipe_id',
        'tenant_id',
        'rating',
        'review',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        // Update recipe rating stats when created or updated
        static::saved(function ($rating) {
            $rating->updateRecipeRating();
        });

        // Update recipe rating stats when deleted
        static::deleted(function ($rating) {
            $rating->updateRecipeRating();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Update recipe average rating and count
     */
    protected function updateRecipeRating(): void
    {
        $recipe = $this->recipe;

        if (! $recipe) {
            return;
        }

        $stats = Rating::where('recipe_id', $recipe->id)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_ratings')
            ->first();

        $recipe->update([
            'average_rating' => round($stats->avg_rating ?? 0, 2),
            'rating_count' => $stats->total_ratings ?? 0,
        ]);
    }

    /**
     * Get rating distribution for a recipe
     */
    public static function getDistribution(int $recipeId): array
    {
        $distribution = self::where('recipe_id', $recipeId)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Ensure all ratings 1-5 are present
        return [
            5 => $distribution[5] ?? 0,
            4 => $distribution[4] ?? 0,
            3 => $distribution[3] ?? 0,
            2 => $distribution[2] ?? 0,
            1 => $distribution[1] ?? 0,
        ];
    }
}
