<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system',
        'level',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')->withTimestamps();
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Sync permissions
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    /**
     * Get default roles
     */
    public static function getDefaults(): array
    {
        return [
            [
                'name' => 'superadmin',
                'display_name' => 'Super Administrator',
                'description' => 'Full access to everything',
                'is_system' => true,
                'level' => 100,
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full access except system settings',
                'is_system' => true,
                'level' => 80,
            ],
            [
                'name' => 'editor',
                'display_name' => 'Editor',
                'description' => 'Can manage content',
                'is_system' => false,
                'level' => 50,
            ],
            [
                'name' => 'support',
                'display_name' => 'Support Staff',
                'description' => 'Can view and help tenants',
                'is_system' => false,
                'level' => 30,
            ],
            [
                'name' => 'viewer',
                'display_name' => 'Viewer',
                'description' => 'Read-only access',
                'is_system' => false,
                'level' => 10,
            ],
        ];
    }
}
