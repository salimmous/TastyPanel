<?php

namespace App\Services;

use App\Models\AuditExport;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;

class AuditExportService
{
    public function export(?int $days = null, ?int $userId = null): AuditExport
    {
        $dir = storage_path('app/audit-exports');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $timestamp = now()->format('Ymd_His');
        $relativePath = 'audit-exports/audit_' . $timestamp . '.csv';
        $fullPath = storage_path('app/' . $relativePath);

        $handle = fopen($fullPath, 'w');
        if (!$handle) {
            throw new \RuntimeException('Unable to create audit export file.');
        }

        fputcsv($handle, [
            'id',
            'created_at',
            'user_email',
            'tenant_id',
            'action',
            'route',
            'method',
            'ip_address',
            'user_agent',
        ]);

        $query = AuditLog::with(['user:id,email']);
        if ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $total = 0;
        $query->orderBy('id')->chunkById(500, function ($logs) use ($handle, &$total) {
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at,
                    $log->user?->email,
                    $log->tenant_id,
                    $log->action,
                    $log->route,
                    $log->method,
                    $log->ip_address,
                    $log->user_agent,
                ]);
                $total++;
            }
        });

        fclose($handle);

        return AuditExport::create([
            'file_path' => $relativePath,
            'total_rows' => $total,
            'created_by' => $userId,
        ]);
    }

    public function cleanup(?int $days = null): int
    {
        if (!$days || $days < 1) {
            return 0;
        }

        $cutoff = now()->subDays($days);
        $exports = AuditExport::where('created_at', '<', $cutoff)->get();
        $deleted = 0;

        foreach ($exports as $export) {
            if ($export->file_path && Storage::disk('local')->exists($export->file_path)) {
                Storage::disk('local')->delete($export->file_path);
            }
            $export->delete();
            $deleted++;
        }

        return $deleted;
    }
}
