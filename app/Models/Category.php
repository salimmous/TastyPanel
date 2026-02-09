<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'tenant_id',
        'environment',
        'slug',
        'name',
        'image',
        'description',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }
}
