<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityBaseline extends Model
{
    protected $fillable = [
        'name',
        'root_path',
        'paths',
        'hashes',
        'created_by',
    ];

    protected $casts = [
        'paths' => 'array',
        'hashes' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checks()
    {
        return $this->hasMany(SecurityIntegrityCheck::class);
    }
}
