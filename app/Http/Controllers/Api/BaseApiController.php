<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;

class BaseApiController extends Controller
{
    /**
     * Get the current tenant ID.
     */
    protected function getTenantId(): ?int
    {
        return TenantContext::id();
    }

    /**
     * Get the current environment.
     */
    protected function getEnvironment(): string
    {
        return TenantContext::environment();
    }

    /**
     * Apply tenant and environment scopes to the query.
     */
    protected function scopeWithTenant(Builder $query): Builder
    {
        $tenantId = $this->getTenantId();
        $environment = $this->getEnvironment();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        $query->where('environment', $environment);

        return $query;
    }

    /**
     * Check if a slug is unique for the current tenant and environment.
     */
    protected function isSlugUnique(string $modelClass, string $slug, $ignoreId = null): bool
    {
        $tenantId = $this->getTenantId();

        if (! $tenantId) {
            return true;
        }

        $environment = $this->getEnvironment();

        $query = $modelClass::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return ! $query->exists();
    }

    /**
     * Append tenant and environment to data array.
     */
    protected function applyTenantData(array $data): array
    {
        $tenantId = $this->getTenantId();
        if ($tenantId) {
            $data['tenant_id'] = $tenantId;
        }
        $data['environment'] = $this->getEnvironment();

        return $data;
    }

    /**
     * Success response
     */
    protected function success($data, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Error response
     */
    protected function error(string $message, $errors = null, int $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
