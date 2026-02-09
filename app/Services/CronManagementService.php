<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Process;

class CronManagementService
{
    public function getJobs(Tenant $tenant): array
    {
        $user = $tenant->instance_system_user;
        if (!$user) return [];

        $result = Process::run("sudo crontab -u $user -l");
        if ($result->failed()) return [];

        $lines = explode("\n", trim($result->output()));
        return array_filter($lines, fn($line) => !empty($line) && strpos($line, '#') !== 0);
    }

    public function addJob(Tenant $tenant, string $command): bool
    {
        $user = $tenant->instance_system_user;
        if (!$user) return false;

        $jobs = $this->getJobs($tenant);
        $jobs[] = $command;
        $this->saveJobs($tenant, $jobs);
        return true;
    }

    public function removeJob(Tenant $tenant, int $index): bool
    {
        $jobs = $this->getJobs($tenant);
        if (!isset($jobs[$index])) return false;

        unset($jobs[$index]);
        $this->saveJobs($tenant, array_values($jobs));
        return true;
    }

    private function saveJobs(Tenant $tenant, array $jobs): void
    {
        $user = $tenant->instance_system_user;
        $content = implode("\n", $jobs) . "\n";
        
        $tempFile = tempnam(sys_get_temp_dir(), 'cron');
        file_put_contents($tempFile, $content);
        
        Process::run("sudo crontab -u $user \"$tempFile\"");
        unlink($tempFile);
    }
}
