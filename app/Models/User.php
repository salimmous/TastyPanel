<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'force_password_reset',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
        'tenant_id',
        'is_superadmin',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
            'force_password_reset' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_expires_at' => 'datetime',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin' || $this->is_superadmin;
    }

    /**
     * User roles (RBAC)
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }
}
