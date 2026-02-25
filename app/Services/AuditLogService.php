<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * Log an action
     */
    public function log(
        string $action,
        ?Model $resource = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'tenant_id' => $this->getTenantId(),
            'action' => $action,
            'resource_type' => $resource ? get_class($resource) : null,
            'resource_id' => $resource?->id,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'method' => request()->method(),
            'url' => request()->fullUrl(),
            'status' => 'success',
            'created_at' => now(),
        ]);
    }

    /**
     * Log authentication attempt
     */
    public function logAuth(string $action, ?User $user = null, bool $success = true, ?string $reason = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $user?->id,
            'tenant_id' => $user?->tenant_id,
            'action' => $action,
            'description' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'method' => request()->method(),
            'url' => request()->fullUrl(),
            'status' => $success ? 'success' : 'failed',
            'error_message' => $success ? null : $reason,
            'created_at' => now(),
        ]);
    }

    /**
     * Log model creation
     */
    public function logCreated(Model $model): AuditLog
    {
        return $this->log(
            'create',
            $model,
            null,
            $model->getAttributes(),
            "Created {$this->getResourceName($model)}"
        );
    }

    /**
     * Log model update
     */
    public function logUpdated(Model $model): AuditLog
    {
        return $this->log(
            'update',
            $model,
            $model->getOriginal(),
            $model->getChanges(),
            "Updated {$this->getResourceName($model)}"
        );
    }

    /**
     * Log model deletion
     */
    public function logDeleted(Model $model): AuditLog
    {
        return $this->log(
            'delete',
            $model,
            $model->getAttributes(),
            null,
            "Deleted {$this->getResourceName($model)}"
        );
    }

    /**
     * Get recent activity for user
     */
    public function getUserActivity(User $user, int $limit = 20): \Illuminate\Support\Collection
    {
        return AuditLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent activity for tenant
     */
    public function getTenantActivity(Tenant $tenant, int $limit = 50): \Illuminate\Support\Collection
    {
        return AuditLog::where('tenant_id', $tenant->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed login attempts
     */
    public function getFailedLogins(int $hours = 24, ?string $ip = null): \Illuminate\Support\Collection
    {
        $query = AuditLog::where('action', 'login')
            ->where('status', 'failed')
            ->where('created_at', '>', now()->subHours($hours));

        if ($ip) {
            $query->where('ip_address', $ip);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Search audit logs
     */
    public function search(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = AuditLog::query()->with('user');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['ip'])) {
            $query->where('ip_address', $filters['ip']);
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * Get current tenant ID
     */
    protected function getTenantId(): ?int
    {
        // Try from authenticated user
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }

        // Try from TenantContext
        if (class_exists('\App\Support\TenantContext')) {
            return \App\Support\TenantContext::id();
        }

        return null;
    }

    /**
     * Get human-readable resource name
     */
    protected function getResourceName(Model $model): string
    {
        $class = class_basename($model);

        if (method_exists($model, 'getAuditName')) {
            return $model->getAuditName();
        }

        if (isset($model->title)) {
            return "{$class}: {$model->title}";
        }

        if (isset($model->name)) {
            return "{$class}: {$model->name}";
        }

        return "{$class} #{$model->id}";
    }
}
