<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainNginxVersion extends Model
{
    protected $fillable = [
        'domain_id',
        'config',
        'source',
        'created_by',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
