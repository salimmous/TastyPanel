<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'admin@tastypanel.com';
        $user = User::where('email', $email)->first();
        
        $configPassword = config('app.admin_password');
        $password = $configPassword;
        $shouldUpdatePassword = false;

        if (!$user) {
            // Create new admin user
            $password = $password ?: Str::random(16);
            $shouldUpdatePassword = true;

            User::create([
                'name' => 'Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'role' => 'superadmin',
                'is_superadmin' => true,
                'force_password_reset' => true,
            ]);
            $this->command->info("Admin user created successfully.");
        } else {
            // Update existing admin user
            if ($configPassword) {
                $user->update([
                    'password' => Hash::make($configPassword),
                    'force_password_reset' => true,
                ]);
                $shouldUpdatePassword = true;
                $this->command->info("Admin user password updated from configuration.");
            }

            $user->update([
                'role' => 'superadmin',
                'is_superadmin' => true,
            ]);
            $this->command->info("Admin user roles and superadmin status ensured.");
        }

        if ($shouldUpdatePassword) {
            $this->command->warn("-----------------------------------------");
            $this->command->warn("Admin Password: {$password}");
            $this->command->warn("Please change this password after login!");
            $this->command->warn("-----------------------------------------");
        }
    }
}
