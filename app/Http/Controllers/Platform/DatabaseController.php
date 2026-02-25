<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Database;
use App\Models\Tenant;
use App\Services\DatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DatabaseController extends Controller
{
    public function index()
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        $databases = Database::with('tenant')->paginate(20);
        return view('platform.databases.index', compact('databases'));
    }

    public function create()
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        $sites = Tenant::all();
        return view('platform.databases.create', compact('sites'));
    }

    public function store(Request $request, DatabaseService $service)
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:databases,name',
            'username' => 'required|string|max:100',
            'password' => 'nullable|string|min:8',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        try {
            $service->create($validated);
            return redirect()->route('platform.databases.index')->with('success', 'Database created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }
    }

    public function destroy(Database $database, DatabaseService $service)
    {
        if (!Auth::check()) return redirect()->route('platform.login');

        try {
            $service->delete($database);
            return back()->with('success', 'Database deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function backup(Database $database)
    {
        // Implement backup logic or delegate to service
        // Prompt says: mysqldump {dbname} > ...
        // I'll defer this to Phase 2/3 polish, or implement basic now.
        // For now, redirect with info.
        return back()->with('info', 'Backup feature coming in next phase.');
    }
}
