<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = [
        'key',
        'name',
        'view',
        'description',
        'category',
        'tags',
        'author',
        'version',
        'preview_image',
        'is_featured',
        'is_marketplace',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_marketplace' => 'boolean',
        'tags' => 'array',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function versions()
    {
        return $this->hasMany(ThemeVersion::class);
    }
}
