<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingListItem extends Model
{
    protected $fillable = [
        'shopping_list_id',
        'recipe_id',
        'name',
        'quantity',
        'unit',
        'category',
        'is_checked',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'is_checked' => 'boolean',
    ];

    const CATEGORIES = [
        'produce' => 'Produce',
        'dairy' => 'Dairy & Eggs',
        'meat' => 'Meat & Seafood',
        'bakery' => 'Bakery',
        'frozen' => 'Frozen',
        'pantry' => 'Pantry',
        'beverages' => 'Beverages',
        'condiments' => 'Condiments & Sauces',
        'spices' => 'Spices & Seasonings',
        'other' => 'Other',
    ];

    public function shoppingList(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    // Methods
    public function check(): void
    {
        $this->update(['is_checked' => true]);
        $this->shoppingList->updateCounts();
    }

    public function uncheck(): void
    {
        $this->update(['is_checked' => false]);
        $this->shoppingList->updateCounts();
    }

    public function toggle(): bool
    {
        $this->is_checked = ! $this->is_checked;
        $this->save();
        $this->shoppingList->updateCounts();

        return $this->is_checked;
    }

    public function getDisplayName(): string
    {
        $parts = [$this->name];
        if ($this->quantity) {
            array_unshift($parts, $this->quantity.($this->unit ? ' '.$this->unit : ''));
        }

        return implode(' ', $parts);
    }
}
