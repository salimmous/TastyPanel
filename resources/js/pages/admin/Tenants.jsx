import { useEffect, useState } from 'react';
import { api } from '../../services/api';
import { Globe, Plus, RefreshCw, Shield, Code, X, Zap, Server, AlertTriangle, RotateCw } from 'lucide-react';
import { isSuperadmin } from '../../utils/permissions';

const emptyForm = {
    name: '',
    slug: '',
    domain: '',
    themeId: '',
    blueprintId: '',
    brandName: '',
    tagline: '',
    primaryColor: '#1f2937',
    secondaryColor: '#f59e0b',
};

const emptyQuotaProfile = {
    max_monthly_requests: '',
    max_storage_mb: '',
    max_db_size_mb: '',
    max_cpu_percent: '',
    max_memory_mb: '',
    max_worker_processes: '',
    quota_alert_threshold_percent: 80,
};

export default function Tenants() {
    const [themes, setThemes] = useState([]);
    const [blueprints, setBlueprints] = useState([]);
    const [tenants, setTenants] = useState([]);
    const [form, setForm] = useState(emptyForm);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [currentUser, setCurrentUser] = useState(null);
    const [editorOpen, setEditorOpen] = useState(false);
    const [editorDomain, setEditorDomain] = useState(null);
    const [editorConfig, setEditorConfig] = useState('');
    const [editorDefaultConfig, setEditorDefaultConfig] = useState('');
    const [editorIsCustom, setEditorIsCustom] = useState(false);
    const [editorLoading, setEditorLoading] = useState(false);
    const [editorSaving, setEditorSaving] = useState(false);
    const [editorTesting, setEditorTesting] = useState(false);
    const [editorMessage, setEditorMessage] = useState('');
    const [editorError, setEditorError] = useState('');
    const [editorTestOutput, setEditorTestOutput] = useState('');
    const [editorVersions, setEditorVersions] = useState([]);
    const [editorVersionsLoading, setEditorVersionsLoading] = useState(false);
    const [http3LoadingId, setHttp3LoadingId] = useState(null);
    const [http3CheckLoadingId, setHttp3CheckLoadingId] = useState(null);
    const [instanceLoadingId, setInstanceLoadingId] = useState(null);
    const [expandedInstanceId, setExpandedInstanceId] = useState(null);
    const [orchestrationLoadingId, setOrchestrationLoadingId] = useState(null);
    const [orchestrationCheckingId, setOrchestrationCheckingId] = useState(null);
    const [orchestrationStatus, setOrchestrationStatus] = useState({});
    const [orchestrationMessage, setOrchestrationMessage] = useState({});
    const [accessLoadingId, setAccessLoadingId] = useState(null);
    const [accessKeyLoadingId, setAccessKeyLoadingId] = useState(null);
    const [secretSyncLoadingId, setSecretSyncLoadingId] = useState(null);
    const [tenantAccessInfo, setTenantAccessInfo] = useState({});
    const [tenantSecurityProfiles, setTenantSecurityProfiles] = useState({});
    const [tenantQuotaUsage, setTenantQuotaUsage] = useState({});
    const [tenantMailSettings, setTenantMailSettings] = useState({});
    const [tenantMailboxes, setTenantMailboxes] = useState({});
    const [tenantMailEvents, setTenantMailEvents] = useState({});
    const [tenantMailSummary, setTenantMailSummary] = useState({});
    const [mailLoadingId, setMailLoadingId] = useState(null);
    const [mailSavingId, setMailSavingId] = useState(null);
    const [securityProfileLoadingId, setSecurityProfileLoadingId] = useState(null);
    const [securityProfileSavingId, setSecurityProfileSavingId] = useState(null);
    const [provisioningRetryId, setProvisioningRetryId] = useState(null);
    const [provisioningRollbackId, setProvisioningRollbackId] = useState(null);
    const [cloneOpen, setCloneOpen] = useState(false);
    const [cloneTenant, setCloneTenant] = useState(null);
    const [cloneSaving, setCloneSaving] = useState(false);
    const [cloneError, setCloneError] = useState('');
    const [cloneForm, setCloneForm] = useState({
        name: '',
        slug: '',
        domain: '',
        themeId: '',
        copySettings: true,
        copyStaging: true,
        fullClone: true,
    });
    const [opsOpen, setOpsOpen] = useState(false);
    const [opsTenant, setOpsTenant] = useState(null);
    const [opsBackups, setOpsBackups] = useState([]);
    const [opsBackupLoading, setOpsBackupLoading] = useState(false);
    const [opsBackupRunning, setOpsBackupRunning] = useState(false);
    const [opsBackupSaving, setOpsBackupSaving] = useState(false);
    const [opsBackupSettings, setOpsBackupSettings] = useState({
        backup_enabled: true,
        backup_interval_hours: '',
        backup_retention_days: '',
        backup_s3_enabled: false,
        backup_keep_local: true,
        backup_s3_prefix: '',
    });
    const [opsQueueStats, setOpsQueueStats] = useState(null);
    const [opsQueueAction, setOpsQueueAction] = useState('');
    const [opsLogType, setOpsLogType] = useState('laravel');
    const [opsLogDomainId, setOpsLogDomainId] = useState('');
    const [opsLogLines, setOpsLogLines] = useState('');
    const [opsLogCount, setOpsLogCount] = useState(200);
    const [opsLogLoading, setOpsLogLoading] = useState(false);

    useEffect(() => {
        loadData();
        loadUser();
    }, []);

    useEffect(() => {
        const hasActiveProvisioning = tenants.some((tenant) =>
            ['queued', 'running'].includes(tenant.latest_provisioning_job?.status)
        );
        if (!hasActiveProvisioning) {
            return undefined;
        }

        const timeout = setTimeout(() => {
            loadData();
        }, 5000);

        return () => clearTimeout(timeout);
    }, [tenants]);

    const loadUser = async () => {
        try {
            const response = await api.admin.getUser();
            setCurrentUser(response?.user || null);
        } catch {
            setCurrentUser(null);
        }
    };

    const loadData = async () => {
        setLoading(true);
        try {
            const [themesRes, tenantsRes, blueprintsRes] = await Promise.all([
                api.admin.getThemes(),
                api.admin.getTenants(),
                api.admin.getTenantBlueprints(),
            ]);
            setThemes(themesRes?.data || []);
            setTenants(tenantsRes?.data || []);
            setBlueprints(blueprintsRes?.data || []);
        } finally {
            setLoading(false);
        }
    };

    const formatBytes = (value) => {
        if (!value && value !== 0) return '—';
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let index = 0;
        let size = Number(value);
        while (size >= 1024 && index < units.length - 1) {
            size /= 1024;
            index += 1;
        }
        return `${size.toFixed(size >= 10 || index === 0 ? 0 : 1)} ${units[index]}`;
    };

    const handleChange = (field) => (event) => {
        setForm((prev) => ({ ...prev, [field]: event.target.value }));
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        if (!form.name || !form.domain) {
            return;
        }
        setSaving(true);
        try {
            await api.admin.createTenant({
                name: form.name,
                slug: form.slug || undefined,
                domain: form.domain,
                theme_id: form.themeId ? Number(form.themeId) : null,
                blueprint_id: form.blueprintId || undefined,
                settings: {
                    brand_name: form.brandName || form.name,
                    tagline: form.tagline,
                    primary_color: form.primaryColor,
                    secondary_color: form.secondaryColor,
                },
            });
            setForm(emptyForm);
            await loadData();
        } finally {
            setSaving(false);
        }
    };

    const provisionDomain = async (domainId) => {
        await api.admin.provisionDomain(domainId);
        await loadData();
    };

    const requestSsl = async (domainId) => {
        await api.admin.requestDomainSsl(domainId);
        await loadData();
    };

    const requestNginx = async (domainId) => {
        await api.admin.requestDomainNginx(domainId);
        await loadData();
    };

    const provisionInstance = async (tenantId) => {
        setInstanceLoadingId(tenantId);
        try {
            await api.admin.provisionTenantInstance(tenantId);
            await loadData();
        } finally {
            setInstanceLoadingId(null);
        }
    };

    const toggleInstanceLog = (tenantId) => {
        setExpandedInstanceId((prev) => {
            const next = prev === tenantId ? null : tenantId;
            if (next) {
                fetchOrchestrationStatus(tenantId);
                fetchTenantAccessInfo(tenantId);
                fetchTenantSecurityProfile(tenantId);
                fetchTenantMailState(tenantId);
            }
            return next;
        });
    };

    const fetchOrchestrationStatus = async (tenantId) => {
        setOrchestrationCheckingId(tenantId);
        try {
            const response = await api.admin.getTenantOrchestrationStatus(tenantId);
            if (response?.data) {
                setOrchestrationStatus((prev) => ({ ...prev, [tenantId]: response.data }));
            }
        } finally {
            setOrchestrationCheckingId(null);
        }
    };

    const runOrchestration = async (tenantId, action) => {
        setOrchestrationLoadingId(tenantId);
        setOrchestrationMessage((prev) => ({ ...prev, [tenantId]: '' }));
        try {
            const response = await api.admin.runTenantOrchestration(tenantId, action);
            if (response?.status) {
                setOrchestrationStatus((prev) => ({ ...prev, [tenantId]: response.status }));
            } else {
                await fetchOrchestrationStatus(tenantId);
            }
            if (response?.message) {
                setOrchestrationMessage((prev) => ({ ...prev, [tenantId]: response.message }));
            }
            await loadData();
        } finally {
            setOrchestrationLoadingId(null);
        }
    };

    const fetchTenantAccessInfo = async (tenantId) => {
        try {
            const response = await api.admin.getTenantAccessInfo(tenantId);
            if (response?.data) {
                setTenantAccessInfo((prev) => ({ ...prev, [tenantId]: response.data }));
            }
        } catch {
            // Ignore access info fetch errors in UI.
        }
    };

    const normalizeQuotaValue = (value, fallback = '') => {
        if (value === null || value === undefined || value === '') {
            return fallback;
        }
        const numeric = Number(value);
        if (!Number.isFinite(numeric) || numeric < 0) {
            return fallback;
        }
        return Math.floor(numeric);
    };

    const fetchTenantSecurityProfile = async (tenantId) => {
        setSecurityProfileLoadingId(tenantId);
        try {
            const response = await api.admin.getTenantSecurityProfile(tenantId);
            const data = response?.data || {};
            const limits = response?.quota?.limits || {};
            const usage = response?.quota?.usage || {};

            setTenantSecurityProfiles((prev) => ({
                ...prev,
                [tenantId]: {
                    ...emptyQuotaProfile,
                    max_monthly_requests: normalizeQuotaValue(data.max_monthly_requests ?? limits.max_monthly_requests),
                    max_storage_mb: normalizeQuotaValue(data.max_storage_mb ?? limits.max_storage_mb),
                    max_db_size_mb: normalizeQuotaValue(data.max_db_size_mb ?? limits.max_db_size_mb),
                    max_cpu_percent: normalizeQuotaValue(data.max_cpu_percent ?? limits.max_cpu_percent),
                    max_memory_mb: normalizeQuotaValue(data.max_memory_mb ?? limits.max_memory_mb),
                    max_worker_processes: normalizeQuotaValue(data.max_worker_processes ?? limits.max_worker_processes),
                    quota_alert_threshold_percent: normalizeQuotaValue(data.quota_alert_threshold_percent ?? limits.alert_threshold_percent, 80),
                },
            }));

            setTenantQuotaUsage((prev) => ({
                ...prev,
                [tenantId]: usage,
            }));
        } catch {
            // Ignore profile fetch failures in UI.
        } finally {
            setSecurityProfileLoadingId((current) => (current === tenantId ? null : current));
        }
    };

    const updateTenantQuotaField = (tenantId, field, value) => {
        setTenantSecurityProfiles((prev) => ({
            ...prev,
            [tenantId]: {
                ...(prev[tenantId] || { ...emptyQuotaProfile }),
                [field]: value,
            },
        }));
    };

    const saveTenantSecurityProfile = async (tenant) => {
        const profile = tenantSecurityProfiles[tenant.id] || emptyQuotaProfile;
        setSecurityProfileSavingId(tenant.id);
        try {
            const payload = {
                max_monthly_requests: normalizeQuotaValue(profile.max_monthly_requests) || null,
                max_storage_mb: normalizeQuotaValue(profile.max_storage_mb) || null,
                max_db_size_mb: normalizeQuotaValue(profile.max_db_size_mb) || null,
                max_cpu_percent: normalizeQuotaValue(profile.max_cpu_percent) || null,
                max_memory_mb: normalizeQuotaValue(profile.max_memory_mb) || null,
                max_worker_processes: normalizeQuotaValue(profile.max_worker_processes) || null,
                quota_alert_threshold_percent: normalizeQuotaValue(profile.quota_alert_threshold_percent, 80) || 80,
            };

            await api.admin.updateTenantSecurityProfile(tenant.id, payload);
            await fetchTenantSecurityProfile(tenant.id);
        } finally {
            setSecurityProfileSavingId(null);
        }
    };

    const fetchTenantMailState = async (tenantId) => {
        setMailLoadingId(tenantId);
        try {
            const [settingsRes, mailboxesRes, eventsRes] = await Promise.all([
                api.admin.getTenantMailSettings(tenantId),
                api.admin.listTenantMailboxes(tenantId),
                api.admin.listTenantMailEvents(tenantId, 30),
            ]);

            setTenantMailSettings((prev) => ({
                ...prev,
                [tenantId]: settingsRes?.data || {},
            }));
            setTenantMailboxes((prev) => ({
                ...prev,
                [tenantId]: mailboxesRes?.data || [],
            }));
            setTenantMailEvents((prev) => ({
                ...prev,
                [tenantId]: eventsRes?.data || [],
            }));
            setTenantMailSummary((prev) => ({
                ...prev,
                [tenantId]: eventsRes?.summary || {},
            }));
        } catch {
            // Ignore mail fetch failures in UI.
        } finally {
            setMailLoadingId((current) => (current === tenantId ? null : current));
        }
    };

    const configureTenantMail = async (tenant) => {
        const current = tenantMailSettings[tenant.id] || {};
        const defaultDomain = (tenant.domains || []).find((domain) => domain.is_primary)?.hostname || 'example.com';
        const host = window.prompt('SMTP host:', current.mail_host || '127.0.0.1');
        if (!host) return;
        const portInput = window.prompt('SMTP port:', String(current.mail_port || 587));
        if (!portInput) return;
        const username = window.prompt('SMTP username (optional):', current.mail_username || '');
        const password = window.prompt('SMTP password (leave empty to keep current):', '');
        const encryption = window.prompt('SMTP encryption (tls/ssl/none):', current.mail_encryption || 'tls');
        const fromAddress = window.prompt('From email address:', current.mail_from_address || `noreply@${defaultDomain}`);
        if (!fromAddress) return;
        const fromName = window.prompt('From name:', current.mail_from_name || tenant.name);
        if (!fromName) return;
        const dailyLimitInput = window.prompt('Daily send limit:', String(current.mail_daily_limit || 500));
        if (!dailyLimitInput) return;
        const perMinuteInput = window.prompt('Per-minute send limit:', String(current.mail_per_minute_limit || 30));
        if (!perMinuteInput) return;

        setMailSavingId(tenant.id);
        try {
            await api.admin.updateTenantMailSettings(tenant.id, {
                mail_driver: 'smtp',
                mail_host: host.trim(),
                mail_port: Number(portInput),
                mail_username: (username || '').trim(),
                mail_password: (password || '').trim(),
                mail_encryption: (encryption || 'tls').trim().toLowerCase(),
                mail_from_address: fromAddress.trim(),
                mail_from_name: fromName.trim(),
                mail_local_enabled: true,
                mail_provider: 'local',
                mail_daily_limit: Number(dailyLimitInput),
                mail_per_minute_limit: Number(perMinuteInput),
                mail_configured: true,
            });
            await fetchTenantMailState(tenant.id);
        } finally {
            setMailSavingId(null);
        }
    };

    const testTenantMail = async (tenant) => {
        const toEmail = window.prompt('Send test email to:');
        if (!toEmail) return;
        setMailSavingId(tenant.id);
        try {
            const response = await api.admin.testTenantMail(tenant.id, toEmail.trim());
            if (response?.success) {
                window.alert('Test email sent successfully.');
            } else {
                window.alert(`Mail test failed:\n${response?.data?.error || response?.data?.reason || response?.message || 'Unknown error'}`);
            }
            await fetchTenantMailState(tenant.id);
        } finally {
            setMailSavingId(null);
        }
    };

    const createTenantMailbox = async (tenant) => {
        const defaultDomain = (tenant.domains || []).find((domain) => domain.is_primary)?.hostname || 'example.com';
        const email = window.prompt('Mailbox email:', `info@${defaultDomain}`);
        if (!email) return;
        const quotaInput = window.prompt('Mailbox quota (MB):', '1024');
        if (!quotaInput) return;
        const password = window.prompt('Mailbox password (empty = auto generate):', '');

        setMailSavingId(tenant.id);
        try {
            const response = await api.admin.createTenantMailbox(tenant.id, {
                email: email.trim(),
                quota_mb: Number(quotaInput),
                password: (password || '').trim() || undefined,
            });
            if (response?.data?.password) {
                window.alert(`Mailbox created: ${email}\nPassword: ${response.data.password}`);
            }
            await fetchTenantMailState(tenant.id);
        } finally {
            setMailSavingId(null);
        }
    };

    const resetTenantMailboxPassword = async (tenant, mailbox) => {
        if (!window.confirm(`Reset password for ${mailbox.email}?`)) return;
        setMailSavingId(tenant.id);
        try {
            const response = await api.admin.resetTenantMailboxPassword(tenant.id, mailbox.id);
            if (response?.data?.password) {
                window.alert(`New password for ${mailbox.email}: ${response.data.password}`);
            }
            await fetchTenantMailState(tenant.id);
        } finally {
            setMailSavingId(null);
        }
    };

    const refreshTenantMailboxUsage = async (tenant, mailbox) => {
        setMailSavingId(tenant.id);
        try {
            await api.admin.refreshTenantMailboxUsage(tenant.id, mailbox.id);
            await fetchTenantMailState(tenant.id);
        } finally {
            setMailSavingId(null);
        }
    };

    const deleteTenantMailbox = async (tenant, mailbox) => {
        if (!window.confirm(`Delete mailbox ${mailbox.email}?`)) return;
        setMailSavingId(tenant.id);
        try {
            await api.admin.deleteTenantMailbox(tenant.id, mailbox.id);
            await fetchTenantMailState(tenant.id);
        } finally {
            setMailSavingId(null);
        }
    };

    const provisionTenantAccess = async (tenant) => {
        setAccessLoadingId(tenant.id);
        try {
            const response = await api.admin.provisionTenantAccess(tenant.id);
            const info = response?.data || {};
            if (info.password) {
                window.alert(`SSH user: ${info.user}\nTemporary password: ${info.password}\nPort: ${info.port}`);
            } else if (info.user) {
                window.alert(`SSH user: ${info.user}\nPassword auth disabled by policy.\nUse SSH key login.`);
            }
            await Promise.all([fetchTenantAccessInfo(tenant.id), loadData()]);
        } finally {
            setAccessLoadingId(null);
        }
    };

    const rotateTenantAccessPassword = async (tenant) => {
        setAccessLoadingId(tenant.id);
        try {
            const response = await api.admin.rotateTenantAccessPassword(tenant.id);
            const info = response?.data || {};
            if (info.password) {
                window.alert(`SSH user: ${info.user}\nNew temporary password: ${info.password}\nPort: ${info.port}`);
            } else if (info.user) {
                window.alert(`Password rotation skipped for ${info.user} (password auth disabled).`);
            }
            await Promise.all([fetchTenantAccessInfo(tenant.id), loadData()]);
        } finally {
            setAccessLoadingId(null);
        }
    };

    const installTenantAccessKey = async (tenant) => {
        const key = window.prompt('Paste SSH public key (ssh-ed25519 ... or ssh-rsa ...):');
        if (!key) return;
        setAccessKeyLoadingId(tenant.id);
        try {
            await api.admin.installTenantAccessKey(tenant.id, key);
            await Promise.all([fetchTenantAccessInfo(tenant.id), loadData()]);
        } finally {
            setAccessKeyLoadingId(null);
        }
    };

    const saveTenantSecretAndSync = async (tenant) => {
        const secretKey = window.prompt('Secret key (example: openai.api_key):');
        if (!secretKey) return;
        const secretValue = window.prompt('Secret value:');
        if (!secretValue) return;
        const defaultEnvKey = secretKey.toUpperCase().replace(/[^A-Z0-9]+/g, '_').replace(/^_+|_+$/g, '') || 'SECRET_KEY';
        const envKey = window.prompt('ENV key to write in tenant .env:', defaultEnvKey);
        if (!envKey) return;

        setSecretSyncLoadingId(tenant.id);
        try {
            const response = await api.admin.storeTenantSecret(tenant.id, {
                secret_key: secretKey.trim(),
                secret_value: secretValue,
                sync_to_env: true,
                env_key: envKey.trim(),
            });
            const sync = response?.data?.sync;
            if (sync?.success === false) {
                window.alert(`Secret saved but .env sync failed:\n${sync.output || 'Unknown error'}`);
            } else {
                window.alert(`Secret synced to .env key: ${sync?.env_key || envKey}`);
            }
        } finally {
            setSecretSyncLoadingId(null);
        }
    };

    const syncExistingTenantSecret = async (tenant) => {
        const secretKey = window.prompt('Existing secret key to sync (example: openai.api_key):');
        if (!secretKey) return;
        const defaultEnvKey = secretKey.toUpperCase().replace(/[^A-Z0-9]+/g, '_').replace(/^_+|_+$/g, '') || 'SECRET_KEY';
        const envKey = window.prompt('ENV key to write in tenant .env:', defaultEnvKey);
        if (!envKey) return;

        setSecretSyncLoadingId(tenant.id);
        try {
            const response = await api.admin.syncTenantSecretToEnv(tenant.id, secretKey.trim(), envKey.trim());
            if (response?.success === false) {
                window.alert(`Sync failed:\n${response?.data?.output || response?.message || 'Unknown error'}`);
            } else {
                window.alert(`Secret synced to .env key: ${response?.data?.env_key || envKey}`);
            }
        } finally {
            setSecretSyncLoadingId(null);
        }
    };

    const removeTenantEnvKey = async (tenant) => {
        const envKey = window.prompt('ENV key to remove from tenant .env (example: OPENAI_API_KEY):');
        if (!envKey) return;
        if (!window.confirm(`Remove ${envKey} from tenant .env?`)) return;

        setSecretSyncLoadingId(tenant.id);
        try {
            const response = await api.admin.removeTenantEnvKey(tenant.id, envKey.trim(), 'DELETE_ENV_KEY');
            if (response?.success === false) {
                window.alert(`Remove failed:\n${response?.data?.output || response?.message || 'Unknown error'}`);
            } else {
                window.alert(`Removed ${envKey} from tenant .env`);
            }
        } finally {
            setSecretSyncLoadingId(null);
        }
    };

    const retryProvisioning = async (tenant) => {
        const primaryDomain = (tenant.domains || []).find((item) => item.is_primary && item.environment === 'production')
            || tenant.domains?.[0]
            || null;
        setProvisioningRetryId(tenant.id);
        try {
            await api.admin.retryTenantProvisioning(tenant.id, primaryDomain?.id || null);
            await loadData();
        } finally {
            setProvisioningRetryId(null);
        }
    };

    const rollbackProvisioning = async (tenant) => {
        const primaryDomain = (tenant.domains || []).find((item) => item.is_primary && item.environment === 'production')
            || tenant.domains?.[0]
            || null;
        if (!window.confirm('Rollback provisioning for this site? This removes DNS record and Nginx vhost.')) {
            return;
        }
        setProvisioningRollbackId(tenant.id);
        try {
            await api.admin.rollbackTenantProvisioning(tenant.id, primaryDomain?.id || null);
            await loadData();
        } finally {
            setProvisioningRollbackId(null);
        }
    };

    const provisioningBadgeClass = (status) => {
        if (status === 'done') return 'bg-emerald-100 text-emerald-700';
        if (status === 'failed') return 'bg-rose-100 text-rose-700';
        if (status === 'rolled_back') return 'bg-slate-100 text-slate-700';
        return 'bg-amber-100 text-amber-700';
    };

    const openCloneModal = (tenant) => {
        setCloneTenant(tenant);
        setCloneForm({
            name: `Copy of ${tenant.name}`,
            slug: '',
            domain: '',
            themeId: tenant.theme_id ? String(tenant.theme_id) : '',
            copySettings: true,
            copyStaging: true,
            fullClone: true,
        });
        setCloneError('');
        setCloneOpen(true);
    };

    const closeCloneModal = () => {
        setCloneOpen(false);
        setCloneTenant(null);
        setCloneError('');
    };

    const submitClone = async (event) => {
        event.preventDefault();
        if (!cloneTenant) return;
        if (!cloneForm.name || !cloneForm.domain) {
            setCloneError('Name and domain are required.');
            return;
        }
        setCloneSaving(true);
        setCloneError('');
        try {
            await api.admin.cloneTenant(cloneTenant.id, {
                name: cloneForm.name,
                slug: cloneForm.slug || undefined,
                domain: cloneForm.domain,
                theme_id: cloneForm.themeId ? Number(cloneForm.themeId) : null,
                copy_settings: cloneForm.copySettings,
                copy_staging: cloneForm.copyStaging,
                full_clone: cloneForm.fullClone,
            });
            setCloneOpen(false);
            await loadData();
        } catch (err) {
            setCloneError('Failed to clone tenant.');
        } finally {
            setCloneSaving(false);
        }
    };

    const openOpsModal = async (tenant) => {
        setOpsTenant(tenant);
        setOpsOpen(true);
        setOpsBackupSettings({
            backup_enabled: tenant.backup_enabled ?? true,
            backup_interval_hours: tenant.backup_interval_hours ?? '',
            backup_retention_days: tenant.backup_retention_days ?? '',
            backup_s3_enabled: tenant.backup_s3_enabled ?? false,
            backup_keep_local: tenant.backup_keep_local ?? true,
            backup_s3_prefix: tenant.backup_s3_prefix ?? '',
        });
        const firstDomain = tenant.domains?.[0];
        if (firstDomain) {
            setOpsLogDomainId(String(firstDomain.id));
        }
        await Promise.all([loadOpsBackups(tenant.id), loadOpsQueue(tenant.id)]);
    };

    const closeOpsModal = () => {
        setOpsOpen(false);
        setOpsTenant(null);
        setOpsLogLines('');
        setOpsQueueStats(null);
    };

    const loadOpsBackups = async (tenantId) => {
        setOpsBackupLoading(true);
        try {
            const res = await api.admin.getTenantBackups(tenantId);
            setOpsBackups(res?.data || []);
        } finally {
            setOpsBackupLoading(false);
        }
    };

    const runOpsBackup = async () => {
        if (!opsTenant) return;
        setOpsBackupRunning(true);
        try {
            await api.admin.createTenantBackup(opsTenant.id);
            await loadOpsBackups(opsTenant.id);
        } finally {
            setOpsBackupRunning(false);
        }
    };

    const saveOpsBackupSettings = async () => {
        if (!opsTenant) return;
        setOpsBackupSaving(true);
        try {
            const payload = {
                ...opsBackupSettings,
                backup_interval_hours:
                    opsBackupSettings.backup_interval_hours === ''
                        ? null
                        : Number(opsBackupSettings.backup_interval_hours),
                backup_retention_days:
                    opsBackupSettings.backup_retention_days === ''
                        ? null
                        : Number(opsBackupSettings.backup_retention_days),
            };
            const res = await api.admin.updateTenantBackupSettings(opsTenant.id, payload);
            const updated = res?.data;
            if (updated) {
                setOpsTenant(updated);
                setOpsBackupSettings({
                    backup_enabled: updated.backup_enabled ?? true,
                    backup_interval_hours: updated.backup_interval_hours ?? '',
                    backup_retention_days: updated.backup_retention_days ?? '',
                    backup_s3_enabled: updated.backup_s3_enabled ?? false,
                    backup_keep_local: updated.backup_keep_local ?? true,
                    backup_s3_prefix: updated.backup_s3_prefix ?? '',
                });
            }
            await loadData();
        } finally {
            setOpsBackupSaving(false);
        }
    };

    const downloadOpsBackup = (backupId) => {
        if (!opsTenant) return;
        const url = api.admin.downloadTenantBackupUrl(opsTenant.id, backupId);
        window.open(url, '_blank', 'noopener');
    };

    const restoreOpsBackup = async (backupId) => {
        if (!opsTenant) return;
        const confirm = window.confirm('Restore this backup? This will overwrite the tenant database and files.');
        if (!confirm) return;
        await api.admin.restoreTenantBackup(opsTenant.id, backupId, true);
        await loadOpsBackups(opsTenant.id);
    };

    const loadOpsQueue = async (tenantId) => {
        const res = await api.admin.getTenantQueue(tenantId);
        setOpsQueueStats(res?.data || null);
    };

    const runQueueAction = async (action) => {
        if (!opsTenant) return;
        setOpsQueueAction(action);
        try {
            if (action === 'restart') {
                await api.admin.restartTenantQueue(opsTenant.id);
            }
            if (action === 'flush') {
                await api.admin.flushTenantQueue(opsTenant.id);
            }
            if (action === 'retry') {
                await api.admin.retryTenantQueue(opsTenant.id);
            }
            await loadOpsQueue(opsTenant.id);
        } finally {
            setOpsQueueAction('');
        }
    };

    const loadOpsLogs = async () => {
        if (!opsTenant) return;
        if (opsLogType.startsWith('domain') && !opsLogDomainId) {
            setOpsLogLines('Select a domain first.');
            return;
        }
        setOpsLogLoading(true);
        try {
            const params = { type: opsLogType, lines: opsLogCount };
            if (opsLogType.startsWith('domain') && opsLogDomainId) {
                params.domain_id = opsLogDomainId;
            }
            const res = await api.admin.tailTenantLogs(opsTenant.id, params);
            setOpsLogLines(res?.lines || '');
        } finally {
            setOpsLogLoading(false);
        }
    };

    const archiveTenant = async (tenantId) => {
        if (!window.confirm('Archive this tenant?')) {
            return;
        }
        await api.admin.archiveTenant(tenantId);
        await loadData();
    };

    const unarchiveTenant = async (tenantId) => {
        await api.admin.unarchiveTenant(tenantId);
        await loadData();
    };

    const deleteTenant = async (tenant) => {
        if (!window.confirm('Delete this tenant? This cannot be undone.')) {
            return;
        }
        const confirmText = window.prompt('Type DELETE to confirm.');
        if (confirmText !== 'DELETE') {
            return;
        }
        await api.admin.deleteTenant(tenant.id);
        await loadData();
    };

    const loadEditorData = async (domainId) => {
        setEditorLoading(true);
        setEditorVersionsLoading(true);
        setEditorError('');
        setEditorMessage('');
        setEditorTestOutput('');
        try {
            const [configResponse, versionsResponse] = await Promise.all([
                api.admin.getDomainNginxConfig(domainId),
                api.admin.getDomainNginxVersions(domainId),
            ]);
            const data = configResponse?.data || {};
            setEditorConfig(data.effective_config || '');
            setEditorDefaultConfig(data.default_config || '');
            setEditorIsCustom(Boolean(data.is_custom));
            setEditorVersions(versionsResponse?.data || []);
        } catch (err) {
            setEditorError('Unable to load Nginx config.');
        } finally {
            setEditorLoading(false);
            setEditorVersionsLoading(false);
        }
    };

    const openEditor = async (domain) => {
        setEditorOpen(true);
        setEditorDomain(domain);
        await loadEditorData(domain.id);
    };

    const closeEditor = () => {
        setEditorOpen(false);
        setEditorDomain(null);
        setEditorConfig('');
        setEditorDefaultConfig('');
        setEditorIsCustom(false);
        setEditorError('');
        setEditorMessage('');
        setEditorTestOutput('');
        setEditorVersions([]);
    };

    const saveEditorConfig = async () => {
        if (!editorDomain) return;
        setEditorSaving(true);
        setEditorError('');
        setEditorMessage('');
        setEditorTestOutput('');
        try {
            const response = await api.admin.updateDomainNginxConfig(editorDomain.id, editorConfig);
            if (response?.success === false) {
                setEditorError(response?.message || 'Nginx deploy failed.');
                if (response?.output) {
                    setEditorTestOutput(response.output);
                }
            } else if (response?.data?.nginx_status === 'error') {
                setEditorError(response?.data?.nginx_error || 'Nginx deploy failed.');
            } else {
                setEditorMessage(response?.message || 'Config saved and applied.');
                setEditorIsCustom(true);
                await Promise.all([loadData(), loadEditorData(editorDomain.id)]);
            }
        } catch (err) {
            setEditorError('Failed to save config.');
        } finally {
            setEditorSaving(false);
        }
    };

    const resetEditorConfig = async () => {
        if (!editorDomain) return;
        setEditorSaving(true);
        setEditorError('');
        setEditorMessage('');
        setEditorTestOutput('');
        try {
            const response = await api.admin.resetDomainNginxConfig(editorDomain.id);
            if (response?.success === false) {
                setEditorError(response?.message || 'Reset failed.');
                if (response?.output) {
                    setEditorTestOutput(response.output);
                }
            } else {
                setEditorConfig(editorDefaultConfig);
                setEditorIsCustom(false);
                setEditorMessage(response?.message || 'Reset to default config.');
                if (response?.data?.nginx_status === 'error') {
                    setEditorError(response?.data?.nginx_error || 'Nginx validation failed.');
                }
                await Promise.all([loadData(), loadEditorData(editorDomain.id)]);
            }
        } catch (err) {
            setEditorError('Failed to reset config.');
        } finally {
            setEditorSaving(false);
        }
    };

    const testEditorConfig = async () => {
        if (!editorDomain) return;
        setEditorTesting(true);
        setEditorError('');
        setEditorMessage('');
        setEditorTestOutput('');
        try {
            const response = await api.admin.testDomainNginxConfig(editorDomain.id, editorConfig);
            if (response?.success) {
                setEditorMessage('Nginx test passed.');
            } else {
                setEditorError('Nginx test failed.');
            }
            if (response?.output) {
                setEditorTestOutput(response.output);
            }
        } catch (err) {
            setEditorError('Failed to run nginx test.');
        } finally {
            setEditorTesting(false);
        }
    };

    const restoreVersion = async (versionId) => {
        if (!editorDomain) return;
        setEditorSaving(true);
        setEditorError('');
        setEditorMessage('');
        setEditorTestOutput('');
        try {
            const response = await api.admin.restoreDomainNginxVersion(editorDomain.id, versionId);
            if (response?.success === false) {
                setEditorError(response?.message || 'Restore failed.');
                if (response?.output) {
                    setEditorTestOutput(response.output);
                }
            } else if (response?.data?.nginx_status === 'error') {
                setEditorError(response?.data?.nginx_error || 'Nginx validation failed.');
            } else {
                setEditorMessage(response?.message || 'Version restored and applied.');
                setEditorIsCustom(true);
                await Promise.all([loadData(), loadEditorData(editorDomain.id)]);
            }
        } catch (err) {
            setEditorError('Failed to restore version.');
        } finally {
            setEditorSaving(false);
        }
    };

    const toggleHttp3 = async (domain) => {
        setHttp3LoadingId(domain.id);
        try {
            await api.admin.toggleDomainHttp3(domain.id, !domain.http3_enabled);
            await loadData();
        } finally {
            setHttp3LoadingId(null);
        }
    };

    const checkHttp3 = async (domain) => {
        setHttp3CheckLoadingId(domain.id);
        try {
            await api.admin.checkDomainHttp3(domain.id);
            await loadData();
        } finally {
            setHttp3CheckLoadingId(null);
        }
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Platform Sites</h1>
                    <p className="text-sm text-gray-500">Manage domains and auto-provision niche templates.</p>
                </div>
                <button
                    onClick={loadData}
                    className="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-white border border-gray-200 hover:bg-gray-50"
                >
                    <RefreshCw className="w-4 h-4" />
                    Refresh
                </button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] gap-6">
                {isSuperadmin(currentUser) ? (
                    <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Create new site</h2>
                        <form className="space-y-4" onSubmit={handleSubmit}>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Site name</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.name}
                                    onChange={handleChange('name')}
                                    placeholder="Atlas Agency"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Slug</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.slug}
                                    onChange={handleChange('slug')}
                                    placeholder="atlas-agency"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Primary domain</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.domain}
                                    onChange={handleChange('domain')}
                                    placeholder="example.com"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Theme</label>
                                <select
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.themeId}
                                    onChange={handleChange('themeId')}
                                >
                                    <option value="">Select theme</option>
                                    {themes.map((theme) => (
                                        <option key={theme.id} value={theme.id}>
                                            {theme.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Blueprint</label>
                                <select
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.blueprintId}
                                    onChange={handleChange('blueprintId')}
                                >
                                    <option value="">Select blueprint</option>
                                    {blueprints.map((blueprint) => (
                                        <option key={blueprint.id} value={blueprint.id}>
                                            {blueprint.name}
                                        </option>
                                    ))}
                                </select>
                                {form.blueprintId && (
                                    <p className="mt-2 text-[11px] text-gray-500">
                                        {blueprints.find((bp) => bp.id === form.blueprintId)?.description || 'Blueprint presets will be applied.'}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Brand name</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.brandName}
                                    onChange={handleChange('brandName')}
                                    placeholder="Atlas"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Tagline</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.tagline}
                                    onChange={handleChange('tagline')}
                                    placeholder="Multi-domain powerhouse"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Primary color</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.primaryColor}
                                    onChange={handleChange('primaryColor')}
                                    placeholder="#1f2937"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Secondary color</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.secondaryColor}
                                    onChange={handleChange('secondaryColor')}
                                    placeholder="#f59e0b"
                                />
                            </div>
                        </div>

                            <button
                                type="submit"
                                disabled={saving}
                                className="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 disabled:opacity-60"
                            >
                                <Plus className="w-4 h-4" />
                                {saving ? 'Creating...' : 'Create & provision'}
                            </button>
                        </form>
                    </div>
                ) : currentUser?.app_mode === 'tenant' ? (
                    <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex items-center gap-3 text-sm text-gray-500">
                        <Globe className="w-4 h-4" />
                        Manage your domain, SSL, and Nginx configuration below.
                    </div>
                ) : (
                    <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex items-center gap-3 text-sm text-gray-500">
                        <Shield className="w-4 h-4" />
                        Only superadmins can create new sites.
                    </div>
                )}

                <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4">Active sites</h2>
                    {loading ? (
                        <p className="text-sm text-gray-500">Loading...</p>
                    ) : tenants.length === 0 ? (
                        <p className="text-sm text-gray-500">No sites yet. Create the first domain.</p>
                    ) : (
                        <div className="space-y-4">
                            {tenants.map((tenant) => (
                                <div key={tenant.id} className="border border-gray-100 rounded-xl p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <h3 className="font-semibold text-gray-900">{tenant.name}</h3>
                                            <p className="text-xs text-gray-500">
                                                {tenant.theme?.name || 'No theme'}
                                                {tenant.active_subscription?.plan?.name ? ` • ${tenant.active_subscription.plan.name}` : ' • No plan'}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-3 text-xs">
                                            <span className={`uppercase ${tenant.status === 'archived' ? 'text-rose-500' : 'text-gray-400'}`}>
                                                {tenant.status}
                                            </span>
                                            <span
                                                className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] uppercase ${
                                                    tenant.instance_status === 'ready'
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : tenant.instance_status === 'error'
                                                            ? 'bg-rose-100 text-rose-700'
                                                            : 'bg-amber-100 text-amber-700'
                                                }`}
                                            >
                                                <Server className="w-3 h-3" />
                                                {tenant.instance_status || 'pending'}
                                            </span>
                                            {tenant.latest_provisioning_job && (
                                                <span
                                                    className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] uppercase ${provisioningBadgeClass(tenant.latest_provisioning_job.status)}`}
                                                >
                                                    <RotateCw className="w-3 h-3" />
                                                    {tenant.latest_provisioning_job.status}
                                                </span>
                                            )}
                                            <button
                                                onClick={() => openCloneModal(tenant)}
                                                className="text-xs font-semibold text-blue-600 hover:text-blue-700"
                                            >
                                                Clone
                                            </button>
                                            <button
                                                onClick={() => openOpsModal(tenant)}
                                                className="text-xs font-semibold text-indigo-600 hover:text-indigo-700"
                                            >
                                                Ops
                                            </button>
                                            {tenant.status === 'archived' ? (
                                                <button
                                                    onClick={() => unarchiveTenant(tenant.id)}
                                                    className="text-xs font-semibold text-emerald-600 hover:text-emerald-700"
                                                >
                                                    Restore
                                                </button>
                                            ) : (
                                                <button
                                                    onClick={() => archiveTenant(tenant.id)}
                                                    className="text-xs font-semibold text-rose-600 hover:text-rose-700"
                                                >
                                                    Archive
                                                </button>
                                            )}
                                            {isSuperadmin(currentUser) && (
                                                <button
                                                    onClick={() => deleteTenant(tenant)}
                                                    className="text-xs font-semibold text-rose-700 hover:text-rose-800"
                                                >
                                                    Delete
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                    <div className="mt-3 flex items-center gap-3 text-xs text-gray-500">
                                        <span>Instance</span>
                                        {tenant.instance_status === 'error' && (
                                            <span className="inline-flex items-center gap-1 text-rose-600">
                                                <AlertTriangle className="w-3 h-3" />
                                                Error
                                            </span>
                                        )}
                                        <button
                                            onClick={() => toggleInstanceLog(tenant.id)}
                                            className="text-xs font-semibold text-gray-600 hover:text-gray-800"
                                        >
                                            {expandedInstanceId === tenant.id ? 'Hide details' : 'Details'}
                                        </button>
                                        <button
                                            onClick={() => provisionInstance(tenant.id)}
                                            disabled={instanceLoadingId === tenant.id}
                                            className="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 disabled:opacity-60"
                                        >
                                            <RotateCw className="w-3 h-3" />
                                            {instanceLoadingId === tenant.id ? 'Provisioning...' : 'Retry instance'}
                                        </button>
                                        <button
                                            onClick={() => retryProvisioning(tenant)}
                                            disabled={provisioningRetryId === tenant.id}
                                            className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700 disabled:opacity-60"
                                        >
                                            <RotateCw className="w-3 h-3" />
                                            {provisioningRetryId === tenant.id ? 'Retrying...' : 'Retry provisioning'}
                                        </button>
                                        <button
                                            onClick={() => rollbackProvisioning(tenant)}
                                            disabled={provisioningRollbackId === tenant.id}
                                            className="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-700 disabled:opacity-60"
                                        >
                                            <AlertTriangle className="w-3 h-3" />
                                            {provisioningRollbackId === tenant.id ? 'Rolling back...' : 'Rollback'}
                                        </button>
                                    </div>

                                    {expandedInstanceId === tenant.id && (
                                        <div className="mt-3 space-y-3">
                                            {tenant.latest_provisioning_job && (
                                                <div className="rounded-xl border border-gray-100 bg-gray-50 p-3 text-xs text-gray-600">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span className={`rounded-full px-2 py-0.5 text-[10px] uppercase ${provisioningBadgeClass(tenant.latest_provisioning_job.status)}`}>
                                                            {tenant.latest_provisioning_job.status}
                                                        </span>
                                                        <span>
                                                            {tenant.latest_provisioning_job.message || 'Provisioning update.'}
                                                        </span>
                                                    </div>
                                                </div>
                                            )}
                                            <div className="grid gap-2 text-[11px] text-gray-600 md:grid-cols-2">
                                                <div>
                                                    <span className="font-semibold text-gray-700">Root:</span>{' '}
                                                    {tenant.instance_root || '—'}
                                                </div>
                                                <div>
                                                    <span className="font-semibold text-gray-700">Public root:</span>{' '}
                                                    {tenant.instance_public_root || '—'}
                                                </div>
                                                <div>
                                                    <span className="font-semibold text-gray-700">PHP‑FPM socket:</span>{' '}
                                                    {tenant.instance_php_socket || '—'}
                                                </div>
                                                <div>
                                                    <span className="font-semibold text-gray-700">DB name:</span>{' '}
                                                    {tenant.instance_db_name || '—'}
                                                </div>
                                                <div>
                                                    <span className="font-semibold text-gray-700">DB user:</span>{' '}
                                                    {tenant.instance_db_user || '—'}
                                                </div>
                                                <div>
                                                    <span className="font-semibold text-gray-700">Installed:</span>{' '}
                                                    {tenant.instance_installed_at
                                                        ? new Date(tenant.instance_installed_at).toLocaleString()
                                                        : '—'}
                                                </div>
                                            </div>

                                            <div className="rounded-xl border border-gray-100 bg-white p-3 text-xs text-gray-600 space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <span className="font-semibold text-gray-700">Quota & Limits</span>
                                                    <button
                                                        onClick={() => fetchTenantSecurityProfile(tenant.id)}
                                                        disabled={securityProfileLoadingId === tenant.id}
                                                        className="text-[11px] font-semibold text-gray-600 hover:text-gray-800 disabled:opacity-60"
                                                    >
                                                        {securityProfileLoadingId === tenant.id ? 'Loading...' : 'Refresh'}
                                                    </button>
                                                </div>
                                                <div className="flex flex-wrap gap-3 text-[11px] text-gray-500">
                                                    <span>Status: {tenantQuotaUsage[tenant.id]?.status || 'unknown'}</span>
                                                    <span>Alert threshold: {tenantQuotaUsage[tenant.id]?.alert_threshold_percent ?? 80}%</span>
                                                    <span>Source: {tenantQuotaUsage[tenant.id]?.runtime_source || 'unavailable'}</span>
                                                </div>
                                                <div className="grid gap-2 text-[11px] text-gray-500 md:grid-cols-3">
                                                    <span>
                                                        Requests: {tenantQuotaUsage[tenant.id]?.requests?.used ?? '—'} / {tenantQuotaUsage[tenant.id]?.requests?.limit ?? '∞'}
                                                    </span>
                                                    <span>
                                                        Storage MB: {tenantQuotaUsage[tenant.id]?.storage_mb?.used ?? '—'} / {tenantQuotaUsage[tenant.id]?.storage_mb?.limit ?? '∞'}
                                                    </span>
                                                    <span>
                                                        DB MB: {tenantQuotaUsage[tenant.id]?.database_mb?.used ?? '—'} / {tenantQuotaUsage[tenant.id]?.database_mb?.limit ?? '∞'}
                                                    </span>
                                                    <span>
                                                        CPU %: {tenantQuotaUsage[tenant.id]?.cpu_percent?.used ?? '—'} / {tenantQuotaUsage[tenant.id]?.cpu_percent?.limit ?? '∞'}
                                                    </span>
                                                    <span>
                                                        Memory MB: {tenantQuotaUsage[tenant.id]?.memory_mb?.used ?? '—'} / {tenantQuotaUsage[tenant.id]?.memory_mb?.limit ?? '∞'}
                                                    </span>
                                                    <span>
                                                        Workers: {tenantQuotaUsage[tenant.id]?.workers?.used ?? '—'} / {tenantQuotaUsage[tenant.id]?.workers?.limit ?? '∞'}
                                                    </span>
                                                </div>
                                                {(tenantQuotaUsage[tenant.id]?.alerts || []).length > 0 && (
                                                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-2 text-[11px] text-amber-800">
                                                        {(tenantQuotaUsage[tenant.id]?.alerts || []).map((alert, index) => (
                                                            <div key={`${tenant.id}-quota-alert-${index}`}>
                                                                {alert.key}: {alert.percent}% ({alert.used}/{alert.limit}) [{alert.severity}]
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                                <div className="grid gap-2 md:grid-cols-3">
                                                    <label className="space-y-1">
                                                        <span className="text-[11px] text-gray-500">Monthly Requests</span>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={tenantSecurityProfiles[tenant.id]?.max_monthly_requests ?? ''}
                                                            onChange={(event) => updateTenantQuotaField(tenant.id, 'max_monthly_requests', event.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 px-2 py-1 text-xs"
                                                        />
                                                    </label>
                                                    <label className="space-y-1">
                                                        <span className="text-[11px] text-gray-500">Storage MB</span>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={tenantSecurityProfiles[tenant.id]?.max_storage_mb ?? ''}
                                                            onChange={(event) => updateTenantQuotaField(tenant.id, 'max_storage_mb', event.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 px-2 py-1 text-xs"
                                                        />
                                                    </label>
                                                    <label className="space-y-1">
                                                        <span className="text-[11px] text-gray-500">Database MB</span>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={tenantSecurityProfiles[tenant.id]?.max_db_size_mb ?? ''}
                                                            onChange={(event) => updateTenantQuotaField(tenant.id, 'max_db_size_mb', event.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 px-2 py-1 text-xs"
                                                        />
                                                    </label>
                                                    <label className="space-y-1">
                                                        <span className="text-[11px] text-gray-500">CPU %</span>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={tenantSecurityProfiles[tenant.id]?.max_cpu_percent ?? ''}
                                                            onChange={(event) => updateTenantQuotaField(tenant.id, 'max_cpu_percent', event.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 px-2 py-1 text-xs"
                                                        />
                                                    </label>
                                                    <label className="space-y-1">
                                                        <span className="text-[11px] text-gray-500">Memory MB</span>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={tenantSecurityProfiles[tenant.id]?.max_memory_mb ?? ''}
                                                            onChange={(event) => updateTenantQuotaField(tenant.id, 'max_memory_mb', event.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 px-2 py-1 text-xs"
                                                        />
                                                    </label>
                                                    <label className="space-y-1">
                                                        <span className="text-[11px] text-gray-500">Workers</span>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={tenantSecurityProfiles[tenant.id]?.max_worker_processes ?? ''}
                                                            onChange={(event) => updateTenantQuotaField(tenant.id, 'max_worker_processes', event.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 px-2 py-1 text-xs"
                                                        />
                                                    </label>
                                                </div>
                                                <div className="flex items-center justify-between gap-2">
                                                    <label className="space-y-1">
                                                        <span className="text-[11px] text-gray-500">Alert Threshold %</span>
                                                        <input
                                                            type="number"
                                                            min="50"
                                                            max="99"
                                                            value={tenantSecurityProfiles[tenant.id]?.quota_alert_threshold_percent ?? 80}
                                                            onChange={(event) => updateTenantQuotaField(tenant.id, 'quota_alert_threshold_percent', event.target.value)}
                                                            className="w-28 rounded-lg border border-gray-200 px-2 py-1 text-xs"
                                                        />
                                                    </label>
                                                    <button
                                                        onClick={() => saveTenantSecurityProfile(tenant)}
                                                        disabled={securityProfileSavingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-blue-700 hover:bg-blue-50 disabled:opacity-60"
                                                    >
                                                        {securityProfileSavingId === tenant.id ? 'Saving...' : 'Save Limits'}
                                                    </button>
                                                </div>
                                            </div>

                                            <div className="rounded-xl border border-gray-100 bg-white p-3 text-xs text-gray-600 space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <span className="font-semibold text-gray-700">SSH / SFTP Access</span>
                                                </div>
                                                <div className="grid gap-2 md:grid-cols-2">
                                                    <div>
                                                        <span className="font-semibold text-gray-700">User:</span>{' '}
                                                        {tenant.instance_ssh_user || tenantAccessInfo[tenant.id]?.user || 'Not provisioned'}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-gray-700">Host:</span>{' '}
                                                        {tenantAccessInfo[tenant.id]?.host || window.location.hostname}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-gray-700">Port:</span>{' '}
                                                        {tenant.instance_ssh_port || tenantAccessInfo[tenant.id]?.port || 22}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-gray-700">Path:</span>{' '}
                                                        {tenant.instance_root || tenantAccessInfo[tenant.id]?.site_path || '—'}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-gray-700">Auth:</span>{' '}
                                                        {tenantAccessInfo[tenant.id]?.auth_mode || 'both'}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-gray-700">SFTP-only:</span>{' '}
                                                        {tenantAccessInfo[tenant.id]?.sftp_only ? 'yes' : 'no'}
                                                    </div>
                                                </div>
                                                <div className="text-[11px] text-gray-500">
                                                    Use SFTP (secure FTP) with this account.
                                                </div>
                                                <div className="flex flex-wrap gap-2">
                                                    <button
                                                        onClick={() => provisionTenantAccess(tenant)}
                                                        disabled={accessLoadingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-60"
                                                    >
                                                        {accessLoadingId === tenant.id ? 'Setting up...' : 'Setup Access'}
                                                    </button>
                                                    <button
                                                        onClick={() => rotateTenantAccessPassword(tenant)}
                                                        disabled={accessLoadingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-blue-700 hover:bg-blue-50 disabled:opacity-60"
                                                    >
                                                        {accessLoadingId === tenant.id ? 'Rotating...' : 'Reset Password'}
                                                    </button>
                                                    <button
                                                        onClick={() => installTenantAccessKey(tenant)}
                                                        disabled={accessKeyLoadingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-50 disabled:opacity-60"
                                                    >
                                                        {accessKeyLoadingId === tenant.id ? 'Adding key...' : 'Add SSH Key'}
                                                    </button>
                                                </div>
                                            </div>

                                            <div className="rounded-xl border border-gray-100 bg-white p-3 text-xs text-gray-600 space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <span className="font-semibold text-gray-700">Local Mail / SMTP</span>
                                                    <button
                                                        onClick={() => fetchTenantMailState(tenant.id)}
                                                        disabled={mailLoadingId === tenant.id}
                                                        className="text-[11px] font-semibold text-gray-600 hover:text-gray-800 disabled:opacity-60"
                                                    >
                                                        {mailLoadingId === tenant.id ? 'Loading...' : 'Refresh'}
                                                    </button>
                                                </div>
                                                <div className="grid gap-2 md:grid-cols-3 text-[11px] text-gray-500">
                                                    <span>
                                                        Configured: {tenantMailSettings[tenant.id]?.mail_configured ? 'yes' : 'no'}
                                                    </span>
                                                    <span>
                                                        Provider: {tenantMailSettings[tenant.id]?.mail_provider || 'local'}
                                                    </span>
                                                    <span>
                                                        Local enabled: {tenantMailSettings[tenant.id]?.mail_local_enabled ? 'yes' : 'no'}
                                                    </span>
                                                    <span>
                                                        Host: {tenantMailSettings[tenant.id]?.mail_host || '—'}
                                                    </span>
                                                    <span>
                                                        Port: {tenantMailSettings[tenant.id]?.mail_port || '—'}
                                                    </span>
                                                    <span>
                                                        Encryption: {tenantMailSettings[tenant.id]?.mail_encryption || 'none'}
                                                    </span>
                                                    <span>
                                                        Daily limit: {tenantMailSettings[tenant.id]?.mail_daily_limit ?? '—'}
                                                    </span>
                                                    <span>
                                                        Per-minute limit: {tenantMailSettings[tenant.id]?.mail_per_minute_limit ?? '—'}
                                                    </span>
                                                    <span>
                                                        From: {tenantMailSettings[tenant.id]?.mail_from_address || '—'}
                                                    </span>
                                                </div>
                                                <div className="flex flex-wrap gap-2">
                                                    <button
                                                        onClick={() => configureTenantMail(tenant)}
                                                        disabled={mailSavingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-60"
                                                    >
                                                        {mailSavingId === tenant.id ? 'Saving...' : 'Configure SMTP'}
                                                    </button>
                                                    <button
                                                        onClick={() => testTenantMail(tenant)}
                                                        disabled={mailSavingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-blue-700 hover:bg-blue-50 disabled:opacity-60"
                                                    >
                                                        {mailSavingId === tenant.id ? 'Testing...' : 'Test SMTP'}
                                                    </button>
                                                    <button
                                                        onClick={() => createTenantMailbox(tenant)}
                                                        disabled={mailSavingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-50 disabled:opacity-60"
                                                    >
                                                        {mailSavingId === tenant.id ? 'Creating...' : 'Create Mailbox'}
                                                    </button>
                                                </div>
                                                <div className="text-[11px] text-gray-500">
                                                    Today: sent {tenantMailSummary[tenant.id]?.sent_today ?? 0} • failed {tenantMailSummary[tenant.id]?.failed_today ?? 0}
                                                </div>
                                                {tenantMailboxes[tenant.id]?.length ? (
                                                    <div className="space-y-2">
                                                        {(tenantMailboxes[tenant.id] || []).map((mailbox) => (
                                                            <div
                                                                key={mailbox.id}
                                                                className="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-[11px] text-gray-600"
                                                            >
                                                                <div className="flex flex-wrap items-center justify-between gap-2">
                                                                    <div>
                                                                        <span className="font-semibold text-gray-700">{mailbox.email}</span>
                                                                        <span className="ml-2 text-gray-500">
                                                                            quota {mailbox.quota_mb} MB
                                                                        </span>
                                                                        <span className="ml-2 text-gray-500">
                                                                            usage {formatBytes(mailbox.last_usage_bytes)}
                                                                        </span>
                                                                    </div>
                                                                    <div className="flex flex-wrap gap-2">
                                                                        <button
                                                                            onClick={() => resetTenantMailboxPassword(tenant, mailbox)}
                                                                            disabled={mailSavingId === tenant.id}
                                                                            className="text-[11px] font-semibold text-blue-700 hover:text-blue-800 disabled:opacity-60"
                                                                        >
                                                                            Reset Password
                                                                        </button>
                                                                        <button
                                                                            onClick={() => refreshTenantMailboxUsage(tenant, mailbox)}
                                                                            disabled={mailSavingId === tenant.id}
                                                                            className="text-[11px] font-semibold text-gray-700 hover:text-gray-800 disabled:opacity-60"
                                                                        >
                                                                            Refresh Usage
                                                                        </button>
                                                                        <button
                                                                            onClick={() => deleteTenantMailbox(tenant, mailbox)}
                                                                            disabled={mailSavingId === tenant.id}
                                                                            className="text-[11px] font-semibold text-rose-700 hover:text-rose-800 disabled:opacity-60"
                                                                        >
                                                                            Delete
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <div className="mt-1 text-[10px] text-gray-500">
                                                                    Last check: {mailbox.last_usage_checked_at ? new Date(mailbox.last_usage_checked_at).toLocaleString() : 'never'}
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                ) : (
                                                    <div className="text-[11px] text-gray-500">
                                                        No mailboxes created yet.
                                                    </div>
                                                )}
                                                {(tenantMailEvents[tenant.id] || []).length > 0 && (
                                                    <div className="space-y-1">
                                                        <div className="text-[11px] font-semibold text-gray-700">Recent mail events</div>
                                                        {(tenantMailEvents[tenant.id] || []).slice(0, 8).map((event) => (
                                                            <div
                                                                key={event.id}
                                                                className="flex flex-wrap items-center justify-between gap-2 rounded-md bg-gray-50 px-2 py-1 text-[10px] text-gray-500"
                                                            >
                                                                <span>
                                                                    {event.event_type} • {event.status}
                                                                </span>
                                                                <span>{event.recipient || '—'}</span>
                                                                <span>{event.created_at ? new Date(event.created_at).toLocaleString() : '—'}</span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>

                                            <div className="rounded-xl border border-gray-100 bg-white p-3 text-xs text-gray-600 space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <span className="font-semibold text-gray-700">Secrets -> Tenant .env</span>
                                                </div>
                                                <p className="text-[11px] text-gray-500">
                                                    Save secrets encrypted in platform DB, then sync selected keys into this tenant .env file.
                                                </p>
                                                <div className="flex flex-wrap gap-2">
                                                    <button
                                                        onClick={() => saveTenantSecretAndSync(tenant)}
                                                        disabled={secretSyncLoadingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-60"
                                                    >
                                                        {secretSyncLoadingId === tenant.id ? 'Saving...' : 'Save Secret + Sync'}
                                                    </button>
                                                    <button
                                                        onClick={() => syncExistingTenantSecret(tenant)}
                                                        disabled={secretSyncLoadingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-blue-700 hover:bg-blue-50 disabled:opacity-60"
                                                    >
                                                        {secretSyncLoadingId === tenant.id ? 'Syncing...' : 'Sync Existing Secret'}
                                                    </button>
                                                    <button
                                                        onClick={() => removeTenantEnvKey(tenant)}
                                                        disabled={secretSyncLoadingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-60"
                                                    >
                                                        {secretSyncLoadingId === tenant.id ? 'Removing...' : 'Remove Env Key'}
                                                    </button>
                                                </div>
                                            </div>

                                            <div className="rounded-xl border border-gray-100 bg-gray-50 p-3 text-xs text-gray-600 space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <span className="font-semibold text-gray-700">Orchestration</span>
                                                    <button
                                                        onClick={() => fetchOrchestrationStatus(tenant.id)}
                                                        disabled={orchestrationCheckingId === tenant.id}
                                                        className="text-xs font-semibold text-gray-600 hover:text-gray-800"
                                                    >
                                                        {orchestrationCheckingId === tenant.id ? 'Checking...' : 'Refresh'}
                                                    </button>
                                                </div>
                                                <div className="flex flex-wrap items-center gap-3">
                                                    <span className="text-[11px] uppercase text-gray-400">
                                                        State: {orchestrationStatus[tenant.id]?.state || 'unknown'}
                                                    </span>
                                                    <span className="text-[11px] text-gray-400">
                                                        Maintenance: {orchestrationStatus[tenant.id]?.maintenance ? 'On' : 'Off'}
                                                    </span>
                                                    <span className="text-[11px] text-gray-400">
                                                        Socket: {orchestrationStatus[tenant.id]?.socket ? 'OK' : 'Missing'}
                                                    </span>
                                                    {orchestrationStatus[tenant.id]?.frontend_service !== null && (
                                                        <span className="text-[11px] text-gray-400">
                                                            Frontend: {orchestrationStatus[tenant.id]?.frontend_service ? 'Running' : 'Stopped'}
                                                        </span>
                                                    )}
                                                </div>
                                                {orchestrationStatus[tenant.id]?.message && (
                                                    <div className="text-[11px] text-gray-500">
                                                        {orchestrationStatus[tenant.id].message}
                                                    </div>
                                                )}
                                                {orchestrationMessage[tenant.id] && (
                                                    <div className="text-[11px] text-emerald-700">
                                                        {orchestrationMessage[tenant.id]}
                                                    </div>
                                                )}
                                                <div className="flex flex-wrap gap-2">
                                                    <button
                                                        onClick={() => runOrchestration(tenant.id, 'start')}
                                                        disabled={orchestrationLoadingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-gray-700 hover:bg-white disabled:opacity-60"
                                                    >
                                                        Start
                                                    </button>
                                                    <button
                                                        onClick={() => runOrchestration(tenant.id, 'stop')}
                                                        disabled={orchestrationLoadingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-gray-700 hover:bg-white disabled:opacity-60"
                                                    >
                                                        Stop
                                                    </button>
                                                    <button
                                                        onClick={() => runOrchestration(tenant.id, 'restart')}
                                                        disabled={orchestrationLoadingId === tenant.id}
                                                        className="px-3 py-1 rounded-lg border border-gray-200 text-[11px] font-semibold text-gray-700 hover:bg-white disabled:opacity-60"
                                                    >
                                                        Restart
                                                    </button>
                                                </div>
                                            </div>

                                            {tenant.instance_last_error && (
                                                <div className="rounded-xl border border-rose-100 bg-rose-50 p-3 text-xs text-rose-800 whitespace-pre-wrap">
                                                    {tenant.instance_last_error}
                                                </div>
                                            )}

                                            <div className="rounded-xl border border-gray-100 bg-white p-3 text-xs text-gray-600">
                                                <div className="text-[11px] uppercase text-gray-400 mb-2">Domain health</div>
                                                <div className="grid gap-2 md:grid-cols-3">
                                                    <div>
                                                        SSL ready:{' '}
                                                        {(tenant.domains || []).filter((d) => d.ssl_certificate?.status === 'ready').length}
                                                    </div>
                                                    <div>
                                                        Nginx ready:{' '}
                                                        {(tenant.domains || []).filter((d) => d.nginx_status === 'ready').length}
                                                    </div>
                                                    <div>
                                                        HTTP/3 on:{' '}
                                                        {(tenant.domains || []).filter((d) => d.http3_enabled).length}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                    <div className="mt-3 space-y-2">
                                        {(tenant.domains || []).map((domain) => (
                                            <div
                                                key={domain.id}
                                                className="flex items-center justify-between text-sm rounded-lg bg-gray-50 px-3 py-2"
                                            >
                                                <div className="flex items-center gap-2">
                                                    <Globe className="w-4 h-4 text-gray-500" />
                                                    <span>{domain.hostname}</span>
                                                    {domain.environment === 'staging' && (
                                                        <span className="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                                                            Staging
                                                        </span>
                                                    )}
                                                    {domain.environment === 'preview' && (
                                                        <span className="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                                            Preview
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <span className="text-xs text-gray-500">{domain.status}</span>
                                                    <span className="text-xs text-gray-400">
                                                        SSL: {domain.ssl_certificate?.status || 'pending'}
                                                    </span>
                                                    <span className="text-xs text-gray-400">
                                                        Nginx: {domain.nginx_status || 'pending'}
                                                    </span>
                                                    <button
                                                        onClick={() => toggleHttp3(domain)}
                                                        disabled={domain.has_custom_nginx || http3LoadingId === domain.id}
                                                        className={`text-xs font-semibold ${
                                                            domain.http3_enabled ? 'text-emerald-600' : 'text-gray-500'
                                                        } ${domain.has_custom_nginx ? 'cursor-not-allowed opacity-50' : 'hover:text-emerald-700'}`}
                                                        title={domain.has_custom_nginx ? 'Disable custom config to toggle HTTP/3' : 'Toggle HTTP/3'}
                                                    >
                                                        <span className="inline-flex items-center gap-1">
                                                            <Zap className="w-3 h-3" />
                                                            HTTP/3: {domain.http3_enabled ? 'On' : 'Off'}
                                                        </span>
                                                    </button>
                                                    {domain.http3_enabled && (
                                                        <div className="flex items-center gap-2 text-xs text-gray-400">
                                                            <span title={domain.http3_error || ''}>
                                                                Health: {domain.http3_status || 'unknown'}
                                                            </span>
                                                            <span title={domain.http3_udp_error || ''}>
                                                                UDP 443: {domain.http3_udp_status || 'unknown'}
                                                            </span>
                                                            {domain.http3_checked_at && (
                                                                <span>
                                                                    {new Date(domain.http3_checked_at).toLocaleString()}
                                                                </span>
                                                            )}
                                                            <button
                                                                onClick={() => checkHttp3(domain)}
                                                                disabled={http3CheckLoadingId === domain.id}
                                                                className="text-xs font-semibold text-gray-600 hover:text-gray-800"
                                                            >
                                                                {http3CheckLoadingId === domain.id ? 'Checking...' : 'Check'}
                                                            </button>
                                                        </div>
                                                    )}
                                                    <button
                                                        onClick={() => provisionDomain(domain.id)}
                                                        className="text-xs font-semibold text-blue-600 hover:text-blue-700"
                                                    >
                                                        Provision
                                                    </button>
                                                    <button
                                                        onClick={() => requestSsl(domain.id)}
                                                        className="text-xs font-semibold text-emerald-600 hover:text-emerald-700"
                                                    >
                                                        SSL
                                                    </button>
                                                    <button
                                                        onClick={() => requestNginx(domain.id)}
                                                        className="text-xs font-semibold text-purple-600 hover:text-purple-700"
                                                    >
                                                        Nginx
                                                    </button>
                                                    <button
                                                        onClick={() => openEditor(domain)}
                                                        className="text-xs font-semibold text-gray-700 hover:text-gray-900 inline-flex items-center gap-1"
                                                    >
                                                        <Code className="w-3 h-3" />
                                                        Vhost
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {editorOpen && (
                <div className="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-6">
                    <div className="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
                        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">Nginx Vhost Editor</h3>
                                <p className="text-xs text-gray-500">
                                    {editorDomain?.hostname || 'Domain'} • {editorIsCustom ? 'Custom config' : 'Default config'}
                                </p>
                            </div>
                            <button onClick={closeEditor} className="text-gray-500 hover:text-gray-700">
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <div className="flex-1 overflow-auto p-6 space-y-4">
                            {editorLoading ? (
                                <p className="text-sm text-gray-500">Loading config...</p>
                            ) : (
                                <div className="grid gap-4 lg:grid-cols-[2fr,1fr]">
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between text-xs text-gray-500">
                                            <span>Edit the vhost file below. Saving will validate via nginx -t.</span>
                                            <button
                                                onClick={() => setEditorConfig(editorDefaultConfig)}
                                                className="text-xs font-semibold text-blue-600 hover:text-blue-700"
                                            >
                                                Load default
                                            </button>
                                        </div>
                                        <textarea
                                            value={editorConfig}
                                            onChange={(event) => setEditorConfig(event.target.value)}
                                            className="w-full min-h-[360px] font-mono text-xs rounded-xl border border-gray-200 p-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />

                                        {editorTestOutput && (
                                            <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                                <p className="text-xs uppercase text-gray-500 mb-2">Nginx test output</p>
                                                <pre className="text-xs text-gray-700 whitespace-pre-wrap">{editorTestOutput}</pre>
                                            </div>
                                        )}
                                    </div>

                                    <div className="space-y-3">
                                        <div className="flex items-center justify-between">
                                            <p className="text-xs uppercase text-gray-500">History</p>
                                            <span className="text-xs text-gray-400">{editorVersions.length}</span>
                                        </div>
                                        {editorVersionsLoading ? (
                                            <p className="text-xs text-gray-500">Loading versions...</p>
                                        ) : editorVersions.length === 0 ? (
                                            <p className="text-xs text-gray-500">No versions yet.</p>
                                        ) : (
                                            <div className="space-y-2">
                                                {editorVersions.map((version) => (
                                                    <div
                                                        key={version.id}
                                                        className="rounded-lg border border-gray-200 p-3 text-xs"
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <span className="font-semibold text-gray-700">
                                                                {version.source}
                                                            </span>
                                                            <button
                                                                onClick={() => restoreVersion(version.id)}
                                                                className="text-xs font-semibold text-blue-600 hover:text-blue-700"
                                                            >
                                                                Restore
                                                            </button>
                                                        </div>
                                                        <p className="text-[11px] text-gray-500 mt-1">
                                                            {new Date(version.created_at).toLocaleString()} • {version.creator?.name || 'System'}
                                                        </p>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {editorError && (
                                <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    {editorError}
                                </div>
                            )}
                            {editorMessage && (
                                <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                    {editorMessage}
                                </div>
                            )}
                        </div>

                        <div className="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div className="text-xs text-gray-500">
                                {editorIsCustom
                                    ? 'Custom config disables HTTP/3 toggle. Reset to default to re-enable it.'
                                    : 'Default config auto-updates on SSL/HTTP3 changes.'}
                            </div>
                            <div className="flex items-center gap-3">
                                <button
                                    onClick={testEditorConfig}
                                    disabled={editorTesting || editorLoading}
                                    className="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-200 hover:bg-white disabled:opacity-60"
                                >
                                    {editorTesting ? 'Testing...' : 'Test'}
                                </button>
                                <button
                                    onClick={resetEditorConfig}
                                    disabled={editorSaving}
                                    className="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-200 hover:bg-white disabled:opacity-60"
                                >
                                    Reset to default
                                </button>
                                <button
                                    onClick={saveEditorConfig}
                                    disabled={editorSaving || editorLoading}
                                    className="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-900 text-white hover:bg-gray-800 disabled:opacity-60"
                                >
                                    {editorSaving ? 'Saving...' : 'Save & Apply'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {cloneOpen && (
                <div className="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-6">
                    <div className="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
                        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">Clone tenant</h3>
                                <p className="text-xs text-gray-500">
                                    {cloneTenant?.name || 'Tenant'} → new instance
                                </p>
                            </div>
                            <button onClick={closeCloneModal} className="text-gray-500 hover:text-gray-700">
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        <form onSubmit={submitClone} className="p-6 space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="text-xs font-semibold uppercase text-gray-500">New name</label>
                                    <input
                                        className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                        value={cloneForm.name}
                                        onChange={(e) => setCloneForm((prev) => ({ ...prev, name: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-semibold uppercase text-gray-500">Slug</label>
                                    <input
                                        className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                        value={cloneForm.slug}
                                        onChange={(e) => setCloneForm((prev) => ({ ...prev, slug: e.target.value }))}
                                        placeholder="leave blank for auto"
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Primary domain</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={cloneForm.domain}
                                    onChange={(e) => setCloneForm((prev) => ({ ...prev, domain: e.target.value }))}
                                    placeholder="new-domain.com"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Theme</label>
                                <select
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={cloneForm.themeId}
                                    onChange={(e) => setCloneForm((prev) => ({ ...prev, themeId: e.target.value }))}
                                >
                                    <option value="">Use same theme</option>
                                    {themes.map((theme) => (
                                        <option key={theme.id} value={theme.id}>
                                            {theme.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2 text-xs text-gray-600">
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={cloneForm.copySettings}
                                        onChange={(e) => setCloneForm((prev) => ({ ...prev, copySettings: e.target.checked }))}
                                    />
                                    Copy production settings
                                </label>
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={cloneForm.copyStaging}
                                        onChange={(e) => setCloneForm((prev) => ({ ...prev, copyStaging: e.target.checked }))}
                                    />
                                    Copy staging settings
                                </label>
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={cloneForm.fullClone}
                                        onChange={(e) => setCloneForm((prev) => ({ ...prev, fullClone: e.target.checked }))}
                                    />
                                    Full clone (DB + files)
                                </label>
                            </div>
                            {cloneError && (
                                <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-700">
                                    {cloneError}
                                </div>
                            )}
                            <div className="flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={closeCloneModal}
                                    className="px-4 py-2 text-sm font-semibold rounded-lg border border-gray-200 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={cloneSaving}
                                    className="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-900 text-white hover:bg-gray-800 disabled:opacity-60"
                                >
                                    {cloneSaving ? 'Cloning...' : 'Clone tenant'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {opsOpen && (
                <div className="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-6">
                    <div className="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
                        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">Tenant Ops Center</h3>
                                <p className="text-xs text-gray-500">{opsTenant?.name || 'Tenant'} • infra & backups</p>
                            </div>
                            <button onClick={closeOpsModal} className="text-gray-500 hover:text-gray-700">
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <div className="flex-1 overflow-auto p-6 space-y-6">
                            <div className="grid gap-6 lg:grid-cols-2">
                                <div className="rounded-2xl border border-gray-200 bg-gray-50 p-5 space-y-4">
                                    <div className="flex items-center justify-between">
                                        <h4 className="text-sm font-semibold text-gray-800">Backups</h4>
                                        <button
                                            onClick={runOpsBackup}
                                            disabled={opsBackupRunning}
                                            className="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-900 text-white hover:bg-gray-800 disabled:opacity-60"
                                        >
                                            {opsBackupRunning ? 'Running...' : 'Run Backup'}
                                        </button>
                                    </div>

                                    <div className="grid gap-3 text-xs text-gray-600 md:grid-cols-2">
                                        <label className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                checked={opsBackupSettings.backup_enabled}
                                                onChange={(e) => setOpsBackupSettings((prev) => ({ ...prev, backup_enabled: e.target.checked }))}
                                            />
                                            Enable backups
                                        </label>
                                        <label className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                checked={opsBackupSettings.backup_s3_enabled}
                                                onChange={(e) => setOpsBackupSettings((prev) => ({ ...prev, backup_s3_enabled: e.target.checked }))}
                                            />
                                            Upload to S3
                                        </label>
                                        <div>
                                            <label className="text-[11px] uppercase text-gray-500">Interval (hours)</label>
                                            <input
                                                type="number"
                                                className="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                                value={opsBackupSettings.backup_interval_hours}
                                                onChange={(e) => setOpsBackupSettings((prev) => ({ ...prev, backup_interval_hours: e.target.value }))}
                                            />
                                        </div>
                                        <div>
                                            <label className="text-[11px] uppercase text-gray-500">Retention (days)</label>
                                            <input
                                                type="number"
                                                className="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                                value={opsBackupSettings.backup_retention_days}
                                                onChange={(e) => setOpsBackupSettings((prev) => ({ ...prev, backup_retention_days: e.target.value }))}
                                            />
                                        </div>
                                        <label className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                checked={opsBackupSettings.backup_keep_local}
                                                onChange={(e) => setOpsBackupSettings((prev) => ({ ...prev, backup_keep_local: e.target.checked }))}
                                            />
                                            Keep local copy
                                        </label>
                                        <div className="md:col-span-2">
                                            <label className="text-[11px] uppercase text-gray-500">S3 prefix</label>
                                            <input
                                                className="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"
                                                value={opsBackupSettings.backup_s3_prefix}
                                                onChange={(e) => setOpsBackupSettings((prev) => ({ ...prev, backup_s3_prefix: e.target.value }))}
                                                placeholder="tastypanel/tenant-backups"
                                            />
                                        </div>
                                    </div>

                                    <div className="flex justify-end">
                                        <button
                                            onClick={saveOpsBackupSettings}
                                            disabled={opsBackupSaving}
                                            className="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-200 hover:bg-white disabled:opacity-60"
                                        >
                                            {opsBackupSaving ? 'Saving...' : 'Save settings'}
                                        </button>
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between text-[11px] uppercase text-gray-500">
                                            <span>Recent backups</span>
                                            <button
                                                onClick={() => opsTenant && loadOpsBackups(opsTenant.id)}
                                                className="text-xs font-semibold text-gray-600 hover:text-gray-800"
                                            >
                                                Refresh
                                            </button>
                                        </div>
                                        {opsBackupLoading ? (
                                            <p className="text-xs text-gray-500">Loading backups...</p>
                                        ) : opsBackups.length === 0 ? (
                                            <p className="text-xs text-gray-500">No backups yet.</p>
                                        ) : (
                                            <div className="space-y-2">
                                                {opsBackups.map((backup) => (
                                                    <div key={backup.id} className="rounded-lg border border-gray-200 bg-white p-3 text-xs">
                                                        <div className="flex items-center justify-between">
                                                            <span className="font-semibold text-gray-700">
                                                                {backup.status} • {backup.type}
                                                            </span>
                                                            <span className="text-[11px] text-gray-400">
                                                                {backup.finished_at ? new Date(backup.finished_at).toLocaleString() : '—'}
                                                            </span>
                                                        </div>
                                                        <div className="mt-1 text-[11px] text-gray-500">
                                                            {formatBytes(backup.size_bytes)} • {backup.disk || 'local'}
                                                        </div>
                                                        <div className="mt-2 flex flex-wrap gap-2">
                                                            <button
                                                                onClick={() => downloadOpsBackup(backup.id)}
                                                                className="text-xs font-semibold text-blue-600 hover:text-blue-700"
                                                            >
                                                                Download
                                                            </button>
                                                            <button
                                                                onClick={() => restoreOpsBackup(backup.id)}
                                                                className="text-xs font-semibold text-rose-600 hover:text-rose-700"
                                                            >
                                                                Restore
                                                            </button>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-6">
                                    <div className="rounded-2xl border border-gray-200 bg-gray-50 p-5 space-y-3">
                                        <div className="flex items-center justify-between">
                                            <h4 className="text-sm font-semibold text-gray-800">Queue Manager</h4>
                                            <button
                                                onClick={() => opsTenant && loadOpsQueue(opsTenant.id)}
                                                className="text-xs font-semibold text-gray-600 hover:text-gray-800"
                                            >
                                                Refresh
                                            </button>
                                        </div>
                                        <div className="grid gap-2 text-xs text-gray-600 md:grid-cols-2">
                                            <div>Failed jobs: {opsQueueStats?.failed ?? '—'}</div>
                                            <div>Pending jobs: {opsQueueStats?.pending ?? '—'}</div>
                                        </div>
                                        {opsQueueStats?.error && (
                                            <div className="text-xs text-rose-600">{opsQueueStats.error}</div>
                                        )}
                                        <div className="flex flex-wrap gap-2">
                                            <button
                                                onClick={() => runQueueAction('restart')}
                                                disabled={opsQueueAction === 'restart'}
                                                className="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold hover:bg-white disabled:opacity-60"
                                            >
                                                {opsQueueAction === 'restart' ? 'Restarting...' : 'Restart workers'}
                                            </button>
                                            <button
                                                onClick={() => runQueueAction('flush')}
                                                disabled={opsQueueAction === 'flush'}
                                                className="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold hover:bg-white disabled:opacity-60"
                                            >
                                                {opsQueueAction === 'flush' ? 'Flushing...' : 'Flush failed'}
                                            </button>
                                            <button
                                                onClick={() => runQueueAction('retry')}
                                                disabled={opsQueueAction === 'retry'}
                                                className="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold hover:bg-white disabled:opacity-60"
                                            >
                                                {opsQueueAction === 'retry' ? 'Retrying...' : 'Retry failed'}
                                            </button>
                                        </div>
                                    </div>

                                    <div className="rounded-2xl border border-gray-200 bg-gray-50 p-5 space-y-3">
                                        <div className="flex items-center justify-between">
                                            <h4 className="text-sm font-semibold text-gray-800">Logs</h4>
                                            <button
                                                onClick={loadOpsLogs}
                                                disabled={opsLogLoading}
                                                className="text-xs font-semibold text-gray-600 hover:text-gray-800 disabled:opacity-60"
                                            >
                                                {opsLogLoading ? 'Loading...' : 'Load'}
                                            </button>
                                        </div>
                                        <div className="flex flex-wrap gap-2 text-xs">
                                            <select
                                                value={opsLogType}
                                                onChange={(e) => setOpsLogType(e.target.value)}
                                                className="rounded-lg border border-gray-200 px-3 py-2"
                                            >
                                                <option value="laravel">Laravel</option>
                                                <option value="php_fpm">PHP-FPM</option>
                                                <option value="domain_access">Domain access</option>
                                                <option value="domain_error">Domain error</option>
                                            </select>
                                            {(opsLogType === 'domain_access' || opsLogType === 'domain_error') && (
                                                <select
                                                    value={opsLogDomainId}
                                                    onChange={(e) => setOpsLogDomainId(e.target.value)}
                                                    className="rounded-lg border border-gray-200 px-3 py-2"
                                                >
                                                    {(opsTenant?.domains || []).map((domain) => (
                                                        <option key={domain.id} value={domain.id}>{domain.hostname}</option>
                                                    ))}
                                                </select>
                                            )}
                                            <input
                                                type="number"
                                                value={opsLogCount}
                                                onChange={(e) => setOpsLogCount(Number(e.target.value))}
                                                className="w-24 rounded-lg border border-gray-200 px-3 py-2"
                                            />
                                        </div>
                                        <pre className="max-h-64 overflow-auto rounded-lg bg-gray-900 text-green-200 text-[11px] p-3 whitespace-pre-wrap">
                                            {opsLogLines || 'No logs loaded.'}
                                        </pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
