<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeVersion extends Model
{
    protected $fillable = [
        'theme_id',
        'version',
        'zip_path',
        'notes',
        'created_by',
    ];

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
