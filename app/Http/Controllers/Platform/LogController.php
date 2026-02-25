<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    // Platform Logs
    public function index()
    {
        if (!Auth::check()) return redirect()->route('platform.login');
        return view('platform.logs.index');
    }

    public function fetch(Request $request)
    {
        if (!Auth::check()) return response()->json(['error' => 'Unauthorized'], 401);

        $service = $request->query('service');
        $logFile = '';

        switch ($service) {
            case 'nginx':
                $logFile = '/var/log/nginx/error.log';
                break;
            case 'mysql':
                $logFile = '/var/log/mysql/error.log';
                break;
            case 'redis':
                $logFile = '/var/log/redis/redis-server.log';
                break;
            case 'php8.3-fpm':
                $logFile = '/var/log/php8.3-fpm.log';
                break;
            case 'laravel':
                $logFile = storage_path('logs/laravel.log');
                break;
            default:
                return response()->json(['error' => 'Unknown service'], 400);
        }

        if (!file_exists($logFile) || !is_readable($logFile)) {
            // Try journalctl if file not accessible (except laravel log)
            if ($service !== 'laravel') {
                $cmd = sprintf('journalctl -u %s -n 200 --no-pager', escapeshellarg($service));
                $output = [];
                @exec($cmd, $output);
                return response()->json(['logs' => implode("\n", $output)]);
            }
            return response()->json(['logs' => 'Log file not found or not readable.']);
        }

        // Tail last 200 lines
        $content = shell_exec("tail -n 200 " . escapeshellarg($logFile));
        return response()->json(['logs' => $content]);
    }

    // Site Logs
    public function siteLogs($id, $type)
    {
        if (!Auth::check()) return response()->json(['error' => 'Unauthorized'], 401);

        $tenant = Tenant::findOrFail($id);
        $tenantKey = $tenant->instance_key ?: $tenant->slug;
        $logPath = '';

        switch ($type) {
            case 'access':
                $logPath = "/var/www/tastypanel-sites/{$tenantKey}/logs/access.log";
                break;
            case 'error':
                $logPath = "/var/www/tastypanel-sites/{$tenantKey}/logs/error.log";
                break;
            case 'php':
                $logPath = "/var/www/tastypanel-sites/{$tenantKey}/logs/php-error.log";
                break;
            default:
                return response()->json(['error' => 'Unknown log type'], 400);
        }

        if (!File::exists($logPath)) {
            return response()->json(['logs' => 'Log file not found.']);
        }

        $content = shell_exec("tail -n 200 " . escapeshellarg($logPath));
        return response()->json(['logs' => $content]);
    }
}
