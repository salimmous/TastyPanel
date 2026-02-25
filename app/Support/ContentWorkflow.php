<?php

namespace App\Support;

use App\Models\User;

class ContentWorkflow
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_REVIEW = 'review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PUBLISHED = 'published';

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_REVIEW,
            self::STATUS_APPROVED,
            self::STATUS_PUBLISHED,
        ];
    }

    public static function allowedStatuses(?User $user): array
    {
        $role = AdminPermissions::role($user);

        return match ($role) {
            AdminPermissions::ROLE_WRITER => [self::STATUS_DRAFT],
            AdminPermissions::ROLE_EDITOR => [self::STATUS_DRAFT, self::STATUS_REVIEW],
            AdminPermissions::ROLE_TENANT_ADMIN, AdminPermissions::ROLE_SUPERADMIN => self::statuses(),
            default => [self::STATUS_DRAFT],
        };
    }

    public static function normalizeStatus(?string $requested, ?User $user): string
    {
        $allowed = self::allowedStatuses($user);
        $requested = $requested ? strtolower(trim($requested)) : null;

        if ($requested && in_array($requested, $allowed, true)) {
            return $requested;
        }

        return $allowed[0] ?? self::STATUS_DRAFT;
    }

    public static function applyStatusTimestamps($model, string $status): void
    {
        $status = strtolower($status);
        $now = now();

        if ($status === self::STATUS_PUBLISHED) {
            $model->published_at = $now;
            $model->approved_at = $model->approved_at ?? $now;
            $model->reviewed_at = $model->reviewed_at ?? $now;

            return;
        }

        if ($status === self::STATUS_APPROVED) {
            $model->approved_at = $now;
            $model->reviewed_at = $model->reviewed_at ?? $now;
            $model->published_at = null;

            return;
        }

        if ($status === self::STATUS_REVIEW) {
            $model->reviewed_at = $now;
            $model->approved_at = null;
            $model->published_at = null;

            return;
        }

        $model->reviewed_at = null;
        $model->approved_at = null;
        $model->published_at = null;
    }
}
