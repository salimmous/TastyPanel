<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'group',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Get default permissions
     */
    public static function getDefaults(): array
    {
        return [
            // Tenants
            ['name' => 'tenants.view', 'display_name' => 'View Tenants', 'group' => 'tenants'],
            ['name' => 'tenants.create', 'display_name' => 'Create Tenants', 'group' => 'tenants'],
            ['name' => 'tenants.edit', 'display_name' => 'Edit Tenants', 'group' => 'tenants'],
            ['name' => 'tenants.delete', 'display_name' => 'Delete Tenants', 'group' => 'tenants'],

            // Content
            ['name' => 'content.view', 'display_name' => 'View Content', 'group' => 'content'],
            ['name' => 'content.create', 'display_name' => 'Create Content', 'group' => 'content'],
            ['name' => 'content.edit', 'display_name' => 'Edit Content', 'group' => 'content'],
            ['name' => 'content.delete', 'display_name' => 'Delete Content', 'group' => 'content'],
            ['name' => 'content.publish', 'display_name' => 'Publish Content', 'group' => 'content'],

            // Users
            ['name' => 'users.view', 'display_name' => 'View Users', 'group' => 'users'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'group' => 'users'],
            ['name' => 'users.edit', 'display_name' => 'Edit Users', 'group' => 'users'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'group' => 'users'],
            ['name' => 'users.roles', 'display_name' => 'Manage User Roles', 'group' => 'users'],

            // Revenue
            ['name' => 'revenue.view', 'display_name' => 'View Revenue', 'group' => 'revenue'],
            ['name' => 'revenue.manage', 'display_name' => 'Manage Billing', 'group' => 'revenue'],

            // Analytics
            ['name' => 'analytics.view', 'display_name' => 'View Analytics', 'group' => 'analytics'],

            // Settings
            ['name' => 'settings.view', 'display_name' => 'View Settings', 'group' => 'settings'],
            ['name' => 'settings.edit', 'display_name' => 'Edit Settings', 'group' => 'settings'],

            // System
            ['name' => 'system.automation', 'display_name' => 'Manage Automation', 'group' => 'system'],
            ['name' => 'system.logs', 'display_name' => 'View System Logs', 'group' => 'system'],
            ['name' => 'system.backups', 'display_name' => 'Manage Backups', 'group' => 'system'],
        ];
    }

    /**
     * Get permissions grouped
     */
    public static function getGrouped(): array
    {
        return self::all()
            ->groupBy('group')
            ->toArray();
    }
}
