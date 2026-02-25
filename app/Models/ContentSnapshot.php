<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentSnapshot extends Model
{
    protected $fillable = [
        'tenant_id',
        'environment',
        'label',
        'description',
        'created_by',
        'total_categories',
        'total_recipes',
        'total_articles',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
