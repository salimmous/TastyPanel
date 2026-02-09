<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'webhook_id',
        'event',
        'attempt',
        'status',
        'successful',
        'response',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'successful' => 'boolean',
    ];

    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
}
