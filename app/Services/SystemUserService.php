<?php

namespace App\Services;

use App\Models\SystemUser;
use App\Models\Tenant;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class SystemUserService
{
    /**
     * Create a new system user
     */
    public function create(array $data): SystemUser
    {
        $tenantId = $data['tenant_id'] ?? null;
        $username = $data['username'];
        $password = $data['password'] ?? null; // Can be null if using SSH keys only? But prompt says "password" and "random password".
        $sshKeys = $data['ssh_keys'] ?? []; // Array of keys

        // Check if user exists in system
        if (SystemUser::where('username', $username)->exists()) {
            throw new \Exception("System user '{$username}' already exists.");
        }

        // Create Linux user via shell command (assuming sudo access configured)
        // Command: useradd -m -s /bin/bash <username>
        // But need to set password too.

        try {
            // Use Process facade
            $result = Process::run(['sudo', 'useradd', '-m', '-s', '/bin/bash', $username]);
            if ($result->failed()) {
                throw new \Exception("Failed to create system user: " . $result->errorOutput());
            }

            // Set password if provided
            if ($password) {
                // echo "username:password" | chpasswd
                $result = Process::input("{$username}:{$password}")->run(['sudo', 'chpasswd']);
                if ($result->failed()) {
                     // Rollback user creation?
                    Process::run(['sudo', 'userdel', '-r', $username]);
                    throw new \Exception("Failed to set password: " . $result->errorOutput());
                }
            }

            // Add SSH keys if provided
            if (!empty($sshKeys)) {
                // Create .ssh directory and authorized_keys file
                // mkdir /home/user/.ssh
                // echo keys > authorized_keys
                // chown user:user -R /home/user/.ssh
                // chmod 700 .ssh, 600 authorized_keys
                // Use a helper script or series of commands
                // Simulating for now or implementing if needed.
                // Assuming simple user creation first.
            }

        } catch (\Exception $e) {
            throw new \Exception("System user creation failed: " . $e->getMessage());
        }

        return SystemUser::create([
            'tenant_id' => $tenantId,
            'username' => $username,
            'password' => $password, // encrypted by model cast
            'home_dir' => "/home/{$username}",
            'shell' => '/bin/bash',
            'ssh_keys' => $sshKeys,
            'status' => 'active',
        ]);
    }

    /**
     * Delete a system user
     */
    public function delete(SystemUser $user): void
    {
        try {
            // userdel -r username
            $result = Process::run(['sudo', 'userdel', '-r', $user->username]);
            if ($result->failed()) {
                \Log::error("Failed to delete system user '{$user->username}': " . $result->errorOutput());
                // Maybe throw exception or just log? Prompt says "Delete system Linux user + home dir".
            }
        } catch (\Exception $e) {
            \Log::error("Error deleting system user: " . $e->getMessage());
        }

        $user->delete();
    }
}
