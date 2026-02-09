<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'tenant_id',
        'environment',
        'slug',
        'category_id',
        'title',
        'description',
        'image',
        'prep_time',
        'cook_time',
        'servings',
        'ingredients',
        'instructions',
        'nutrition',
        'status',
        'reviewed_at',
        'approved_at',
        'published_at',
        'readability_score',
        'seo_score',
        'word_count',
        'reading_time_minutes',
        'language',
    ];

    protected $casts = [
        'ingredients' => 'array',
        'instructions' => 'array',
        'nutrition' => 'array',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
