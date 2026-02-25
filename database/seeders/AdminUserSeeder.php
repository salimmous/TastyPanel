<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = 'Admin123!';

        $user = User::where('email', 'admin@tastypanel.com')->first();

        if (! $user) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@tastypanel.com',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'role' => 'superadmin',
                'is_superadmin' => true,
            ]);
            $this->command->info("Admin user created successfully with password: {$password}");
        } else {
            $user->update([
                'password' => Hash::make($password),
                'role' => 'superadmin',
                'is_superadmin' => true,
            ]);
            $this->command->info("Admin user updated successfully with password: {$password}");
        }
    }
}
