<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Database;
use App\Models\User;
use App\Models\Domain;
use App\Models\BackupRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class DashboardController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        // System metrics
        $load = sys_getloadavg();
        $systemMetrics = [
            'load' => [
                '1m' => round($load[0] ?? 0, 2),
                '5m' => round($load[1] ?? 0, 2),
                '15m' => round($load[2] ?? 0, 2),
            ],
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
        ];

        // Service statuses
        $services = [
            'nginx' => $this->checkServiceStatus('nginx'),
            'mysql' => 'running',
            'redis' => 'running',
            'php' => 'running',
        ];

        try {
            DB::connection()->getPdo();
            $services['mysql'] = 'running';
        } catch (\Throwable $e) {
            $services['mysql'] = 'stopped';
        }

        try {
            Redis::connection()->ping();
            $services['redis'] = 'running';
        } catch (\Throwable $e) {
            $services['redis'] = 'stopped';
        }

        // Quick stats
        $stats = [
            'sites' => Tenant::count(), // Prompt calls them "Sites" now
            'databases' => Database::count(),
            'users' => User::count(), // Platform users
            // 'system_users' => SystemUser::count(), // if needed
        ];

        // Queue stats
        $queueSize = 0;
        $failedJobs = 0;
        try {
            $queueSize = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
        }

        // Recent activity
        $recentSites = Tenant::with('domains')->latest()->take(5)->get();
        // $recentActivity = ActivityLog::latest()->take(10)->get();

        return view('platform.dashboard', compact(
            'systemMetrics',
            'services',
            'stats',
            'queueSize',
            'failedJobs',
            'recentSites'
        ));
    }

    private function getMemoryUsage(): array
    {
        $meminfo = $this->readMeminfo();
        if ($meminfo) {
            $total = $meminfo['MemTotal'] ?? 0;
            $available = $meminfo['MemAvailable'] ?? 0;
            $used = max($total - $available, 0);
            return [
                'total_mb' => round($total / 1024, 2),
                'used_mb' => round($used / 1024, 2),
                'free_mb' => round($available / 1024, 2),
                'percent' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
            ];
        }

        $usage = memory_get_usage(true);
        return [
            'total_mb' => null,
            'used_mb' => round($usage / 1024 / 1024, 2),
            'free_mb' => null,
            'percent' => null,
        ];
    }

    private function readMeminfo(): ?array
    {
        $path = '/proc/meminfo';
        if (!is_readable($path)) {
            return null;
        }

        $data = [];
        foreach (file($path) as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $matches)) {
                $data[$matches[1]] = (int) $matches[2];
            }
        }

        return $data ?: null;
    }

    private function getDiskUsage(): array
    {
        $root = '/';
        $total = @disk_total_space($root);
        $free = @disk_free_space($root);
        if ($total === false || $free === false) {
            return [
                'total_gb' => null,
                'used_gb' => null,
                'free_gb' => null,
                'percent' => null,
            ];
        }

        $used = max($total - $free, 0);
        return [
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'used_gb' => round($used / 1024 / 1024 / 1024, 2),
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'percent' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
        ];
    }

    private function checkServiceStatus($service): string
    {
        $output = [];
        $exit = 0;
        @exec(sprintf('systemctl is-active %s 2>&1', escapeshellarg($service)), $output, $exit);
        if ($exit === 0) {
            return 'running';
        }
        return 'stopped';
    }
}
