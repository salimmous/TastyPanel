<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class PhpController extends Controller
{
    public function index()
    {
        if (!Auth::check()) return redirect()->route('platform.login');
        return view('platform.php.index');
    }

    public function update(Request $request, $id)
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'memory_limit' => 'required|integer|min:64|max:4096',
            'max_children' => 'required|integer|min:1|max:100',
            'max_requests' => 'required|integer|min:1|max:10000',
        ]);

        $fpmPoolPath = "/etc/php/8.3/fpm/pool.d/{$tenant->instance_key}.conf";
        if (!File::exists($fpmPoolPath)) {
            return redirect()->back()->with('error', 'PHP-FPM pool configuration not found.');
        }

        $content = File::get($fpmPoolPath);
        $content = preg_replace('/pm.max_children = .*/', "pm.max_children = {$validated['max_children']}", $content);
        $content = preg_replace('/pm.max_requests = .*/', "pm.max_requests = {$validated['max_requests']}", $content);
        $content = preg_replace('/php_admin_value\[memory_limit\] = .*/', "php_admin_value[memory_limit] = {$validated['memory_limit']}M", $content);

        // Write back using sudo
        $tempFile = tempnam(sys_get_temp_dir(), 'fpm_pool');
        File::put($tempFile, $content);

        $result = Process::run("sudo cp \"$tempFile\" \"$fpmPoolPath\" && sudo systemctl reload php8.3-fpm");
        unlink($tempFile);

        if ($result->failed()) {
            return redirect()->back()->with('error', 'Failed to update PHP-FPM config: ' . $result->errorOutput());
        }

        return redirect()->back()->with('success', 'PHP settings updated and FPM reloaded.');
    }
}
