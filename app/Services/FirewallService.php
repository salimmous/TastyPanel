<?php

namespace App\Services;

use App\Models\FirewallRule;

class FirewallService
{
    public function applyAll(): array
    {
        $rules = FirewallRule::where('is_active', true)->orderBy('id')->get();
        $results = [];

        foreach ($rules as $rule) {
            $results[] = $this->applyRule($rule);
        }

        return $results;
    }

    public function status(): string
    {
        $commandParts = [];
        if (config('services.firewall.use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = 'ufw';
        $commandParts[] = 'status';

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));
        $output = [];
        exec($escaped.' 2>&1', $output);

        return implode("\n", $output);
    }

    public function applyRule(FirewallRule $rule): array
    {
        $this->validateRule($rule);

        $script = config('services.firewall.script', base_path('infrastructure/firewall-apply.sh'));
        if (! $script || ! file_exists($script)) {
            return ['success' => false, 'output' => 'Firewall script not found.'];
        }

        $commandParts = [];
        if (config('services.firewall.use_sudo', true)) {
            $commandParts[] = 'sudo';
            $commandParts[] = '-n';
        }
        $commandParts[] = $script;
        $commandParts[] = $rule->action;
        $commandParts[] = $rule->protocol;
        $commandParts[] = $rule->port;
        $commandParts[] = $rule->source ?? '';
        $commandParts[] = $rule->description ?? '';

        $escaped = implode(' ', array_map('escapeshellarg', $commandParts));
        $output = [];
        $exitCode = 0;
        exec($escaped.' 2>&1', $output, $exitCode);

        if ($exitCode === 0) {
            $rule->applied_at = now();
            $rule->save();
        }

        return [
            'success' => $exitCode === 0,
            'output' => implode("\n", $output),
        ];
    }

    private function validateRule(FirewallRule $rule): void
    {
        $action = strtolower($rule->action);
        if (! in_array($action, ['allow', 'deny'], true)) {
            throw new \InvalidArgumentException('Invalid firewall action.');
        }

        $protocol = strtolower($rule->protocol);
        if (! in_array($protocol, ['tcp', 'udp'], true)) {
            throw new \InvalidArgumentException('Invalid firewall protocol.');
        }

        if (! preg_match('/^[0-9]{1,5}(:[0-9]{1,5})?$/', $rule->port)) {
            throw new \InvalidArgumentException('Invalid port format.');
        }
    }
}
