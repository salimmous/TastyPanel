<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\SearchController as PublicSearchController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\RecipeController as AdminRecipeController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TenantDomainController;
use App\Http\Controllers\Admin\TenantSubscriptionController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\ThemeVersionController;
use App\Http\Controllers\Admin\ThemeMarketplaceController;
use App\Http\Controllers\Admin\SetupController;
use App\Http\Controllers\Admin\PlatformController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\WebhookController;
use App\Http\Controllers\Admin\PluginController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\CacheController;
use App\Http\Controllers\Admin\TenantAnalyticsController;
use App\Http\Controllers\Admin\TenantBackupController;
use App\Http\Controllers\Admin\LogViewerController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\FirewallController;
use App\Http\Controllers\Admin\SecurityIntegrityController;
use App\Http\Controllers\Admin\UptimeController;
use App\Http\Controllers\Admin\SsoController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\TenantFileController;
use App\Http\Controllers\Admin\TenantLogController;
use App\Http\Controllers\Admin\TenantQueueController;
use App\Http\Controllers\Admin\TenantSecurityProfileController;
use App\Http\Controllers\Admin\TenantSecretController;
use App\Http\Controllers\Admin\TenantMailController;
use App\Http\Controllers\Admin\StagingController;
use App\Http\Controllers\Admin\ContentSnapshotController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\BulkOperationController;
use App\Http\Controllers\Admin\PlatformAnalyticsController;
use App\Http\Controllers\Admin\AutomationRulesController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RequestLogController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Middleware\SetTenantFromHost;

// Partner API (API key authenticated)
Route::prefix('partner')->middleware(['api.key'])->group(function () {
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);
    Route::get('recipes', [RecipeController::class, 'index']);
    Route::get('recipes/{slug}', [RecipeController::class, 'show']);
    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('articles/{slug}', [ArticleController::class, 'show']);
});

// Public API routes (tenant-aware by hostname)
Route::middleware([SetTenantFromHost::class, 'tenant.security', 'tenant.throttle', 'tenant.quota'])->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('recipes', RecipeController::class);
    Route::apiResource('articles', ArticleController::class);
    Route::get('search', [PublicSearchController::class, 'index']);

    // Phase 9: User Features (authenticated)
    Route::middleware('auth:sanctum')->group(function () {
        // Collections
        Route::prefix('collections')->group(function () {
            Route::get('/', [CollectionController::class, 'index']);
            Route::post('/', [CollectionController::class, 'store']);
            Route::get('{collection}', [CollectionController::class, 'show']);
            Route::put('{collection}', [CollectionController::class, 'update']);
            Route::delete('{collection}', [CollectionController::class, 'destroy']);
            Route::post('{collection}/recipes', [CollectionController::class, 'addRecipe']);
            Route::delete('{collection}/recipes/{recipe}', [CollectionController::class, 'removeRecipe']);
            Route::post('{collection}/reorder', [CollectionController::class, 'reorder']);
        });

        // Meal Plans
        Route::prefix('meal-plans')->group(function () {
            Route::get('/', [MealPlanController::class, 'index']);
            Route::post('/', [MealPlanController::class, 'store']);
            Route::get('{mealPlan}', [MealPlanController::class, 'show']);
            Route::put('{mealPlan}', [MealPlanController::class, 'update']);
            Route::delete('{mealPlan}', [MealPlanController::class, 'destroy']);
            Route::post('{mealPlan}/items', [MealPlanController::class, 'addItem']);
            Route::put('items/{item}', [MealPlanController::class, 'updateItem']);
            Route::delete('items/{item}', [MealPlanController::class, 'removeItem']);
            Route::post('{mealPlan}/shopping-list', [MealPlanController::class, 'generateShoppingList']);
            Route::get('{mealPlan}/suggestions', [MealPlanController::class, 'suggestions']);
        });

        // Shopping Lists
        Route::prefix('shopping-lists')->group(function () {
            Route::get('/', [ShoppingListController::class, 'index']);
            Route::post('/', [ShoppingListController::class, 'store']);
            Route::get('{shoppingList}', [ShoppingListController::class, 'show']);
            Route::put('{shoppingList}', [ShoppingListController::class, 'update']);
            Route::delete('{shoppingList}', [ShoppingListController::class, 'destroy']);
            Route::post('{shoppingList}/items', [ShoppingListController::class, 'addItem']);
            Route::put('items/{item}', [ShoppingListController::class, 'updateItem']);
            Route::post('items/{item}/toggle', [ShoppingListController::class, 'toggleItem']);
            Route::delete('items/{item}', [ShoppingListController::class, 'removeItem']);
            Route::post('{shoppingList}/bulk-toggle', [ShoppingListController::class, 'bulkToggle']);
            Route::delete('{shoppingList}/checked', [ShoppingListController::class, 'clearChecked']);
        });
    });
});

