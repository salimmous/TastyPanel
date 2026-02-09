<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Collection extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'slug',
        'description',
        'cover_image',
        'is_public',
        'recipes_count',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($collection) {
            if (empty($collection->slug)) {
                $collection->slug = Str::slug($collection->name);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'collection_recipe')
            ->withPivot(['sort_order', 'notes', 'added_at'])
            ->orderByPivot('sort_order');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function addRecipe(int $recipeId, ?string $notes = null): void
    {
        if (!$this->recipes()->where('recipe_id', $recipeId)->exists()) {
            $maxOrder = $this->recipes()->max('collection_recipe.sort_order') ?? 0;
            $this->recipes()->attach($recipeId, [
                'sort_order' => $maxOrder + 1,
                'notes' => $notes,
                'added_at' => now(),
            ]);
            $this->increment('recipes_count');
        }
    }

    public function removeRecipe(int $recipeId): void
    {
        if ($this->recipes()->detach($recipeId)) {
            $this->decrement('recipes_count');
        }
    }

    public function reorderRecipes(array $recipeIds): void
    {
        foreach ($recipeIds as $order => $recipeId) {
            $this->recipes()->updateExistingPivot($recipeId, [
                'sort_order' => $order,
            ]);
        }
    }
}
