<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirewallRule extends Model
{
    protected $fillable = [
        'action',
        'protocol',
        'port',
        'source',
        'description',
        'is_active',
        'applied_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'applied_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