// Admin Authentication routes
Route::prefix('admin')->middleware('admin.ip')->group(function () {
    Route::get('/branding', [BrandingController::class, 'show']);
    Route::get('/setup', [SetupController::class, 'status']);
    Route::post('/setup', [SetupController::class, 'store']);

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:web');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:web');
    Route::post('/user/password', [AuthController::class, 'updatePassword'])->middleware('auth:web');
    Route::post('/2fa/request', [AuthController::class, 'requestTwoFactor'])->middleware('auth:web');
    Route::post('/2fa/verify', [AuthController::class, 'verifyTwoFactor'])->middleware('auth:web');
    Route::get('/sso/status', [SsoController::class, 'status']);

    // Dashboard routes (protected)
    Route::middleware(['auth:web', 'admin.2fa', 'admin.audit'])->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

        Route::apiResource('themes', ThemeController::class)->only(['index', 'store', 'update']);
        Route::post('themes/upload', [ThemeController::class, 'upload']);
        Route::get('themes/{theme}/versions', [ThemeVersionController::class, 'index']);
        Route::post('themes/{theme}/versions', [ThemeVersionController::class, 'store']);
        Route::post('themes/{theme}/versions/{version}/restore', [ThemeVersionController::class, 'restore']);
        Route::apiResource('plugins', PluginController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('marketplace/themes', [ThemeMarketplaceController::class, 'index']);
        Route::post('marketplace/themes/{theme}/install', [ThemeMarketplaceController::class, 'install']);
        Route::get('staging', [StagingController::class, 'show']);
        Route::post('staging/enable', [StagingController::class, 'enable']);
        Route::post('staging/sync', [StagingController::class, 'sync']);
        Route::post('staging/promote', [StagingController::class, 'promote']);
        Route::put('staging', [StagingController::class, 'update']);
        Route::get('preview', [\App\Http\Controllers\Admin\PreviewController::class, 'show']);
        Route::post('preview/enable', [\App\Http\Controllers\Admin\PreviewController::class, 'enable']);
        Route::post('preview/sync', [\App\Http\Controllers\Admin\PreviewController::class, 'sync']);
        Route::post('preview/promote', [\App\Http\Controllers\Admin\PreviewController::class, 'promote']);
        Route::put('preview', [\App\Http\Controllers\Admin\PreviewController::class, 'update']);
        Route::get('staging/snapshots', [ContentSnapshotController::class, 'index']);
        Route::post('staging/snapshots', [ContentSnapshotController::class, 'store']);
        Route::post('staging/snapshots/{snapshot}/restore', [ContentSnapshotController::class, 'restore']);
        Route::delete('staging/snapshots/{snapshot}', [ContentSnapshotController::class, 'destroy']);
        Route::get('files', [TenantFileController::class, 'index']);
        Route::post('files/upload', [TenantFileController::class, 'upload']);
        Route::post('files/folder', [TenantFileController::class, 'createFolder']);
        Route::post('files/rename', [TenantFileController::class, 'rename']);
        Route::delete('files', [TenantFileController::class, 'destroy']);
        Route::get('files/download', [TenantFileController::class, 'download']);
        Route::get('tenants/blueprints', [TenantController::class, 'blueprints']);
        Route::apiResource('tenants', TenantController::class);
        Route::post('tenants/{tenant}/instance', [TenantController::class, 'provisionInstance']);
        Route::get('tenants/{tenant}/provisioning-jobs', [TenantController::class, 'provisioningJobs']);
        Route::post('tenants/{tenant}/provisioning/retry', [TenantController::class, 'retryProvisioning']);
        Route::post('tenants/{tenant}/provisioning/rollback', [TenantController::class, 'rollbackProvisioning']);
        Route::get('tenants/{tenant}/access', [TenantController::class, 'accessInfo']);
        Route::post('tenants/{tenant}/access/provision', [TenantController::class, 'provisionAccess']);
        Route::post('tenants/{tenant}/access/password', [TenantController::class, 'rotateAccessPassword']);
        Route::post('tenants/{tenant}/access/key', [TenantController::class, 'installAccessKey']);
        Route::get('tenants/{tenant}/mail/settings', [TenantMailController::class, 'settings']);
        Route::put('tenants/{tenant}/mail/settings', [TenantMailController::class, 'updateSettings']);
        Route::post('tenants/{tenant}/mail/test', [TenantMailController::class, 'test']);
        Route::get('tenants/{tenant}/mailboxes', [TenantMailController::class, 'mailboxes']);
        Route::post('tenants/{tenant}/mailboxes', [TenantMailController::class, 'createMailbox']);
        Route::post('tenants/{tenant}/mailboxes/{mailbox}/password', [TenantMailController::class, 'resetMailboxPassword']);
        Route::post('tenants/{tenant}/mailboxes/{mailbox}/usage', [TenantMailController::class, 'refreshMailboxUsage']);
        Route::delete('tenants/{tenant}/mailboxes/{mailbox}', [TenantMailController::class, 'deleteMailbox']);
        Route::get('tenants/{tenant}/mail/events', [TenantMailController::class, 'events']);
        Route::get('tenants/{tenant}/security-profile', [TenantSecurityProfileController::class, 'show']);
        Route::put('tenants/{tenant}/security-profile', [TenantSecurityProfileController::class, 'update']);
        Route::get('tenants/{tenant}/secrets', [TenantSecretController::class, 'index']);
        Route::post('tenants/{tenant}/secrets', [TenantSecretController::class, 'store']);
        Route::post('tenants/{tenant}/secrets/sync', [TenantSecretController::class, 'syncToEnv']);
        Route::delete('tenants/{tenant}/secrets/sync', [TenantSecretController::class, 'removeFromEnv']);
        Route::delete('tenants/{tenant}/secrets/{secretKey}', [TenantSecretController::class, 'destroy']);
        Route::get('tenants/{tenant}/orchestration/status', [TenantController::class, 'orchestrationStatus']);
        Route::post('tenants/{tenant}/orchestration', [TenantController::class, 'orchestrate']);
        Route::post('tenants/{tenant}/archive', [TenantController::class, 'archive']);
        Route::post('tenants/{tenant}/unarchive', [TenantController::class, 'unarchive']);
        Route::post('tenants/{tenant}/clone', [TenantController::class, 'clone']);
        Route::post('tenants/{tenant}/plan', [TenantSubscriptionController::class, 'assign']);
        Route::get('tenants/{tenant}/backups', [TenantBackupController::class, 'index']);
        Route::post('tenants/{tenant}/backups', [TenantBackupController::class, 'store']);
        Route::put('tenants/{tenant}/backups/settings', [TenantBackupController::class, 'updateSettings']);
        Route::get('tenants/{tenant}/backups/{backup}/download', [TenantBackupController::class, 'download']);
        Route::post('tenants/{tenant}/backups/{backup}/restore', [TenantBackupController::class, 'restore']);
        Route::get('tenants/{tenant}/queue', [TenantQueueController::class, 'show']);
        Route::post('tenants/{tenant}/queue/restart', [TenantQueueController::class, 'restart']);
        Route::post('tenants/{tenant}/queue/flush-failed', [TenantQueueController::class, 'flushFailed']);
        Route::post('tenants/{tenant}/queue/retry-failed', [TenantQueueController::class, 'retryFailed']);
        Route::get('tenants/{tenant}/logs/meta', [TenantLogController::class, 'meta']);
        Route::get('tenants/{tenant}/logs/tail', [TenantLogController::class, 'tail']);
        Route::post('tenants/{tenant}/domains', [TenantDomainController::class, 'store']);
        Route::post('domains/{domain}/provision', [TenantDomainController::class, 'provision']);
        Route::post('domains/{domain}/ssl', [TenantDomainController::class, 'ssl']);
        Route::post('domains/{domain}/nginx', [TenantDomainController::class, 'nginx']);
        Route::get('domains/{domain}/nginx/config', [TenantDomainController::class, 'config']);
        Route::put('domains/{domain}/nginx/config', [TenantDomainController::class, 'updateConfig']);
        Route::delete('domains/{domain}/nginx/config', [TenantDomainController::class, 'resetConfig']);
        Route::post('domains/{domain}/nginx/test', [TenantDomainController::class, 'testConfig']);
        Route::get('domains/{domain}/nginx/versions', [TenantDomainController::class, 'versions']);
        Route::post('domains/{domain}/nginx/versions/{version}/restore', [TenantDomainController::class, 'restoreVersion']);
        Route::post('domains/{domain}/http3', [TenantDomainController::class, 'toggleHttp3']);
        Route::post('domains/{domain}/http3/check', [TenantDomainController::class, 'checkHttp3']);
        Route::delete('domains/{domain}', [TenantDomainController::class, 'destroy']);

        Route::get('automation/settings', [\App\Http\Controllers\Admin\AutomationController::class, 'show']);
        Route::put('automation/settings', [\App\Http\Controllers\Admin\AutomationController::class, 'update']);
        Route::post('automation/test', [\App\Http\Controllers\Admin\AutomationController::class, 'test']);
        Route::post('automation/draft', [\App\Http\Controllers\Admin\AutomationController::class, 'createDraft']);
        Route::get('automation/canva/connect', [\App\Http\Controllers\Admin\AutomationController::class, 'canvaConnect']);
        Route::get('automation/runs', [\App\Http\Controllers\Admin\AutomationController::class, 'runs']);
        Route::post('automation/run', [\App\Http\Controllers\Admin\AutomationController::class, 'run']);

        Route::get('platform/overview', [PlatformController::class, 'overview']);
        Route::get('platform/settings', [PlatformController::class, 'settings']);
        Route::put('platform/settings', [PlatformController::class, 'updateSettings']);
        Route::get('platform/queue', [PlatformController::class, 'queue']);
        Route::post('platform/queue/restart', [PlatformController::class, 'queueRestart']);
        Route::post('platform/queue/flush-failed', [PlatformController::class, 'queueFlushFailed']);
        Route::get('platform/services', [PlatformController::class, 'services']);
        Route::post('platform/services/{service}/action', [PlatformController::class, 'serviceAction']);
        Route::get('platform/services/{service}/logs', [PlatformController::class, 'serviceLogs']);
        Route::post('platform/nginx/deploy-safe', [PlatformController::class, 'deployNginxSafe']);
        Route::get('platform/backups', [PlatformController::class, 'backups']);
        Route::post('platform/backups', [PlatformController::class, 'createBackup']);
        Route::get('platform/backups/{backup}/download', [PlatformController::class, 'downloadBackup']);
        Route::post('platform/backups/{backup}/restore', [PlatformController::class, 'restoreBackup']);
        Route::get('platform/drills', [PlatformController::class, 'drills']);
        Route::post('platform/drills/run', [PlatformController::class, 'runDrill']);
        Route::get('platform/audit-logs', [PlatformController::class, 'auditLogs']);
        Route::get('platform/audit-exports', [PlatformController::class, 'auditExports']);
        Route::post('platform/audit-exports', [PlatformController::class, 'createAuditExport']);
        Route::get('platform/audit-exports/{export}/download', [PlatformController::class, 'downloadAuditExport']);
        Route::get('platform/alerts', [PlatformController::class, 'alerts']);
        Route::get('search/status', [SearchController::class, 'status']);
        Route::post('search/test', [SearchController::class, 'test']);
        Route::post('search/reindex', [SearchController::class, 'reindex']);
        Route::get('logs/meta', [LogViewerController::class, 'meta']);
        Route::get('logs/tail', [LogViewerController::class, 'tail']);
        Route::get('security/scans', [SecurityController::class, 'scans']);
        Route::post('security/scans', [SecurityController::class, 'runScan']);
        Route::get('security/baselines', [SecurityIntegrityController::class, 'baselines']);
        Route::post('security/baselines', [SecurityIntegrityController::class, 'createBaseline']);
        Route::post('security/baselines/{baseline}/check', [SecurityIntegrityController::class, 'runCheck']);
        Route::get('security/baselines/{baseline}/checks', [SecurityIntegrityController::class, 'checks']);
        Route::get('firewall', [FirewallController::class, 'index']);
        Route::post('firewall', [FirewallController::class, 'store']);
        Route::put('firewall/{rule}', [FirewallController::class, 'update']);
        Route::delete('firewall/{rule}', [FirewallController::class, 'destroy']);
        Route::post('firewall/apply', [FirewallController::class, 'apply']);
        Route::get('firewall/status', [FirewallController::class, 'status']);

        Route::apiResource('plans', PlanController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('tenants/{tenant}/analytics', [TenantAnalyticsController::class, 'show']);
        Route::get('tenants/{tenant}/analytics/realtime', [TenantAnalyticsController::class, 'realtime']);
        Route::post('tenants/{tenant}/cache/purge', [CacheController::class, 'purgeTenant']);
        Route::get('tenants/{tenant}/activity', [ActivityLogController::class, 'index']);
        Route::get('tenants/{tenant}/api-keys', [ApiKeyController::class, 'index']);
        Route::post('tenants/{tenant}/api-keys', [ApiKeyController::class, 'store']);
        Route::post('tenants/{tenant}/api-keys/{apiKey}/revoke', [ApiKeyController::class, 'revoke']);
        Route::post('tenants/{tenant}/api-keys/{apiKey}/rotate', [ApiKeyController::class, 'rotate']);
        Route::get('tenants/{tenant}/webhooks', [WebhookController::class, 'index']);
        Route::post('tenants/{tenant}/webhooks', [WebhookController::class, 'store']);
        Route::put('tenants/{tenant}/webhooks/{webhook}', [WebhookController::class, 'update']);
        Route::delete('tenants/{tenant}/webhooks/{webhook}', [WebhookController::class, 'destroy']);
        Route::post('tenants/{tenant}/webhooks/{webhook}/test', [WebhookController::class, 'test']);
        Route::get('tenants/{tenant}/webhooks/{webhook}/deliveries', [WebhookController::class, 'deliveries']);
        Route::get('tenants/{tenant}/uptime-checks', [UptimeController::class, 'index']);
        Route::post('tenants/{tenant}/uptime-checks', [UptimeController::class, 'store']);
        Route::put('tenants/{tenant}/uptime-checks/{check}', [UptimeController::class, 'update']);
        Route::delete('tenants/{tenant}/uptime-checks/{check}', [UptimeController::class, 'destroy']);
        Route::get('tenants/{tenant}/uptime-checks/{check}/events', [UptimeController::class, 'events']);
        Route::post('tenants/{tenant}/uptime-checks/{check}/run', [UptimeController::class, 'run']);
        Route::get('feature-flags', [FeatureFlagController::class, 'index']);
        Route::post('feature-flags', [FeatureFlagController::class, 'store']);
        Route::put('feature-flags/{featureFlag}', [FeatureFlagController::class, 'update']);
        Route::delete('feature-flags/{featureFlag}', [FeatureFlagController::class, 'destroy']);

        // Admin CRUD routes (with different names to avoid conflicts)
        Route::apiResource('categories', AdminCategoryController::class)->names([
            'index' => 'admin.categories.index',
            'store' => 'admin.categories.store',
            'show' => 'admin.categories.show',
            'update' => 'admin.categories.update',
            'destroy' => 'admin.categories.destroy',
        ]);
        Route::apiResource('recipes', AdminRecipeController::class)->names([
            'index' => 'admin.recipes.index',
            'store' => 'admin.recipes.store',
            'show' => 'admin.recipes.show',
            'update' => 'admin.recipes.update',
            'destroy' => 'admin.recipes.destroy',
        ]);
        Route::apiResource('articles', AdminArticleController::class)->names([
            'index' => 'admin.articles.index',
            'store' => 'admin.articles.store',
            'show' => 'admin.articles.show',
            'update' => 'admin.articles.update',
            'destroy' => 'admin.articles.destroy',
        ]);

        // Phase 6: Admin Notifications
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
        Route::get('notification-settings', [NotificationController::class, 'getSettings']);
        Route::put('notification-settings', [NotificationController::class, 'updateSettings']);

        // Phase 6: Scheduling
        Route::get('scheduled-publications', [ScheduleController::class, 'index']);
        Route::post('scheduled-publications', [ScheduleController::class, 'store']);
        Route::put('scheduled-publications/{schedule}', [ScheduleController::class, 'update']);
        Route::delete('scheduled-publications/{schedule}', [ScheduleController::class, 'destroy']);
        Route::get('scheduled-publications/calendar', [ScheduleController::class, 'calendar']);

        // Phase 6: Bulk Operations
        Route::post('bulk/recipes/update', [BulkOperationController::class, 'update']);
        Route::post('bulk/recipes/delete', [BulkOperationController::class, 'delete']);
        Route::post('bulk/recipes/publish', [BulkOperationController::class, 'publish']);
        Route::post('bulk/recipes/draft', [BulkOperationController::class, 'draft']);
        Route::post('bulk/recipes/category', [BulkOperationController::class, 'changeCategory']);
        Route::post('bulk/recipes/export', [BulkOperationController::class, 'export']);

        // Phase 7: Platform Analytics Dashboard
        Route::prefix('platform-analytics')->group(function () {
            Route::get('dashboard', [PlatformAnalyticsController::class, 'dashboard']);
            Route::get('leaderboard', [PlatformAnalyticsController::class, 'leaderboard']);
            Route::get('content', [PlatformAnalyticsController::class, 'content']);
            Route::get('traffic', [PlatformAnalyticsController::class, 'traffic']);
            Route::get('history', [PlatformAnalyticsController::class, 'history']);
            Route::post('collect', [PlatformAnalyticsController::class, 'collect']);
        });

        // Phase 7: Automation Rules
        Route::prefix('automation-rules')->group(function () {
            Route::get('/', [AutomationRulesController::class, 'index']);
            Route::get('actions', [AutomationRulesController::class, 'availableActions']);
            Route::post('/', [AutomationRulesController::class, 'store']);
            Route::get('{rule}', [AutomationRulesController::class, 'show']);
            Route::put('{rule}', [AutomationRulesController::class, 'update']);
            Route::delete('{rule}', [AutomationRulesController::class, 'destroy']);
            Route::post('{rule}/toggle', [AutomationRulesController::class, 'toggle']);
            Route::post('{rule}/run', [AutomationRulesController::class, 'run']);
            Route::get('{rule}/logs', [AutomationRulesController::class, 'logs']);
            Route::post('run-scheduled', [AutomationRulesController::class, 'runScheduled']);
        });

        // Phase 8: Revenue Tracking
        Route::prefix('revenue')->group(function () {
            Route::get('dashboard', [RevenueController::class, 'dashboard']);
            Route::get('mrr', [RevenueController::class, 'mrrChart']);
            Route::get('by-plan', [RevenueController::class, 'byPlan']);
            Route::get('subscriptions', [RevenueController::class, 'subscriptions']);
            Route::post('subscriptions', [RevenueController::class, 'createSubscription']);
            Route::put('subscriptions/{subscription}', [RevenueController::class, 'updateSubscription']);
            Route::post('subscriptions/{subscription}/cancel', [RevenueController::class, 'cancelSubscription']);
            Route::get('invoices', [RevenueController::class, 'invoices']);
            Route::post('invoices/{invoice}/paid', [RevenueController::class, 'markInvoicePaid']);
        });

        // Phase 8: Custom Roles (RBAC)
        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::get('permissions', [RoleController::class, 'permissions']);
            Route::post('seed', [RoleController::class, 'seed']);
            Route::post('/', [RoleController::class, 'store']);
            Route::get('{role}', [RoleController::class, 'show']);
            Route::put('{role}', [RoleController::class, 'update']);
            Route::delete('{role}', [RoleController::class, 'destroy']);
        });
        Route::get('users/{user}/roles', [RoleController::class, 'userRoles']);
        Route::post('users/{user}/roles', [RoleController::class, 'assignToUser']);

        // Phase 8: Advanced Logging
        Route::prefix('logs')->group(function () {
            Route::get('requests', [RequestLogController::class, 'index']);
            Route::get('requests/{requestLog}', [RequestLogController::class, 'show']);
            Route::get('performance', [RequestLogController::class, 'performance']);
            Route::get('errors', [RequestLogController::class, 'errors']);
            Route::get('daily', [RequestLogController::class, 'daily']);
            Route::delete('cleanup', [RequestLogController::class, 'cleanup']);
        });

        // Phase 8: Tenant Cloning (added to existing tenant routes)
        Route::get('tenants/{tenant}/clone/preview', [TenantController::class, 'clonePreview']);
        Route::post('tenants/{tenant}/clone', [TenantController::class, 'clone']);

        Route::apiResource('users', UserController::class)->names([
            'index' => 'admin.users.index',
            'store' => 'admin.users.store',
            'show' => 'admin.users.show',
            'update' => 'admin.users.update',
            'destroy' => 'admin.users.destroy',
        ]);
    });
});

// Social Features (Auth required)
Route::middleware('auth:sanctum')->group(function () {
    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/recipes/{recipe}/favorite', [FavoriteController::class, 'toggle']);
    Route::get('/recipes/{recipe}/favorite/check', [FavoriteController::class, 'check']);

    // Ratings
    Route::get('/recipes/{recipe}/ratings', [RatingController::class, 'index']);
    Route::post('/recipes/{recipe}/ratings', [RatingController::class, 'store']);
    Route::get('/recipes/{recipe}/ratings/me', [RatingController::class, 'show']);
    Route::delete('/recipes/{recipe}/ratings', [RatingController::class, 'destroy']);

    // Comments
    Route::get('/recipes/{recipe}/comments', [CommentController::class, 'index']);
    Route::post('/recipes/{recipe}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // Sharing
    Route::post('/recipes/{recipe}/share', [ShareController::class, 'store']);
    Route::get('/recipes/{recipe}/share/stats', [ShareController::class, 'stats']);
    Route::get('/recipes/{recipe}/share/urls', [ShareController::class, 'urls']);
});

// User route (protected)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
