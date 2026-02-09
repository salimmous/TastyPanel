<?php

use App\Http\Controllers\Admin\AutomationController;
use App\Http\Controllers\Admin\SamlController;
use App\Http\Controllers\Admin\SsoController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\PlatformInstallController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!PlatformInstallController::isInstalled()) {
        return redirect()->route('platform.install');
    }

    if (Auth::check()) {
        return redirect()->route('platform.dashboard');
    }

    return redirect()->route('platform.login');
})->name('home');

// Keep the default "login" route name for auth redirects.
Route::get('/login', function () {
    if (!PlatformInstallController::isInstalled()) {
        return redirect()->route('platform.install');
    }

    return redirect()->route('platform.login');
})->name('login');

Route::prefix('platform')->group(function () {
    Route::get('/install', [PlatformInstallController::class, 'show'])->name('platform.install');
    Route::post('/install/complete', [PlatformInstallController::class, 'complete'])->name('platform.install.complete');

    Route::get('/login', [PlatformController::class, 'showLogin'])->name('platform.login');
    Route::post('/login', [PlatformController::class, 'login']);
    Route::post('/logout', [PlatformController::class, 'logout'])->name('platform.logout');

    Route::middleware(['web'])->group(function () {
        Route::get('/', [PlatformController::class, 'dashboard'])->name('platform.dashboard');
        Route::get('/dashboard', [PlatformController::class, 'dashboard']);
        Route::get('/overview', [PlatformController::class, 'overview'])->name('platform.overview');
        Route::get('/tenants', [PlatformController::class, 'tenants'])->name('platform.tenants');
        Route::get('/tenants/create', [PlatformController::class, 'createTenant'])->name('platform.tenants.create');
        Route::post('/tenants', [PlatformController::class, 'storeTenant'])->name('platform.tenants.store');
        Route::get('/tenants/{id}', [PlatformController::class, 'showTenant'])->name('platform.tenants.show');
        Route::post('/tenants/{id}/vhost', [PlatformController::class, 'updateVhost'])->name('platform.tenants.vhost.update');
        Route::post('/tenants/{id}/ssl', [PlatformController::class, 'provisionSsl'])->name('platform.tenants.ssl.provision');
        Route::post('/tenants/{id}/php', [PlatformController::class, 'updatePhpSettings'])->name('platform.tenants.php.update');
        Route::post('/tenants/{id}/cron', [PlatformController::class, 'storeCronJob'])->name('platform.tenants.cron.store');
        Route::delete('/tenants/{id}/cron/{index}', [PlatformController::class, 'destroyCronJob'])->name('platform.tenants.cron.destroy');
        Route::get('/tenants/{id}/phpmyadmin', [PlatformController::class, 'phpmyadminFrame'])->name('platform.tenants.phpmyadmin');
        Route::post('/tenants/{id}/phpmyadmin/provision', [PlatformController::class, 'provisionPhpMyAdmin'])->name('platform.tenants.phpmyadmin.provision');
        Route::delete('/tenants/{id}', [PlatformController::class, 'destroyTenant'])->name('platform.tenants.destroy');
        Route::get('/tenants/{id}/staging', [\App\Http\Controllers\Admin\StagingController::class, 'index'])->name('platform.tenants.staging');
        Route::post('/tenants/{id}/staging/enable', [\App\Http\Controllers\Admin\StagingController::class, 'enable'])->name('platform.tenants.staging.enable');
        Route::post('/tenants/{id}/staging/sync', [\App\Http\Controllers\Admin\StagingController::class, 'sync'])->name('platform.tenants.staging.sync');
        Route::post('/tenants/{id}/staging/disable', [\App\Http\Controllers\Admin\StagingController::class, 'destroy'])->name('platform.tenants.staging.disable');
        Route::get('/tenants/{id}/automation', [\App\Http\Controllers\Admin\AutomationController::class, 'index'])->name('platform.tenants.automation');
        Route::post('/admin/automation/update', [\App\Http\Controllers\Admin\AutomationController::class, 'update'])->name('platform.automation.update');
        Route::post('/admin/automation/test', [\App\Http\Controllers\Admin\AutomationController::class, 'test'])->name('platform.automation.test');
        Route::post('/tenants/{id}/toggle-status', [PlatformController::class, 'toggleTenantStatus'])->name('platform.tenants.toggle-status');
        Route::post('/tenants/bulk-activate', [PlatformController::class, 'bulkActivate'])->name('platform.tenants.bulk-activate');
        Route::post('/tenants/bulk-deactivate', [PlatformController::class, 'bulkDeactivate'])->name('platform.tenants.bulk-deactivate');
        Route::post('/tenants/bulk-delete', [PlatformController::class, 'bulkDelete'])->name('platform.tenants.bulk-delete');
        Route::post('/tenants/{id}/install-app', [PlatformController::class, 'installApp'])->name('platform.tenants.install-app');
        Route::post('/tenants/{id}/uninstall-app', [PlatformController::class, 'uninstallApp'])->name('platform.tenants.uninstall-app');
        Route::post('/tenants/{id}/secrets', [PlatformController::class, 'storeSecret'])->name('platform.tenants.secrets.store');
        Route::delete('/tenants/{id}/secrets/{secretId}', [PlatformController::class, 'destroySecret'])->name('platform.tenants.secrets.destroy');
        Route::post('/tenants/{id}/backups', [PlatformController::class, 'createTenantBackup'])->name('platform.tenants.backups.create');
        Route::get('/tenants/{id}/backups/{backupId}/download', [PlatformController::class, 'downloadTenantBackup'])->name('platform.tenants.backups.download');
        Route::delete('/tenants/{id}/backups/{backupId}', [PlatformController::class, 'deleteTenantBackup'])->name('platform.tenants.backups.destroy');
        Route::get('/themes', [PlatformController::class, 'themes'])->name('platform.themes');
        Route::post('/themes/upload', [PlatformController::class, 'uploadTheme'])->name('platform.themes.upload');
        Route::get('/marketplace', [PlatformController::class, 'marketplace'])->name('platform.marketplace');
        Route::get('/plugins', [PlatformController::class, 'plugins'])->name('platform.plugins');

        Route::get('/users', [PlatformController::class, 'users'])->name('platform.users');
        Route::resource('/roles', \App\Http\Controllers\Admin\RoleController::class)->names('platform.roles');
        Route::get('/settings', [PlatformController::class, 'settings'])->name('platform.settings');
        Route::get('/settings', [PlatformController::class, 'settings'])->name('platform.settings');
        Route::post('/settings', [PlatformController::class, 'updateSettings'])->name('platform.settings.update');
        Route::post('/settings/test-email', [PlatformController::class, 'sendTestEmail'])->name('platform.settings.test-email');

        Route::get('/revenue', [\App\Http\Controllers\Admin\RevenueController::class, 'index'])->name('platform.revenue');
        Route::get('/revenue/api/dashboard', [\App\Http\Controllers\Admin\RevenueController::class, 'dashboard']);
        Route::get('/revenue/api/mrr', [\App\Http\Controllers\Admin\RevenueController::class, 'mrrChart']);

        // Advanced Features
        Route::get('/analytics', [PlatformController::class, 'analytics'])->name('platform.analytics');

        // Backups
        Route::get('/backups', [PlatformController::class, 'backups'])->name('platform.backups');
        Route::post('/backups', [PlatformController::class, 'createBackup'])->name('platform.backups.create');
        Route::get('/backups/{id}/download', [PlatformController::class, 'downloadBackup'])->name('platform.backups.download');
        Route::delete('/backups/{id}', [PlatformController::class, 'deleteBackup'])->name('platform.backups.delete');

        // Disaster Recovery Drills
        Route::get('/drills', [PlatformController::class, 'drills'])->name('platform.drills');
        Route::post('/drills', [PlatformController::class, 'runDrill'])->name('platform.drills.run');

        Route::get('/system', [PlatformController::class, 'system'])->name('platform.system');

        // Services Management
        Route::get('/services', [PlatformController::class, 'services'])->name('platform.services');
        Route::post('/services/action', [PlatformController::class, 'serviceAction'])->name('platform.services.action');
        Route::get('/services/logs', [PlatformController::class, 'serviceLogs'])->name('platform.services.logs');
        Route::post('/services/deploy-nginx', [PlatformController::class, 'deployNginxSafe'])->name('platform.services.deploy-nginx');

        Route::get('/audit-logs', [PlatformController::class, 'auditLogs'])->name('platform.audit_logs');

        // Queue Management
        Route::get('/queue', [PlatformController::class, 'queue'])->name('platform.queue');
        Route::post('/queue/restart', [PlatformController::class, 'queueRestart'])->name('platform.queue.restart');
        Route::post('/queue/flush-failed', [PlatformController::class, 'queueFlushFailed'])->name('platform.queue.flush');
    });
});

