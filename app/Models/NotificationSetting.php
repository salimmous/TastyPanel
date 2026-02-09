<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'email_enabled',
        'in_app_enabled',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default settings for all categories
     */
    public static function getDefaults(): array
    {
        return [
            'system' => [
                'email_enabled' => true,
                'in_app_enabled' => true,
            ],
            'security' => [
                'email_enabled' => true,
                'in_app_enabled' => true,
            ],
            'content' => [
                'email_enabled' => false,
                'in_app_enabled' => true,
            ],
            'webhook' => [
                'email_enabled' => false,
                'in_app_enabled' => true,
            ],
            'job' => [
                'email_enabled' => false,
                'in_app_enabled' => true,
            ],
            'performance' => [
                'email_enabled' => true,
                'in_app_enabled' => true,
            ],
        ];
    }

    /**
     * Initialize default settings for a user
     */
    public static function initializeForUser(int $userId): void
    {
        foreach (self::getDefaults() as $category => $settings) {
            self::firstOrCreate(
                ['user_id' => $userId, 'category' => $category],
                $settings
            );
        }
    }
}
