<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;


    public function test_seeder_creates_admin_with_random_password_when_no_env_set()
    {
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@tastypanel.com',
            'role' => 'superadmin',
            'is_superadmin' => true,
            'force_password_reset' => true,
        ]);

        $user = User::where('email', 'admin@tastypanel.com')->first();
        $this->assertNotNull($user->password);
    }

    public function test_seeder_uses_config_password_when_set()
    {
        $password = 'SecretConfigPassword123!';
        config(['app.admin_password' => $password]);

        $this->seed(AdminUserSeeder::class);

        $user = User::where('email', 'admin@tastypanel.com')->first();
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_seeder_does_not_change_existing_password_if_no_env_set()
    {
        $existingPassword = 'ExistingPassword123!';
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@tastypanel.com',
            'password' => Hash::make($existingPassword),
            'role' => 'superadmin',
            'is_superadmin' => true,
            'force_password_reset' => false,
        ]);

        $this->seed(AdminUserSeeder::class);

        $user->refresh();
        $this->assertTrue(Hash::check($existingPassword, $user->password));
        $this->assertFalse($user->force_password_reset);
    }

    public function test_seeder_updates_existing_password_if_config_set()
    {
        $existingPassword = 'ExistingPassword123!';
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@tastypanel.com',
            'password' => Hash::make($existingPassword),
            'role' => 'superadmin',
            'is_superadmin' => true,
            'force_password_reset' => false,
        ]);

        $newPassword = 'NewConfigPassword123!';
        config(['app.admin_password' => $newPassword]);

        $this->seed(AdminUserSeeder::class);

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertTrue($user->force_password_reset);
    }
}