Route::get('/admin/sso/redirect', [SsoController::class, 'redirect']);
Route::get('/admin/sso/callback', [SsoController::class, 'callback']);
Route::get('/admin/saml/login', [SamlController::class, 'login']);
Route::post('/admin/saml/acs', [SamlController::class, 'acs']);
Route::get('/admin/saml/metadata', [SamlController::class, 'metadata']);
Route::get('/admin/automation/canva/callback', [AutomationController::class, 'canvaCallback'])
    ->middleware(['auth:web', 'admin.2fa', 'admin.audit']);

Route::get('/download/project', function () {
    $newPath = public_path('tastypanel-site.zip');
    $legacyPath = public_path('tastybox-site.zip');
    $path = File::exists($newPath) ? $newPath : $legacyPath;

    if (!File::exists($path)) {
        abort(404);
    }

    return Response::download($path, 'tastypanel-site.zip', ['Content-Type' => 'application/zip']);
});

Route::get('/tastypanel-site.zip', function () {
    $newPath = public_path('tastypanel-site.zip');
    $legacyPath = public_path('tastybox-site.zip');
    $path = File::exists($newPath) ? $newPath : $legacyPath;

    if (!File::exists($path)) {
        abort(404);
    }

    return Response::download($path, 'tastypanel-site.zip', ['Content-Type' => 'application/zip']);
});

Route::get('/tastybox-site.zip', function () {
    return redirect('/tastypanel-site.zip', 301);
});

Route::get('/health', [HealthController::class, 'index']);
Route::get('/health/database', [HealthController::class, 'database']);
Route::get('/health/redis', [HealthController::class, 'redis']);
Route::get('/health/storage', [HealthController::class, 'storage']);
Route::get('/health/queue', [HealthController::class, 'queue']);
Route::get('/metrics', [MetricsController::class, 'index']);

Route::get('/sitemap.xml', [SiteController::class, 'sitemap']);
Route::get('/robots.txt', [SiteController::class, 'robots']);

// Tenant/public site fallback. Keep API and platform paths out of this catch-all.
Route::get('/{any}', [SiteController::class, 'handle'])
    ->where('any', '^(?!api|platform).*$')
    ->middleware('site.cache');
