<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficLogOffset extends Model
{
    protected $fillable = [
        'domain_id',
        'file_path',
        'position',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
