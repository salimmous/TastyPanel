<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'recipe_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($favorite) {
            $favorite->created_at = now();
        });

        // Update recipe favorites count when created
        static::created(function ($favorite) {
            $favorite->recipe()->increment('favorites_count');
        });

        // Update recipe favorites count when deleted
        static::deleted(function ($favorite) {
            $favorite->recipe()->decrement('favorites_count');
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
}
