<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantMailbox;

class TenantMailboxService
{
    public function listForTenant(Tenant $tenant)
    {
        return $tenant->mailboxes()->orderBy('email')->get();
    }

    public function createMailbox(Tenant $tenant, array $data): array
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $quotaMb = (int) ($data['quota_mb'] ?? 1024);
        $quotaMb = max(128, $quotaMb);
        $password = trim((string) ($data['password'] ?? ''));
        if ($password === '') {
            $password = $this->generatePassword();
        }

        $result = $this->runScript('create', $tenant, [
            'MAILBOX_EMAIL' => $email,
            'MAILBOX_QUOTA_MB' => (string) $quotaMb,
            'MAILBOX_PASSWORD' => $password,
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        $mailbox = $tenant->mailboxes()->updateOrCreate(
            ['email' => $email],
            [
                'mailbox_path' => $result['meta']['MAILBOX_PATH'] ?? null,
                'quota_mb' => (int) ($result['meta']['QUOTA_MB'] ?? $quotaMb),
                'is_active' => true,
                'meta' => [
                    'provider' => 'local',
                ],
            ]
        );

        return [
            'success' => true,
            'mailbox' => $mailbox,
            'password' => $result['meta']['PASSWORD'] ?? $password,
            'output' => $result['output'] ?? '',
        ];
    }

    public function resetPassword(Tenant $tenant, TenantMailbox $mailbox, ?string $password = null): array
    {
        $password = trim((string) ($password ?? ''));
        if ($password === '') {
            $password = $this->generatePassword();
        }

        $result = $this->runScript('reset-password', $tenant, [
            'MAILBOX_EMAIL' => $mailbox->email,
            'MAILBOX_QUOTA_MB' => (string) ($mailbox->quota_mb ?: 1024),
            'MAILBOX_PASSWORD' => $password,
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        return [
            'success' => true,
            'password' => $result['meta']['PASSWORD'] ?? $password,
            'output' => $result['output'] ?? '',
        ];
    }

    public function deleteMailbox(Tenant $tenant, TenantMailbox $mailbox): array
    {
        $result = $this->runScript('delete', $tenant, [
            'MAILBOX_EMAIL' => $mailbox->email,
            'MAILBOX_QUOTA_MB' => (string) ($mailbox->quota_mb ?: 1024),
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        $mailbox->delete();

        return [
            'success' => true,
            'output' => $result['output'] ?? '',
        ];
    }

    public function refreshUsage(Tenant $tenant, TenantMailbox $mailbox): array
    {
        $result = $this->runScript('usage', $tenant, [
            'MAILBOX_EMAIL' => $mailbox->email,
            'MAILBOX_QUOTA_MB' => (string) ($mailbox->quota_mb ?: 1024),
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        $usageBytes = isset($result['meta']['USAGE_BYTES']) ? (int) $result['meta']['USAGE_BYTES'] : null;
        $mailbox->last_usage_bytes = $usageBytes;
        $mailbox->last_usage_checked_at = now();
        $mailbox->save();

        return [
            'success' => true,
            'mailbox' => $mailbox->fresh(),
            'usage_bytes' => $usageBytes,
            'output' => $result['output'] ?? '',
        ];
    }

    private function runScript(string $action, Tenant $tenant, array $envVars): array
    {
        $script = (string) config('services.mailboxes.script');
        if ($script === '' || !file_exists($script)) {
            return [
                'success' => false,
                'output' => 'Mailbox script not found.',
            ];
        }

        $mailboxRoot = (string) config('services.mailboxes.root', '/var/mail/tastypanel');
        $usersFile = (string) config('services.mailboxes.users_file', '/etc/dovecot/tastypanel-users');
        $osUser = (string) config('services.mailboxes.os_user', 'vmail');
        $osGroup = (string) config('services.mailboxes.os_group', 'vmail');

        $parts = [];
        if (config('services.mailboxes.use_sudo', true)) {
            $parts[] = 'sudo';
            $parts[] = '-n';
        }
        $parts[] = 'env';
        $parts[] = 'TENANT_KEY=' . (string) ($tenant->instance_key ?: ('tenant-' . $tenant->id));
        $parts[] = 'TENANT_ID=' . (string) $tenant->id;
        $parts[] = 'MAILBOX_ROOT=' . $mailboxRoot;
        $parts[] = 'MAILBOX_USERS_FILE=' . $usersFile;
        $parts[] = 'MAILBOX_OS_USER=' . $osUser;
        $parts[] = 'MAILBOX_OS_GROUP=' . $osGroup;

        foreach ($envVars as $key => $value) {
            $parts[] = $key . '=' . (string) $value;
        }

        $parts[] = $script;
        $parts[] = $action;

        $escaped = implode(' ', array_map('escapeshellarg', $parts));
        $output = [];
        $exitCode = 0;
        exec($escaped . ' 2>&1', $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'exit_code' => $exitCode,
            'output' => implode("\n", $output),
            'meta' => $this->parseMetadata($output),
        ];
    }

    private function parseMetadata(array $lines): array
    {
        $meta = [];
        foreach ($lines as $line) {
            if (!str_contains((string) $line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', (string) $line, 2);
            $key = trim($key);
            if ($key === '') {
                continue;
            }
            $meta[$key] = trim($value);
        }

        return $meta;
    }

    private function generatePassword(): string
    {
        return bin2hex(random_bytes(10));
    }
}
