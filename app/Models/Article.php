<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'tenant_id',
        'environment',
        'slug',
        'title',
        'description',
        'image',
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
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
