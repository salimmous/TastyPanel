<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealPlanItem extends Model
{
    protected $fillable = [
        'meal_plan_id',
        'recipe_id',
        'planned_date',
        'meal_type',
        'servings',
        'notes',
        'is_completed',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'is_completed' => 'boolean',
    ];

    const MEAL_TYPES = ['breakfast', 'lunch', 'dinner', 'snack'];

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    // Scopes
    public function scopeForDate($query, string $date)
    {
        return $query->where('planned_date', $date);
    }

    public function scopeForMealType($query, string $type)
    {
        return $query->where('meal_type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    // Methods
    public function markCompleted(): void
    {
        $this->update(['is_completed' => true]);
    }

    public function markPending(): void
    {
        $this->update(['is_completed' => false]);
    }

    // Get scaled ingredients for servings
    public function getScaledIngredients(): array
    {
        if (!$this->recipe || !$this->recipe->ingredients) {
            return [];
        }

        $baseServings = $this->recipe->servings ?? 1;
        $multiplier = $this->servings / $baseServings;

        $ingredients = [];
        foreach ($this->recipe->ingredients as $ingredient) {
            $scaled = $ingredient;
            if (isset($ingredient['quantity']) && is_numeric($ingredient['quantity'])) {
                $scaled['quantity'] = round($ingredient['quantity'] * $multiplier, 2);
            }
            $ingredients[] = $scaled;
        }

        return $ingredients;
    }
}
