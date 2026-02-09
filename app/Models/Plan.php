<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price_cents',
        'interval',
        'is_active',
        'limits',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'limits' => 'array',
    ];

    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class);
    }
}
