<?php

use App\Http\Controllers\Admin\AutomationController;
use App\Http\Controllers\Admin\SamlController;
use App\Http\Controllers\Admin\SsoController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\Platform\AuthController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\DatabaseController;
use App\Http\Controllers\Platform\LogController;
use App\Http\Controllers\Platform\PhpController;
use App\Http\Controllers\Platform\SettingsController;
use App\Http\Controllers\Platform\SiteController;
use App\Http\Controllers\Platform\SslController;
use App\Http\Controllers\Platform\UserController;
use App\Http\Controllers\PlatformDeployController;
use App\Http\Controllers\PlatformDomainController;
use App\Http\Controllers\PlatformInstallController;
use App\Http\Controllers\PlatformMonitoringController;
use App\Http\Controllers\PlatformMonitoringRulesController;
use App\Http\Controllers\PlatformOpsController;
use App\Http\Controllers\PlatformSecurityController;
use App\Http\Controllers\PlatformTwoFactorController;
use App\Http\Controllers\SiteController as PublicSiteController;
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

Route::prefix('platform')->middleware(['admin.ip'])->group(function () {
    Route::get('/install', [PlatformInstallController::class, 'show'])->name('platform.install');
    Route::post('/install/complete', [PlatformInstallController::class, 'complete'])->name('platform.install.complete');

    // Auth Routes
    Route::get('/login', [AuthController::class, 'showLogin'])->name('platform.login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:web'])->group(function () {
        Route::get('/2fa', [PlatformTwoFactorController::class, 'show'])->name('platform.2fa');
        Route::post('/2fa/verify', [PlatformTwoFactorController::class, 'verify'])->name('platform.2fa.verify');
        Route::post('/2fa/resend', [PlatformTwoFactorController::class, 'resend'])->name('platform.2fa.resend');
        Route::post('/logout', [AuthController::class, 'logout'])->name('platform.logout');
    });

    Route::middleware(['auth:web', 'admin.2fa'])->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('platform.dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Overview (keep if needed, or map to dashboard)
        Route::get('/overview', [DashboardController::class, 'index'])->name('platform.overview');

        // Operations & Monitoring (Existing Controllers)
        Route::get('/control', [PlatformOpsController::class, 'index'])->name('platform.control');
        Route::post('/control/run', [PlatformOpsController::class, 'run'])->name('platform.control.run');
        Route::post('/control/bulk-run', [PlatformOpsController::class, 'bulkRun'])->name('platform.control.bulk-run');
        Route::post('/control/firewall/rules', [PlatformOpsController::class, 'firewallStore'])->name('platform.control.firewall.store');
        Route::post('/control/firewall/rules/{rule}/toggle', [PlatformOpsController::class, 'firewallToggle'])->name('platform.control.firewall.toggle');
        Route::delete('/control/firewall/rules/{rule}', [PlatformOpsController::class, 'firewallDestroy'])->name('platform.control.firewall.destroy');

        Route::get('/deploy', [PlatformDeployController::class, 'index'])->name('platform.deploy');
        Route::get('/monitoring', [PlatformMonitoringController::class, 'index'])->name('platform.monitoring');
        Route::get('/monitoring/rules', [PlatformMonitoringRulesController::class, 'index'])->name('platform.monitoring.rules');
        Route::post('/monitoring/rules/{tenant}', [PlatformMonitoringRulesController::class, 'upsert'])->name('platform.monitoring.rules.upsert');
        Route::delete('/monitoring/rules/{tenant}', [PlatformMonitoringRulesController::class, 'destroy'])->name('platform.monitoring.rules.destroy');
        Route::post('/monitoring/settings', [PlatformMonitoringController::class, 'updateSettings'])->name('platform.monitoring.settings.update');
        Route::post('/monitoring/uptime-checks', [PlatformMonitoringController::class, 'storeUptimeCheck'])->name('platform.monitoring.uptime.store');
        Route::post('/monitoring/uptime-checks/{check}', [PlatformMonitoringController::class, 'updateUptimeCheck'])->name('platform.monitoring.uptime.update');
        Route::delete('/monitoring/uptime-checks/{check}', [PlatformMonitoringController::class, 'destroyUptimeCheck'])->name('platform.monitoring.uptime.destroy');
        Route::post('/monitoring/uptime-checks/{check}/run', [PlatformMonitoringController::class, 'runUptimeCheck'])->name('platform.monitoring.uptime.run');

        Route::get('/domains', [PlatformDomainController::class, 'index'])->name('platform.domains');
        Route::get('/domains/{domain}/dns', [PlatformDomainController::class, 'dns'])->name('platform.domains.dns');

        Route::get('/security', [PlatformSecurityController::class, 'index'])->name('platform.security');
        Route::post('/security', [PlatformSecurityController::class, 'update'])->name('platform.security.update');
        Route::post('/security/2fa/enable', [PlatformSecurityController::class, 'enableTwoFactor'])->name('platform.security.2fa.enable');
        Route::post('/security/2fa/disable', [PlatformSecurityController::class, 'disableTwoFactor'])->name('platform.security.2fa.disable');
        Route::post('/security/sessions/revoke-other', [PlatformSecurityController::class, 'revokeOtherSessions'])->name('platform.security.sessions.revoke_other');
        Route::post('/security/emergency-lock', [PlatformSecurityController::class, 'emergencyLock'])->name('platform.security.emergency_lock');

        // Sites / Tenants (Refactored to SiteController)
        Route::get('/tenants', [SiteController::class, 'index'])->name('platform.tenants');
        // Add alias for /sites
        Route::get('/sites', [SiteController::class, 'index'])->name('platform.sites');

        Route::get('/tenants/create', [SiteController::class, 'create'])->name('platform.tenants.create');
        Route::post('/tenants', [SiteController::class, 'store'])->name('platform.tenants.store');
        Route::get('/tenants/{id}', [SiteController::class, 'show'])->name('platform.tenants.show');
        Route::delete('/tenants/{id}', [SiteController::class, 'destroy'])->name('platform.tenants.destroy');

        // Tenant Actions handled by SiteController or delegated
        Route::post('/tenants/{id}/vhost', [SiteController::class, 'updateVhost'])->name('platform.tenants.vhost.update');

        // SSL (Refactored to SslController)
        Route::get('/ssl', [SslController::class, 'index'])->name('platform.ssl');
        Route::post('/tenants/{id}/ssl', [SslController::class, 'issue'])->name('platform.tenants.ssl.provision');
        // Route::post('/ssl/{site_id}/issue', [SslController::class, 'issue'])->name('platform.ssl.issue');

        // PHP (Refactored to PhpController)
Route::get('/php', [PhpController::class, 'index'])->name('platform.php.index');
        Route::post('/tenants/{id}/php', [PhpController::class, 'update'])->name('platform.tenants.php.update');

        // Logs (Refactored to LogController)
        Route::get('/logs', [LogController::class, 'index'])->name('platform.logs'); // Platform logs page
        Route::get('/logs/fetch', [LogController::class, 'fetch'])->name('platform.logs.fetch'); // AJAX fetch
        Route::get('/tenants/{id}/logs/{type}', [LogController::class, 'siteLogs'])->name('platform.tenants.logs'); // Site logs AJAX

        // Settings (Refactored to SettingsController)
        Route::get('/settings', [SettingsController::class, 'index'])->name('platform.settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('platform.settings.update');
        Route::post('/settings/test-email', [SettingsController::class, 'sendTestEmail'])->name('platform.settings.test-email');

        // Users (Refactored to UserController)
        Route::get('/users', [UserController::class, 'index'])->name('platform.users');
        Route::get('/users/create', [UserController::class, 'create'])->name('platform.users.create');
        Route::post('/users', [UserController::class, 'store'])->name('platform.users.store');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('platform.users.destroy');
        // System Users
        Route::get('/users/system/create', [UserController::class, 'createSystemUser'])->name('platform.users.system.create');
        Route::post('/users/system', [UserController::class, 'storeSystemUser'])->name('platform.users.system.store');
        Route::delete('/users/system/{user}', [UserController::class, 'destroySystemUser'])->name('platform.users.system.destroy');

        // Databases (New Module)
        Route::get('/databases', [DatabaseController::class, 'index'])->name('platform.databases');
        Route::get('/databases/create', [DatabaseController::class, 'create'])->name('platform.databases.create');
        Route::post('/databases', [DatabaseController::class, 'store'])->name('platform.databases.store');
        Route::delete('/databases/{database}', [DatabaseController::class, 'destroy'])->name('platform.databases.destroy');
        Route::post('/databases/{database}/backup', [DatabaseController::class, 'backup'])->name('platform.databases.backup');

        // Legacy/Other Tenant Actions (Delegate to SiteController or keep PlatformController imports if needed)
        // Since I only migrated core CRUD, I might need to map others or leave them broken for now if not refactored.
        // But prompt says "Refactor Architecture -> Yes".
        // I should ensure existing routes work.
        // I will map remaining tenant routes to SiteController (assuming I add methods there later or reused PlatformController logic via inheritance or delegation).
        // Since SiteController::show delegates to PlatformController::showTenant, the view might generate URLs to routes I haven't defined yet.
        // e.g. `platform.tenants.cron.store`

        // I will re-add the missing routes pointing to PlatformController for backward compatibility if I haven't moved them.
        // But PlatformController is large.
        // Ideally I should move them.

        Route::post('/tenants/{id}/cron', [\App\Http\Controllers\PlatformController::class, 'storeCronJob'])->name('platform.tenants.cron.store');
        Route::delete('/tenants/{id}/cron/{index}', [\App\Http\Controllers\PlatformController::class, 'destroyCronJob'])->name('platform.tenants.cron.destroy');
        Route::get('/tenants/{id}/phpmyadmin', [\App\Http\Controllers\PlatformController::class, 'phpmyadminFrame'])->name('platform.tenants.phpmyadmin');
        Route::post('/tenants/{id}/phpmyadmin/provision', [\App\Http\Controllers\PlatformController::class, 'provisionPhpMyAdmin'])->name('platform.tenants.phpmyadmin.provision');

        Route::get('/tenants/{id}/staging', [\App\Http\Controllers\Admin\StagingController::class, 'index'])->name('platform.tenants.staging');
        Route::post('/tenants/{id}/staging/enable', [\App\Http\Controllers\Admin\StagingController::class, 'enable'])->name('platform.tenants.staging.enable');
        Route::post('/tenants/{id}/staging/sync', [\App\Http\Controllers\Admin\StagingController::class, 'sync'])->name('platform.tenants.staging.sync');
        Route::post('/tenants/{id}/staging/disable', [\App\Http\Controllers\Admin\StagingController::class, 'destroy'])->name('platform.tenants.staging.disable');

        Route::get('/tenants/{id}/preview', [\App\Http\Controllers\Admin\PreviewWebController::class, 'index'])->name('platform.tenants.preview');
        Route::post('/tenants/{id}/preview/enable', [\App\Http\Controllers\Admin\PreviewWebController::class, 'enable'])->name('platform.tenants.preview.enable');
        Route::post('/tenants/{id}/preview/sync', [\App\Http\Controllers\Admin\PreviewWebController::class, 'sync'])->name('platform.tenants.preview.sync');
        Route::post('/tenants/{id}/preview/promote', [\App\Http\Controllers\Admin\PreviewWebController::class, 'promote'])->name('platform.tenants.preview.promote');
        Route::post('/tenants/{id}/preview/disable', [\App\Http\Controllers\Admin\PreviewWebController::class, 'destroy'])->name('platform.tenants.preview.disable');

        Route::get('/tenants/{id}/automation', [\App\Http\Controllers\Admin\AutomationController::class, 'index'])->name('platform.tenants.automation');

        Route::post('/tenants/{id}/toggle-status', [\App\Http\Controllers\PlatformController::class, 'toggleTenantStatus'])->name('platform.tenants.toggle-status');
        Route::post('/tenants/bulk-activate', [\App\Http\Controllers\PlatformController::class, 'bulkActivate'])->name('platform.tenants.bulk-activate');
        Route::post('/tenants/bulk-deactivate', [\App\Http\Controllers\PlatformController::class, 'bulkDeactivate'])->name('platform.tenants.bulk-deactivate');
        Route::post('/tenants/bulk-delete', [\App\Http\Controllers\PlatformController::class, 'bulkDelete'])->name('platform.tenants.bulk-delete');

        Route::post('/tenants/{id}/install-app', [\App\Http\Controllers\PlatformController::class, 'installApp'])->name('platform.tenants.install-app');
        Route::post('/tenants/{id}/uninstall-app', [\App\Http\Controllers\PlatformController::class, 'uninstallApp'])->name('platform.tenants.uninstall-app');
        Route::post('/tenants/{id}/secrets', [\App\Http\Controllers\PlatformController::class, 'storeSecret'])->name('platform.tenants.secrets.store');
        Route::delete('/tenants/{id}/secrets/{secretId}', [\App\Http\Controllers\PlatformController::class, 'destroySecret'])->name('platform.tenants.secrets.destroy');
        Route::post('/tenants/{id}/backups', [\App\Http\Controllers\PlatformController::class, 'createTenantBackup'])->name('platform.tenants.backups.create');
        Route::get('/tenants/{id}/backups/{backupId}/download', [\App\Http\Controllers\PlatformController::class, 'downloadTenantBackup'])->name('platform.tenants.backups.download');
        Route::delete('/tenants/{id}/backups/{backupId}', [\App\Http\Controllers\PlatformController::class, 'deleteTenantBackup'])->name('platform.tenants.backups.destroy');

        Route::get('/themes', [\App\Http\Controllers\PlatformController::class, 'themes'])->name('platform.themes');
        Route::post('/themes/upload', [\App\Http\Controllers\PlatformController::class, 'uploadTheme'])->name('platform.themes.upload');
        Route::get('/marketplace', [\App\Http\Controllers\PlatformController::class, 'marketplace'])->name('platform.marketplace');
        Route::get('/plugins', [\App\Http\Controllers\PlatformController::class, 'plugins'])->name('platform.plugins');

        Route::resource('/roles', \App\Http\Controllers\Admin\RoleController::class)->names('platform.roles');

        // Advanced Features
        Route::get('/analytics', [\App\Http\Controllers\PlatformController::class, 'analytics'])->name('platform.analytics');
        Route::get('/backups', [\App\Http\Controllers\PlatformController::class, 'backups'])->name('platform.backups');
        Route::post('/backups', [\App\Http\Controllers\PlatformController::class, 'createBackup'])->name('platform.backups.create');
        Route::get('/backups/{id}/download', [\App\Http\Controllers\PlatformController::class, 'downloadBackup'])->name('platform.backups.download');
        Route::delete('/backups/{id}', [\App\Http\Controllers\PlatformController::class, 'deleteBackup'])->name('platform.backups.delete');

        Route::get('/drills', [\App\Http\Controllers\PlatformController::class, 'drills'])->name('platform.drills');
        Route::post('/drills', [\App\Http\Controllers\PlatformController::class, 'runDrill'])->name('platform.drills.run');

        Route::get('/system', [\App\Http\Controllers\PlatformController::class, 'system'])->name('platform.system');

        Route::get('/services', [\App\Http\Controllers\PlatformController::class, 'services'])->name('platform.services');
        Route::post('/services/action', [\App\Http\Controllers\PlatformController::class, 'serviceAction'])->name('platform.services.action');
        Route::get('/services/logs', [\App\Http\Controllers\PlatformController::class, 'serviceLogs'])->name('platform.services.logs');
        Route::post('/services/deploy-nginx', [\App\Http\Controllers\PlatformController::class, 'deployNginxSafe'])->name('platform.services.deploy-nginx');

        Route::get('/audit-logs', [\App\Http\Controllers\PlatformController::class, 'auditLogs'])->name('platform.audit_logs');

        Route::get('/queue', [\App\Http\Controllers\PlatformController::class, 'queue'])->name('platform.queue');
        Route::post('/queue/restart', [\App\Http\Controllers\PlatformController::class, 'queueRestart'])->name('platform.queue.restart');
        Route::post('/queue/flush-failed', [\App\Http\Controllers\PlatformController::class, 'queueFlushFailed'])->name('platform.queue.flush');
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

Route::get('/sitemap.xml', [PublicSiteController::class, 'sitemap']);
Route::get('/robots.txt', [PublicSiteController::class, 'robots']);

// Tenant/public site fallback. Keep API and platform paths out of this catch-all.
Route::get('/{any}', [PublicSiteController::class, 'handle'])
    ->where('any', '^(?!api|platform).*$')
    ->middleware('site.cache');
