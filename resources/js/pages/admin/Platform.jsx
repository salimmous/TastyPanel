import { useEffect, useState } from 'react';
import { api } from '../../services/api';
import { Shield, Server, HardDrive, RefreshCw, Database, Activity, ClipboardList, Download, Palette, Search } from 'lucide-react';

const defaultSettings = {
    panel_allowed_ips: '',
    force_2fa: false,
    rate_limit_per_minute: 120,
    backup_retention_days: 7,
    ssl_alert_days: 14,
    http3_check_interval_minutes: 30,
    ssl_check_interval_hours: 6,
    backup_interval_hours: 24,
    analytics_interval_hours: 6,
    uptime_check_interval_minutes: 5,
    integrity_check_interval_hours: 24,
    cron_enabled: true,
    cron_timezone: 'UTC',
    audit_export_retention_days: 30,
    backup_s3_enabled: false,
    backup_keep_local: true,
    backup_s3_prefix: 'tastypanel/backups',
    search_enabled: true,
    search_driver: 'database',
    search_endpoint: '',
    search_api_key: '',
    search_index_prefix: 'tastypanel',
    brand_name: 'TastyPanel',
    brand_logo_url: '',
    brand_favicon_url: '',
    brand_primary_color: '#2563eb',
    brand_secondary_color: '#111827',
    brand_accent_color: '#f97316',
    brand_login_message: 'Admin Dashboard',
    alerts_emails: '',
    alerts_slack_webhook: '',
    alerts_interval_hours: 24,
    alerts_send_empty: false,
    alerts_last_sent_at: null,
    sso_enabled: false,
    sso_provider_label: 'SSO',
    sso_client_id: '',
    sso_client_secret: '',
    sso_auth_url: '',
    sso_token_url: '',
    sso_userinfo_url: '',
    sso_redirect_url: '',
    sso_scopes: 'openid email profile',
    sso_allowed_domains: '',
    sso_auto_create: false,
    sso_enforce: false,
    sso_default_role: 'tenant-admin',
    sso_default_tenant_id: null,
    saml_enabled: false,
    saml_provider_label: 'SAML SSO',
    saml_idp_metadata_url: '',
    saml_idp_metadata_xml: '',
    saml_idp_entity_id: '',
    saml_idp_sso_url: '',
    saml_idp_slo_url: '',
    saml_idp_x509: '',
    saml_sp_entity_id: '',
    saml_acs_url: '',
    saml_slo_url: '',
    saml_nameid_format: 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
    saml_attribute_email: 'email',
    saml_attribute_name: 'name',
    saml_attribute_groups: 'groups',
    saml_allowed_domains: '',
    saml_auto_create: false,
    saml_enforce: false,
    saml_default_role: 'tenant-admin',
    saml_default_tenant_id: null,
};

