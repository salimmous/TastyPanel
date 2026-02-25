<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TenantFileService;
use App\Support\AdminPermissions;
use App\Support\AdminTenantResolver;
use Illuminate\Http\Request;
use RuntimeException;

class TenantFileController extends Controller
{
    public function __construct(private TenantFileService $files) {}

    public function index(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $path = $request->query('path', '');

        try {
            return response()->json([
                'data' => $this->files->list($tenantId, $path),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function upload(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $data = $request->validate([
            'path' => ['nullable', 'string'],
            'files' => ['required'],
            'files.*' => ['file', 'max:51200'],
        ]);

        try {
            $uploads = $this->files->upload($tenantId, $data['path'] ?? '', $request->file('files', []));

            return response()->json([
                'data' => $uploads,
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function createFolder(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $data = $request->validate([
            'path' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        try {
            $path = $this->files->createFolder($tenantId, $data['path'] ?? '', $data['name']);

            return response()->json([
                'data' => ['path' => $path],
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function rename(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $data = $request->validate([
            'path' => ['required', 'string'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        try {
            $path = $this->files->rename($tenantId, $data['path'], $data['name']);

            return response()->json([
                'data' => ['path' => $path],
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $data = $request->validate([
            'path' => ['required', 'string'],
        ]);

        try {
            $this->files->delete($tenantId, $data['path']);

            return response()->json([
                'message' => 'Deleted',
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function download(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $data = $request->validate([
            'path' => ['required', 'string'],
        ]);

        try {
            $file = $this->files->download($tenantId, $data['path']);

            return response()->download($file['absolute'], $file['name']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function resolveTenantId(Request $request): int
    {
        $user = $request->user();
        if (! AdminPermissions::canManageTenantInfrastructure($user)) {
            abort(403);
        }

        $tenantId = AdminTenantResolver::resolveId($request);
        if (! $tenantId) {
            abort(422, 'Tenant required.');
        }

        return $tenantId;
    }
}
