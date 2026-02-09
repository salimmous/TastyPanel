<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditExport extends Model
{
    protected $fillable = [
        'file_path',
        'total_rows',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
