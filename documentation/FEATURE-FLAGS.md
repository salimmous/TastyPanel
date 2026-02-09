# Feature Flags

API (admin, superadmin only):
- `GET /api/admin/feature-flags`
- `POST /api/admin/feature-flags` (key, enabled, rollout_percentage 0-100, tenant_id optional, environment optional)
- `PUT /api/admin/feature-flags/{id}`
- `DELETE /api/admin/feature-flags/{id}`

Storage:
- Table `feature_flags` (unique per key + tenant + environment)
- Service check: `app/Services/FeatureFlagService.php`

CLI helpers:
- `php artisan feature:rollout <key> --enable|--disable --percent=50 [--tenant=ID]`
- `php artisan tenant:prerender <tenantId> --limit=10` (warms cache for popular pages)

Frontend:
- Admin page `/admin/feature-flags` (superadmin) for CRUD.