export default function Platform() {
    const [overview, setOverview] = useState(null);
    const [settings, setSettings] = useState(defaultSettings);
    const [savingSettings, setSavingSettings] = useState(false);
    const [queueAction, setQueueAction] = useState('');
    const [backups, setBackups] = useState([]);
    const [backupRunning, setBackupRunning] = useState(false);
    const [restoringId, setRestoringId] = useState(null);
    const [plans, setPlans] = useState([]);
    const [tenants, setTenants] = useState([]);
    const [planForm, setPlanForm] = useState({
        name: '',
        slug: '',
        price_cents: 0,
        interval: 'monthly',
        is_active: true,
        max_posts: 0,
        max_users: 0,
        rate_limit_per_minute: 0,
        storage_gb: 0,
    });
    const [assignForm, setAssignForm] = useState({ tenantId: '', planId: '' });
    const [auditLogs, setAuditLogs] = useState([]);
    const [auditSearch, setAuditSearch] = useState('');
    const [auditExports, setAuditExports] = useState([]);
    const [auditExportDays, setAuditExportDays] = useState(30);
    const [auditExportRunning, setAuditExportRunning] = useState(false);
    const [searchTestQuery, setSearchTestQuery] = useState('');
    const [searchTestResult, setSearchTestResult] = useState(null);
    const [searchRunning, setSearchRunning] = useState(false);
    const [reindexRunning, setReindexRunning] = useState(false);
    const [alerts, setAlerts] = useState({ ssl_expiring: [], http3_issues: [], storage_overages: [] });
    const [platformServices, setPlatformServices] = useState([]);
    const [serviceActionTarget, setServiceActionTarget] = useState('');
    const [serviceLogs, setServiceLogs] = useState({});
    const [serviceLogTarget, setServiceLogTarget] = useState('');
    const [serviceMessage, setServiceMessage] = useState('');
    const [safeDeployLoading, setSafeDeployLoading] = useState(false);
    const [safeDeployMessage, setSafeDeployMessage] = useState('');

    useEffect(() => {
        loadAll();
    }, []);

    const loadAll = async () => {
        const [overviewRes, settingsRes, backupRes, plansRes, tenantsRes, auditRes, auditExportRes, alertsRes, servicesRes] = await Promise.all([
            api.admin.getPlatformOverview(),
            api.admin.getPlatformSettings(),
            api.admin.getBackups(),
            api.admin.getPlans(),
            api.admin.getTenants(),
            api.admin.getAuditLogs(1, ''),
            api.admin.getAuditExports(1),
            api.admin.getPlatformAlerts(),
            api.admin.getPlatformServices(),
        ]);
        setOverview(overviewRes || null);
        setSettings(settingsRes?.data || defaultSettings);
        setBackups(backupRes?.data || []);
        setPlans(plansRes?.data || []);
        setTenants(tenantsRes?.data || []);
        setAuditLogs(auditRes?.data || []);
        setAuditExports(auditExportRes?.data || []);
        setAlerts(alertsRes || { ssl_expiring: [], http3_issues: [], storage_overages: [] });
        setPlatformServices(servicesRes?.data || []);
    };

    const saveSettings = async () => {
        setSavingSettings(true);
        try {
            const response = await api.admin.updatePlatformSettings(settings);
            setSettings(response?.data || settings);
        } finally {
            setSavingSettings(false);
        }
    };

    const handleQueueRestart = async () => {
        setQueueAction('restart');
        await api.admin.restartQueue();
        const refreshed = await api.admin.getPlatformOverview();
        setOverview(refreshed || overview);
        setQueueAction('');
    };

    const handleQueueFlush = async () => {
        setQueueAction('flush');
        await api.admin.flushFailedQueue();
        const refreshed = await api.admin.getPlatformOverview();
        setOverview(refreshed || overview);
        setQueueAction('');
    };

    const handleBackup = async () => {
        setBackupRunning(true);
        await api.admin.createBackup();
        const refreshed = await api.admin.getBackups();
        setBackups(refreshed?.data || []);
        setBackupRunning(false);
    };

    const handleRestore = async (backupId) => {
        const confirm = window.prompt('Type RESTORE to confirm database + storage restore.');
        if (!confirm) return;
        setRestoringId(backupId);
        await api.admin.restoreBackup(backupId, confirm);
        setRestoringId(null);
    };

    const createPlan = async () => {
        const payload = {
            name: planForm.name,
            slug: planForm.slug,
            price_cents: Number(planForm.price_cents || 0),
            interval: planForm.interval,
            is_active: planForm.is_active,
            limits: {
                max_posts: Number(planForm.max_posts || 0),
                max_users: Number(planForm.max_users || 0),
            rate_limit_per_minute: Number(planForm.rate_limit_per_minute || 0),
            storage_gb: Number(planForm.storage_gb || 0),
        },
    };
        await api.admin.createPlan(payload);
        const refreshed = await api.admin.getPlans();
        setPlans(refreshed?.data || []);
        setPlanForm({
            name: '',
            slug: '',
            price_cents: 0,
            interval: 'monthly',
            is_active: true,
            max_posts: 0,
            max_users: 0,
            rate_limit_per_minute: 0,
            storage_gb: 0,
        });
    };

    const assignPlan = async () => {
        if (!assignForm.tenantId || !assignForm.planId) return;
        await api.admin.assignTenantPlan(assignForm.tenantId, assignForm.planId);
        const refreshed = await api.admin.getTenants();
        setTenants(refreshed?.data || []);
        setAssignForm({ tenantId: '', planId: '' });
    };

    const refreshAuditLogs = async () => {
        const res = await api.admin.getAuditLogs(1, auditSearch);
        setAuditLogs(res?.data || []);
    };

    const runAuditExport = async () => {
        setAuditExportRunning(true);
        try {
            const res = await api.admin.createAuditExport(auditExportDays || null);
            if (res?.data) {
                setAuditExports((prev) => [res.data, ...prev]);
            } else {
                const refreshed = await api.admin.getAuditExports(1);
                setAuditExports(refreshed?.data || []);
            }
        } finally {
            setAuditExportRunning(false);
        }
    };

    const runSearchTest = async () => {
        if (!searchTestQuery) return;
        setSearchRunning(true);
        try {
            const res = await api.admin.testSearch(searchTestQuery);
            setSearchTestResult(res || null);
        } finally {
            setSearchRunning(false);
        }
    };

    const runReindex = async () => {
        setReindexRunning(true);
        try {
            await api.admin.reindexSearch();
        } finally {
            setReindexRunning(false);
        }
    };

    const serviceStateClass = (state) => {
        if (state === 'active') return 'bg-emerald-100 text-emerald-700';
        if (state === 'inactive') return 'bg-amber-100 text-amber-700';
        if (state === 'missing') return 'bg-rose-100 text-rose-700';
        return 'bg-gray-100 text-gray-700';
    };

    const refreshPlatformServices = async () => {
        const response = await api.admin.getPlatformServices();
        setPlatformServices(response?.data || []);
    };

    const runServiceAction = async (serviceKey, action) => {
        setServiceActionTarget(`${serviceKey}:${action}`);
        setServiceMessage('');
        try {
            const response = await api.admin.actionPlatformService(serviceKey, action);
            if (response?.success === false) {
                setServiceMessage(response?.output || 'Service action failed.');
            } else {
                setServiceMessage(response?.output || `Action '${action}' completed.`);
            }
            await refreshPlatformServices();
        } finally {
            setServiceActionTarget('');
        }
    };

    const openServiceLogs = async (serviceKey) => {
        setServiceLogTarget(serviceKey);
        try {
            const response = await api.admin.getPlatformServiceLogs(serviceKey, 120);
            setServiceLogs((prev) => ({ ...prev, [serviceKey]: response?.output || '' }));
        } finally {
            setServiceLogTarget('');
        }
    };

    const runSafeNginxDeploy = async (mode) => {
        setSafeDeployLoading(true);
        setSafeDeployMessage('');
        try {
            const response = await api.admin.deployNginxSafe(mode);
            const output = response?.output || (response?.success ? 'Safe deploy completed.' : 'Safe deploy failed.');
            setSafeDeployMessage(output);
            await refreshPlatformServices();
        } finally {
            setSafeDeployLoading(false);
        }
    };

    const status = overview?.status || {};
    const queue = overview?.queue || {};

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Platform Control Panel</h1>
                    <p className="text-sm text-gray-500">Server health, security, backups, plans, and logs.</p>
                </div>
                <button
                    onClick={loadAll}
                    className="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-white border border-gray-200 hover:bg-gray-50"
                >
                    <RefreshCw className="w-4 h-4" />
                    Refresh
                </button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <div className="flex items-center gap-2 mb-3 text-gray-700">
                        <Server className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Server Status</h2>
                    </div>
                    <div className="text-xs text-gray-500 space-y-2">
                        <p>Load: {status.load?.['1m']} / {status.load?.['5m']} / {status.load?.['15m']}</p>
                        <p>Memory: {status.memory?.used_mb} / {status.memory?.total_mb} MB</p>
                        <p>Disk: {status.disk?.used_gb} / {status.disk?.total_gb} GB</p>
                        <p>Services: nginx {status.services?.nginx}, php {status.services?.php_fpm}, mysql {status.services?.mysql}</p>
                    </div>
                </div>

                <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <div className="flex items-center gap-2 mb-3 text-gray-700">
                        <Activity className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Queue</h2>
                    </div>
                    <div className="text-xs text-gray-500 space-y-2">
                        <p>Pending jobs: {queue.pending ?? 0}</p>
                        <p>Failed jobs: {queue.failed ?? 0}</p>
                    </div>
                    <div className="flex gap-2 mt-4">
                        <button
                            onClick={handleQueueRestart}
                            disabled={queueAction === 'restart'}
                            className="px-3 py-2 text-xs rounded-lg border border-gray-200 hover:bg-gray-50"
                        >
                            {queueAction === 'restart' ? 'Restarting...' : 'Restart workers'}
                        </button>
                        <button
                            onClick={handleQueueFlush}
                            disabled={queueAction === 'flush'}
                            className="px-3 py-2 text-xs rounded-lg border border-gray-200 hover:bg-gray-50"
                        >
                            {queueAction === 'flush' ? 'Flushing...' : 'Flush failed'}
                        </button>
                    </div>
                </div>

                <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <div className="flex items-center gap-2 mb-3 text-gray-700">
                        <Shield className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Security</h2>
                    </div>
                    <div className="space-y-3 text-xs text-gray-600">
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Panel allowlist (IPs)</label>
                            <textarea
                                value={settings.panel_allowed_ips || ''}
                                onChange={(e) => setSettings({ ...settings, panel_allowed_ips: e.target.value })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                placeholder="1.2.3.4, 5.6.7.8"
                            />
                        </div>
                        <div className="flex items-center justify-between">
                            <span>Force 2FA for superadmin</span>
                            <input
                                type="checkbox"
                                checked={!!settings.force_2fa}
                                onChange={(e) => setSettings({ ...settings, force_2fa: e.target.checked })}
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Rate limit / min</label>
                            <input
                                type="number"
                                value={settings.rate_limit_per_minute || 0}
                                onChange={(e) => setSettings({ ...settings, rate_limit_per_minute: Number(e.target.value) })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">SSL alert days</label>
                            <input
                                type="number"
                                value={settings.ssl_alert_days || 14}
                                onChange={(e) => setSettings({ ...settings, ssl_alert_days: Number(e.target.value) })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">HTTP/3 check (minutes)</label>
                            <input
                                type="number"
                                value={settings.http3_check_interval_minutes || 30}
                                onChange={(e) => setSettings({ ...settings, http3_check_interval_minutes: Number(e.target.value) })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">SSL check (hours)</label>
                            <input
                                type="number"
                                value={settings.ssl_check_interval_hours || 6}
                                onChange={(e) => setSettings({ ...settings, ssl_check_interval_hours: Number(e.target.value) })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Backups (hours)</label>
                            <input
                                type="number"
                                value={settings.backup_interval_hours || 24}
                                onChange={(e) => setSettings({ ...settings, backup_interval_hours: Number(e.target.value) })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Analytics interval (hours)</label>
                            <input
                                type="number"
                                value={settings.analytics_interval_hours || 6}
                                onChange={(e) => setSettings({ ...settings, analytics_interval_hours: Number(e.target.value) })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Uptime check (minutes)</label>
                            <input
                                type="number"
                                value={settings.uptime_check_interval_minutes || 5}
                                onChange={(e) => setSettings({ ...settings, uptime_check_interval_minutes: Number(e.target.value) })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Integrity check (hours)</label>
                            <input
                                type="number"
                                value={settings.integrity_check_interval_hours || 24}
                                onChange={(e) => setSettings({ ...settings, integrity_check_interval_hours: Number(e.target.value) })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            />
                        </div>
                        <div className="flex items-center justify-between">
                            <span>Enable scheduled Cron jobs</span>
                            <input
                                type="checkbox"
                                checked={settings.cron_enabled !== false}
                                onChange={(e) => setSettings({ ...settings, cron_enabled: e.target.checked })}
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Cron timezone</label>
                            <input
                                value={settings.cron_timezone || 'UTC'}
                                onChange={(e) => setSettings({ ...settings, cron_timezone: e.target.value })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                placeholder="UTC"
                            />
                            <p className="mt-1 text-[11px] text-gray-500">
                                System cron command: <code className="rounded bg-gray-100 px-1 py-0.5">* * * * * cd /var/www/tastypanel &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
                            </p>
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Backup retention (days)</label>
                            <input
                                type="number"
                                value={settings.backup_retention_days || 7}
                                onChange={(e) => setSettings({ ...settings, backup_retention_days: Number(e.target.value) })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            />
                        </div>
                        <div className="flex items-center justify-between">
                            <span>Upload backups to S3</span>
                            <input
                                type="checkbox"
                                checked={!!settings.backup_s3_enabled}
                                onChange={(e) => setSettings({ ...settings, backup_s3_enabled: e.target.checked })}
                            />
                        </div>
                        <div className="flex items-center justify-between">
                            <span>Keep local backups</span>
                            <input
                                type="checkbox"
                                checked={settings.backup_keep_local !== false}
                                onChange={(e) => setSettings({ ...settings, backup_keep_local: e.target.checked })}
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">S3 prefix</label>
                            <input
                                value={settings.backup_s3_prefix || 'tastypanel/backups'}
                                onChange={(e) => setSettings({ ...settings, backup_s3_prefix: e.target.value })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                placeholder="tastypanel/backups"
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Alert emails (comma separated)</label>
                            <input
                                value={settings.alerts_emails || ''}
                                onChange={(e) => setSettings({ ...settings, alerts_emails: e.target.value })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                placeholder="ops@example.com, admin@example.com"
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Slack webhook URL</label>
                            <input
                                value={settings.alerts_slack_webhook || ''}
                                onChange={(e) => setSettings({ ...settings, alerts_slack_webhook: e.target.value })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                placeholder="https://hooks.slack.com/services/..."
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Alert interval (hours)</label>
                            <input
                                type="number"
                                value={settings.alerts_interval_hours || 24}
                                onChange={(e) => setSettings({ ...settings, alerts_interval_hours: Number(e.target.value) })}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            />
                        </div>
                        <div className="flex items-center justify-between">
                            <span>Send empty alert reports</span>
                            <input
                                type="checkbox"
                                checked={!!settings.alerts_send_empty}
                                onChange={(e) => setSettings({ ...settings, alerts_send_empty: e.target.checked })}
                            />
                        </div>
                        {settings.alerts_last_sent_at ? (
                            <p className="text-[11px] text-gray-500">Last alert sent: {settings.alerts_last_sent_at}</p>
                        ) : null}
                        <button
                            onClick={saveSettings}
                            disabled={savingSettings}
                            className="w-full mt-2 px-3 py-2 text-xs rounded-lg bg-gray-900 text-white"
                        >
                            {savingSettings ? 'Saving...' : 'Save Settings'}
                        </button>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2 text-gray-700">
                        <Server className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Service Manager</h2>
                    </div>
                    <button
                        onClick={refreshPlatformServices}
                        className="px-3 py-1.5 text-xs rounded-lg border border-gray-200 hover:bg-gray-50"
                    >
                        Refresh services
                    </button>
                </div>

                {serviceMessage ? (
                    <div className="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-800 whitespace-pre-wrap">
                        {serviceMessage}
                    </div>
                ) : null}

                <div className="overflow-auto">
                    <table className="w-full text-xs">
                        <thead>
                            <tr className="text-left text-gray-500 border-b border-gray-100">
                                <th className="py-2 pr-3">Service</th>
                                <th className="py-2 pr-3">Unit</th>
                                <th className="py-2 pr-3">State</th>
                                <th className="py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {platformServices.map((service) => (
                                <tr key={service.key} className="border-b border-gray-50">
                                    <td className="py-2 pr-3 font-semibold text-gray-700">{service.label || service.key}</td>
                                    <td className="py-2 pr-3 text-gray-500">{service.unit || '—'}</td>
                                    <td className="py-2 pr-3">
                                        <span className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ${serviceStateClass(service.state)}`}>
                                            {service.state || 'unknown'}
                                        </span>
                                    </td>
                                    <td className="py-2">
                                        <div className="flex flex-wrap gap-2">
                                            <button
                                                onClick={() => runServiceAction(service.key, 'start')}
                                                disabled={!service.managed || serviceActionTarget === `${service.key}:start`}
                                                className="px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-50"
                                            >
                                                {serviceActionTarget === `${service.key}:start` ? 'Starting...' : 'Start'}
                                            </button>
                                            <button
                                                onClick={() => runServiceAction(service.key, 'stop')}
                                                disabled={!service.managed || serviceActionTarget === `${service.key}:stop`}
                                                className="px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-50"
                                            >
                                                {serviceActionTarget === `${service.key}:stop` ? 'Stopping...' : 'Stop'}
                                            </button>
                                            <button
                                                onClick={() => runServiceAction(service.key, 'restart')}
                                                disabled={!service.managed || serviceActionTarget === `${service.key}:restart`}
                                                className="px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-50"
                                            >
                                                {serviceActionTarget === `${service.key}:restart` ? 'Restarting...' : 'Restart'}
                                            </button>
                                            <button
                                                onClick={() => openServiceLogs(service.key)}
                                                disabled={serviceLogTarget === service.key}
                                                className="px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-50"
                                            >
                                                {serviceLogTarget === service.key ? 'Loading logs...' : 'Logs'}
                                            </button>
                                        </div>
                                        {service.detail ? (
                                            <div className="mt-1 text-[11px] text-gray-500">{service.detail}</div>
                                        ) : null}
                                        {serviceLogs[service.key] ? (
                                            <pre className="mt-2 max-h-40 overflow-auto rounded-lg bg-gray-900 p-2 text-[11px] text-gray-100 whitespace-pre-wrap">
                                                {serviceLogs[service.key]}
                                            </pre>
                                        ) : null}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-3">
                <div className="flex items-center gap-2 text-gray-700">
                    <Shield className="w-4 h-4" />
                    <h2 className="text-sm font-semibold">Deploy Safety</h2>
                </div>
                <p className="text-xs text-gray-500">
                    Backup current Nginx vhost directories, run <code className="rounded bg-gray-100 px-1 py-0.5">nginx -t</code>, and auto-rollback if reload fails.
                </p>
                <div className="flex flex-wrap gap-2">
                    <button
                        onClick={() => runSafeNginxDeploy('deploy')}
                        disabled={safeDeployLoading}
                        className="px-3 py-2 text-xs rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-60"
                    >
                        {safeDeployLoading ? 'Running...' : 'Run Safe Deploy'}
                    </button>
                    <button
                        onClick={() => runSafeNginxDeploy('rollback')}
                        disabled={safeDeployLoading}
                        className="px-3 py-2 text-xs rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-60"
                    >
                        {safeDeployLoading ? 'Running...' : 'Rollback Latest Backup'}
                    </button>
                </div>
                {safeDeployMessage ? (
                    <pre className="max-h-48 overflow-auto rounded-lg bg-gray-900 p-3 text-[11px] text-gray-100 whitespace-pre-wrap">
                        {safeDeployMessage}
                    </pre>
                ) : null}
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center gap-2 mb-3 text-gray-700">
                    <Palette className="w-4 h-4" />
                    <h2 className="text-sm font-semibold">Branding</h2>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-gray-600">
                    <div>
                        <label className="text-xs font-semibold uppercase text-gray-500">Brand name</label>
                        <input
                            value={settings.brand_name || ''}
                            onChange={(e) => setSettings({ ...settings, brand_name: e.target.value })}
                            className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="Control Panel"
                        />
                    </div>
                    <div>
                        <label className="text-xs font-semibold uppercase text-gray-500">Login message</label>
                        <input
                            value={settings.brand_login_message || ''}
                            onChange={(e) => setSettings({ ...settings, brand_login_message: e.target.value })}
                            className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="Admin Dashboard"
                        />
                    </div>
                    <div>
                        <label className="text-xs font-semibold uppercase text-gray-500">Logo URL</label>
                        <input
                            value={settings.brand_logo_url || ''}
                            onChange={(e) => setSettings({ ...settings, brand_logo_url: e.target.value })}
                            className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="https://cdn.example.com/logo.svg"
                        />
                    </div>
                    <div>
                        <label className="text-xs font-semibold uppercase text-gray-500">Favicon URL</label>
                        <input
                            value={settings.brand_favicon_url || ''}
                            onChange={(e) => setSettings({ ...settings, brand_favicon_url: e.target.value })}
                            className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="https://cdn.example.com/favicon.png"
                        />
                    </div>
                    <div>
                        <label className="text-xs font-semibold uppercase text-gray-500">Primary color</label>
                        <input
                            value={settings.brand_primary_color || '#2563eb'}
                            onChange={(e) => setSettings({ ...settings, brand_primary_color: e.target.value })}
                            className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="#2563eb"
                        />
                    </div>
                    <div>
                        <label className="text-xs font-semibold uppercase text-gray-500">Secondary color</label>
                        <input
                            value={settings.brand_secondary_color || '#111827'}
                            onChange={(e) => setSettings({ ...settings, brand_secondary_color: e.target.value })}
                            className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="#111827"
                        />
                    </div>
                    <div>
                        <label className="text-xs font-semibold uppercase text-gray-500">Accent color</label>
                        <input
                            value={settings.brand_accent_color || '#f97316'}
                            onChange={(e) => setSettings({ ...settings, brand_accent_color: e.target.value })}
                            className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="#f97316"
                        />
                    </div>
                </div>
                <button
                    onClick={saveSettings}
                    disabled={savingSettings}
                    className="w-full mt-4 px-3 py-2 text-xs rounded-lg bg-gray-900 text-white"
                >
                    {savingSettings ? 'Saving...' : 'Save Branding'}
                </button>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center gap-2 mb-3 text-gray-700">
                    <Search className="w-4 h-4" />
                    <h2 className="text-sm font-semibold">Search Integration</h2>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-gray-600">
                    <label className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={!!settings.search_enabled}
                            onChange={(e) => setSettings({ ...settings, search_enabled: e.target.checked })}
                        />
                        Enable search
                    </label>
                    <select
                        value={settings.search_driver || 'database'}
                        onChange={(e) => setSettings({ ...settings, search_driver: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-xs"
                    >
                        <option value="database">Database</option>
                        <option value="meilisearch">Meilisearch</option>
                        <option value="elasticsearch">Elasticsearch</option>
                    </select>
                    <input
                        value={settings.search_endpoint || ''}
                        onChange={(e) => setSettings({ ...settings, search_endpoint: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-xs"
                        placeholder="Search endpoint (optional)"
                    />
                    <input
                        value={settings.search_api_key || ''}
                        onChange={(e) => setSettings({ ...settings, search_api_key: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-xs"
                        placeholder="API key"
                    />
                    <input
                        value={settings.search_index_prefix || 'tastypanel'}
                        onChange={(e) => setSettings({ ...settings, search_index_prefix: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-xs"
                        placeholder="Index prefix"
                    />
                </div>
                <div className="mt-3 flex flex-wrap items-center gap-2">
                    <button
                        onClick={saveSettings}
                        disabled={savingSettings}
                        className="px-3 py-2 text-xs rounded-lg border border-gray-200"
                    >
                        {savingSettings ? 'Saving...' : 'Save Search Settings'}
                    </button>
                    <button
                        onClick={runReindex}
                        disabled={reindexRunning}
                        className="px-3 py-2 text-xs rounded-lg bg-gray-900 text-white"
                    >
                        {reindexRunning ? 'Reindexing...' : 'Reindex'}
                    </button>
                </div>
                <div className="mt-4 flex flex-wrap items-center gap-2">
                    <input
                        value={searchTestQuery}
                        onChange={(e) => setSearchTestQuery(e.target.value)}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-xs flex-1"
                        placeholder="Test query (e.g. chicken)"
                    />
                    <button
                        onClick={runSearchTest}
                        disabled={searchRunning}
                        className="px-3 py-2 text-xs rounded-lg border border-gray-200"
                    >
                        {searchRunning ? 'Testing...' : 'Test'}
                    </button>
                </div>
                {searchTestResult && (
                    <div className="mt-3 text-[11px] text-gray-600">
                        <pre className="rounded-lg bg-gray-50 border border-gray-100 p-3 overflow-auto">
{JSON.stringify(searchTestResult, null, 2)}
                        </pre>
                    </div>
                )}
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center justify-between mb-4">
                    <div>
                        <h2 className="text-sm font-semibold text-gray-700">SSO Settings</h2>
                        <p className="text-xs text-gray-500">OIDC provider configuration (Google/Microsoft). Redirect URL: {settings.sso_redirect_url || 'auto'}</p>
                    </div>
                    <div className="flex items-center gap-3 text-xs">
                        <label className="flex items-center gap-2">
                            <span>Enable SSO</span>
                            <input
                                type="checkbox"
                                checked={!!settings.sso_enabled}
                                onChange={(e) => setSettings({ ...settings, sso_enabled: e.target.checked })}
                            />
                        </label>
                        <label className="flex items-center gap-2">
                            <span>Enforce SSO</span>
                            <input
                                type="checkbox"
                                checked={!!settings.sso_enforce}
                                onChange={(e) => setSettings({ ...settings, sso_enforce: e.target.checked })}
                            />
                        </label>
                    </div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <input
                        value={settings.sso_provider_label || ''}
                        onChange={(e) => setSettings({ ...settings, sso_provider_label: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Provider label (e.g. Google, Microsoft)"
                    />
                    <input
                        value={settings.sso_client_id || ''}
                        onChange={(e) => setSettings({ ...settings, sso_client_id: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Client ID"
                    />
                    <input
                        type="password"
                        value={settings.sso_client_secret || ''}
                        onChange={(e) => setSettings({ ...settings, sso_client_secret: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Client Secret"
                    />
                    <input
                        value={settings.sso_auth_url || ''}
                        onChange={(e) => setSettings({ ...settings, sso_auth_url: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Auth URL"
                    />
                    <input
                        value={settings.sso_token_url || ''}
                        onChange={(e) => setSettings({ ...settings, sso_token_url: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Token URL"
                    />
                    <input
                        value={settings.sso_userinfo_url || ''}
                        onChange={(e) => setSettings({ ...settings, sso_userinfo_url: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Userinfo URL"
                    />
                    <input
                        value={settings.sso_redirect_url || ''}
                        onChange={(e) => setSettings({ ...settings, sso_redirect_url: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Redirect URL"
                    />
                    <input
                        value={settings.sso_scopes || ''}
                        onChange={(e) => setSettings({ ...settings, sso_scopes: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Scopes (openid email profile)"
                    />
                    <input
                        value={settings.sso_allowed_domains || ''}
                        onChange={(e) => setSettings({ ...settings, sso_allowed_domains: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Allowed email domains (comma separated)"
                    />
                    <label className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={!!settings.sso_auto_create}
                            onChange={(e) => setSettings({ ...settings, sso_auto_create: e.target.checked })}
                        />
                        Auto-create users
                    </label>
                    <select
                        value={settings.sso_default_role || 'tenant-admin'}
                        onChange={(e) => setSettings({ ...settings, sso_default_role: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                    >
                        <option value="superadmin">Superadmin</option>
                        <option value="tenant-admin">Tenant admin</option>
                        <option value="editor">Editor</option>
                        <option value="writer">Writer</option>
                    </select>
                    <select
                        value={settings.sso_default_tenant_id || ''}
                        onChange={(e) => setSettings({ ...settings, sso_default_tenant_id: e.target.value ? Number(e.target.value) : null })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                    >
                        <option value="">Default tenant (optional)</option>
                        {tenants.map((tenant) => (
                            <option key={tenant.id} value={tenant.id}>{tenant.name}</option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center justify-between mb-4">
                    <div>
                        <h2 className="text-sm font-semibold text-gray-700">SAML Settings</h2>
                        <p className="text-xs text-gray-500">SAML IdP config (Google Workspace). ACS URL: {settings.saml_acs_url || 'auto'}</p>
                    </div>
                    <div className="flex items-center gap-3 text-xs">
                        <label className="flex items-center gap-2">
                            <span>Enable SAML</span>
                            <input
                                type="checkbox"
                                checked={!!settings.saml_enabled}
                                onChange={(e) => setSettings({ ...settings, saml_enabled: e.target.checked })}
                            />
                        </label>
                        <label className="flex items-center gap-2">
                            <span>Enforce SAML</span>
                            <input
                                type="checkbox"
                                checked={!!settings.saml_enforce}
                                onChange={(e) => setSettings({ ...settings, saml_enforce: e.target.checked })}
                            />
                        </label>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <input
                        value={settings.saml_provider_label || ''}
                        onChange={(e) => setSettings({ ...settings, saml_provider_label: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Provider label (Google Workspace)"
                    />
                    <input
                        value={settings.saml_idp_metadata_url || ''}
                        onChange={(e) => setSettings({ ...settings, saml_idp_metadata_url: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="IdP metadata URL"
                    />
                    <textarea
                        value={settings.saml_idp_metadata_xml || ''}
                        onChange={(e) => setSettings({ ...settings, saml_idp_metadata_xml: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2 md:col-span-2"
                        placeholder="IdP metadata XML (optional)"
                        rows={3}
                    />
                    <input
                        value={settings.saml_idp_entity_id || ''}
                        onChange={(e) => setSettings({ ...settings, saml_idp_entity_id: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="IdP Entity ID"
                    />
                    <input
                        value={settings.saml_idp_sso_url || ''}
                        onChange={(e) => setSettings({ ...settings, saml_idp_sso_url: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="IdP SSO URL"
                    />
                    <input
                        value={settings.saml_idp_slo_url || ''}
                        onChange={(e) => setSettings({ ...settings, saml_idp_slo_url: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="IdP SLO URL (optional)"
                    />
                    <textarea
                        value={settings.saml_idp_x509 || ''}
                        onChange={(e) => setSettings({ ...settings, saml_idp_x509: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2 md:col-span-2"
                        placeholder="IdP X509 cert (optional if metadata provided)"
                        rows={3}
                    />
                    <input
                        value={settings.saml_sp_entity_id || ''}
                        onChange={(e) => setSettings({ ...settings, saml_sp_entity_id: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="SP Entity ID"
                    />
                    <input
                        value={settings.saml_acs_url || ''}
                        onChange={(e) => setSettings({ ...settings, saml_acs_url: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="ACS URL"
                    />
                    <input
                        value={settings.saml_slo_url || ''}
                        onChange={(e) => setSettings({ ...settings, saml_slo_url: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="SLO URL"
                    />
                    <input
                        value={settings.saml_nameid_format || ''}
                        onChange={(e) => setSettings({ ...settings, saml_nameid_format: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="NameID format"
                    />
                    <input
                        value={settings.saml_attribute_email || ''}
                        onChange={(e) => setSettings({ ...settings, saml_attribute_email: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Email attribute (e.g. email)"
                    />
                    <input
                        value={settings.saml_attribute_name || ''}
                        onChange={(e) => setSettings({ ...settings, saml_attribute_name: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Name attribute (e.g. name)"
                    />
                    <input
                        value={settings.saml_attribute_groups || ''}
                        onChange={(e) => setSettings({ ...settings, saml_attribute_groups: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Groups attribute (optional)"
                    />
                    <input
                        value={settings.saml_allowed_domains || ''}
                        onChange={(e) => setSettings({ ...settings, saml_allowed_domains: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Allowed email domains (comma separated)"
                    />
                    <label className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={!!settings.saml_auto_create}
                            onChange={(e) => setSettings({ ...settings, saml_auto_create: e.target.checked })}
                        />
                        Auto-create users
                    </label>
                    <select
                        value={settings.saml_default_role || 'tenant-admin'}
                        onChange={(e) => setSettings({ ...settings, saml_default_role: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                    >
                        <option value="superadmin">Superadmin</option>
                        <option value="tenant-admin">Tenant admin</option>
                        <option value="editor">Editor</option>
                        <option value="writer">Writer</option>
                    </select>
                    <select
                        value={settings.saml_default_tenant_id || ''}
                        onChange={(e) => setSettings({ ...settings, saml_default_tenant_id: e.target.value ? Number(e.target.value) : null })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                    >
                        <option value="">Default tenant (optional)</option>
                        {tenants.map((tenant) => (
                            <option key={tenant.id} value={tenant.id}>{tenant.name}</option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <div className="flex items-center gap-2 mb-4 text-gray-700">
                        <Database className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Backups</h2>
                    </div>
                    <button
                        onClick={handleBackup}
                        disabled={backupRunning}
                        className="px-3 py-2 text-xs rounded-lg bg-blue-600 text-white"
                    >
                        {backupRunning ? 'Running backup...' : 'Run Backup Now'}
                    </button>
                    <div className="mt-4 space-y-2 text-xs">
                        {backups.length === 0 ? (
                            <p className="text-gray-500">No backups yet.</p>
                        ) : (
                            backups.map((backup) => (
                                <div key={backup.id} className="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                                    <div>
                                        <p className="font-semibold text-gray-700">{backup.status}</p>
                                        <p className="text-[11px] text-gray-500">
                                            {backup.started_at} • {backup.disk || 'local'}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {backup.path ? (
                                            <a
                                                href={`/api/admin/platform/backups/${backup.id}/download`}
                                                className="text-xs font-semibold text-blue-600 inline-flex items-center gap-1"
                                            >
                                                <Download className="w-3 h-3" />
                                                Download
                                            </a>
                                        ) : (
                                            <span className="text-[11px] text-gray-400">S3 only</span>
                                        )}
                                        <button
                                            onClick={() => handleRestore(backup.id)}
                                            className="text-xs font-semibold text-rose-600"
                                            disabled={restoringId === backup.id}
                                        >
                                            {restoringId === backup.id ? 'Restoring...' : 'Restore'}
                                        </button>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>

                <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <div className="flex items-center gap-2 mb-4 text-gray-700">
                        <HardDrive className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Plans & Limits</h2>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <input
                            value={planForm.name}
                            onChange={(e) => setPlanForm({ ...planForm, name: e.target.value })}
                            className="rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="Plan name"
                        />
                        <input
                            value={planForm.slug}
                            onChange={(e) => setPlanForm({ ...planForm, slug: e.target.value })}
                            className="rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="plan-slug"
                        />
                        <input
                            type="number"
                            value={planForm.price_cents}
                            onChange={(e) => setPlanForm({ ...planForm, price_cents: e.target.value })}
                            className="rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="Price (cents)"
                        />
                        <select
                            value={planForm.interval}
                            onChange={(e) => setPlanForm({ ...planForm, interval: e.target.value })}
                            className="rounded-lg border border-gray-200 px-3 py-2"
                        >
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="lifetime">Lifetime</option>
                        </select>
                        <input
                            type="number"
                            value={planForm.max_posts}
                            onChange={(e) => setPlanForm({ ...planForm, max_posts: e.target.value })}
                            className="rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="Max posts (0 = unlimited)"
                        />
                        <input
                            type="number"
                            value={planForm.max_users}
                            onChange={(e) => setPlanForm({ ...planForm, max_users: e.target.value })}
                            className="rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="Max users (0 = unlimited)"
                        />
                        <input
                            type="number"
                            value={planForm.rate_limit_per_minute}
                            onChange={(e) => setPlanForm({ ...planForm, rate_limit_per_minute: e.target.value })}
                            className="rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="Rate limit / min"
                        />
                        <input
                            type="number"
                            value={planForm.storage_gb}
                            onChange={(e) => setPlanForm({ ...planForm, storage_gb: e.target.value })}
                            className="rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="Storage GB (0 = unlimited)"
                        />
                        <button
                            onClick={createPlan}
                            className="rounded-lg bg-gray-900 text-white px-3 py-2"
                        >
                            Create plan
                        </button>
                    </div>

                    <div className="mt-4 text-xs space-y-2">
                        {plans.map((plan) => (
                            <div key={plan.id} className="border border-gray-100 rounded-lg px-3 py-2">
                                <div className="flex items-center justify-between">
                                    <span className="font-semibold">{plan.name}</span>
                                    <span className="text-gray-400">{plan.interval}</span>
                                </div>
                                <p className="text-gray-500">Limits: {JSON.stringify(plan.limits || {})}</p>
                            </div>
                        ))}
                    </div>

                    <div className="mt-4 text-xs">
                        <h3 className="font-semibold text-gray-700 mb-2">Assign Plan to Tenant</h3>
                        <div className="flex gap-2">
                            <select
                                value={assignForm.tenantId}
                                onChange={(e) => setAssignForm({ ...assignForm, tenantId: e.target.value })}
                                className="rounded-lg border border-gray-200 px-3 py-2"
                            >
                                <option value="">Select tenant</option>
                                {tenants.map((tenant) => (
                                    <option key={tenant.id} value={tenant.id}>{tenant.name}</option>
                                ))}
                            </select>
                            <select
                                value={assignForm.planId}
                                onChange={(e) => setAssignForm({ ...assignForm, planId: e.target.value })}
                                className="rounded-lg border border-gray-200 px-3 py-2"
                            >
                                <option value="">Select plan</option>
                                {plans.map((plan) => (
                                    <option key={plan.id} value={plan.id}>{plan.name}</option>
                                ))}
                            </select>
                            <button
                                onClick={assignPlan}
                                className="rounded-lg bg-blue-600 text-white px-3 py-2"
                            >
                                Assign
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center gap-2 text-gray-700">
                        <ClipboardList className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Audit Logs</h2>
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            value={auditSearch}
                            onChange={(e) => setAuditSearch(e.target.value)}
                            className="rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="Search logs"
                        />
                        <button
                            onClick={refreshAuditLogs}
                            className="px-3 py-2 text-xs rounded-lg border border-gray-200"
                        >
                            Search
                        </button>
                        <input
                            type="number"
                            value={auditExportDays}
                            onChange={(e) => setAuditExportDays(Number(e.target.value))}
                            className="w-24 rounded-lg border border-gray-200 px-3 py-2 text-xs"
                            placeholder="Days"
                            title="Export logs from last N days"
                        />
                        <button
                            onClick={runAuditExport}
                            disabled={auditExportRunning}
                            className="px-3 py-2 text-xs rounded-lg bg-gray-900 text-white"
                        >
                            {auditExportRunning ? 'Exporting...' : 'Export CSV'}
                        </button>
                    </div>
                </div>
                <div className="flex items-center gap-3 text-xs text-gray-500 mb-4">
                    <span>Retention days</span>
                    <input
                        type="number"
                        value={settings.audit_export_retention_days || 30}
                        onChange={(e) => setSettings({ ...settings, audit_export_retention_days: Number(e.target.value) })}
                        className="w-24 rounded-lg border border-gray-200 px-3 py-2 text-xs"
                    />
                    <button
                        onClick={saveSettings}
                        disabled={savingSettings}
                        className="px-3 py-2 text-xs rounded-lg border border-gray-200"
                    >
                        {savingSettings ? 'Saving...' : 'Save'}
                    </button>
                </div>
                <div className="space-y-2 text-xs">
                    {auditLogs.length === 0 ? (
                        <p className="text-gray-500">No audit logs yet.</p>
                    ) : (
                        auditLogs.map((log) => (
                            <div key={log.id} className="border border-gray-100 rounded-lg px-3 py-2">
                                <div className="flex items-center justify-between">
                                    <span className="font-semibold text-gray-700">{log.action}</span>
                                    <span className="text-gray-400">{log.created_at}</span>
                                </div>
                                <p className="text-gray-500">{log.user?.email || 'System'} • {log.route}</p>
                            </div>
                        ))
                    )}
                </div>
                <div className="mt-4">
                    <h3 className="text-xs font-semibold text-gray-600 mb-2">Audit Exports</h3>
                    {auditExports.length === 0 ? (
                        <p className="text-xs text-gray-500">No exports yet.</p>
                    ) : (
                        <div className="space-y-2 text-xs">
                            {auditExports.map((exportItem) => (
                                <div key={exportItem.id} className="border border-gray-100 rounded-lg px-3 py-2 flex items-center justify-between">
                                    <div>
                                        <p className="font-semibold text-gray-700">Export #{exportItem.id}</p>
                                        <p className="text-[11px] text-gray-400">{exportItem.created_at} • {exportItem.total_rows} rows</p>
                                    </div>
                                    <a
                                        href={api.admin.downloadAuditExportUrl(exportItem.id)}
                                        className="text-xs text-blue-600"
                                    >
                                        Download
                                    </a>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center gap-2 text-gray-700 mb-4">
                    <Activity className="w-4 h-4" />
                    <h2 className="text-sm font-semibold">Alerts</h2>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-600">
                    <div>
                        <h3 className="font-semibold text-gray-700 mb-2">SSL Expiring</h3>
                        {alerts.ssl_expiring?.length ? (
                            <div className="space-y-2">
                                {alerts.ssl_expiring.map((item, idx) => (
                                    <div key={idx} className="border border-gray-100 rounded-lg px-3 py-2">
                                        <p>{item.domain}</p>
                                        <p className="text-[11px] text-gray-500">{item.expires_at}</p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-gray-500">No expiring certificates.</p>
                        )}
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-700 mb-2">HTTP/3 Issues</h3>
                        {alerts.http3_issues?.length ? (
                            <div className="space-y-2">
                                {alerts.http3_issues.map((item, idx) => (
                                    <div key={idx} className="border border-gray-100 rounded-lg px-3 py-2">
                                        <p>{item.hostname}</p>
                                        <p className="text-[11px] text-gray-500">{item.http3_status}</p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-gray-500">No HTTP/3 issues.</p>
                        )}
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-700 mb-2">Storage Overages</h3>
                        {alerts.storage_overages?.length ? (
                            <div className="space-y-2">
                                {alerts.storage_overages.map((item, idx) => (
                                    <div key={idx} className="border border-gray-100 rounded-lg px-3 py-2">
                                        <p>{item.tenant}</p>
                                        <p className="text-[11px] text-gray-500">
                                            {Math.round(item.usage_bytes / 1024 / 1024)} MB / {Math.round(item.limit_bytes / 1024 / 1024)} MB
                                        </p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-gray-500">No storage overages.</p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
