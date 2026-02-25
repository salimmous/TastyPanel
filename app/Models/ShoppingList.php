<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShoppingList extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'meal_plan_id',
        'name',
        'shop_date',
        'is_completed',
        'items_count',
        'checked_count',
    ];

    protected $casts = [
        'shop_date' => 'date',
        'is_completed' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class)->orderBy('category')->orderBy('sort_order');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function updateCounts(): void
    {
        $this->items_count = $this->items()->count();
        $this->checked_count = $this->items()->where('is_checked', true)->count();
        $this->is_completed = $this->items_count > 0 && $this->items_count === $this->checked_count;
        $this->save();
    }

    public function addItem(array $data): ShoppingListItem
    {
        $item = $this->items()->create($data);
        $this->increment('items_count');

        return $item;
    }

    public function getProgress(): int
    {
        if ($this->items_count === 0) {
            return 0;
        }

        return (int) round(($this->checked_count / $this->items_count) * 100);
    }

    public function getGroupedItems(): array
    {
        return $this->items
            ->groupBy('category')
            ->toArray();
    }
}
