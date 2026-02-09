const API_BASE_URL = '/api';

// Helper function for authenticated requests
const authFetch = async (url, options = {}) => {
    const tenantId = typeof window !== 'undefined' ? window.localStorage.getItem('adminTenantId') : null;
    const environment = typeof window !== 'undefined' ? window.localStorage.getItem('adminEnvironment') : null;
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(tenantId ? { 'X-Tenant-ID': tenantId } : {}),
            ...(environment ? { 'X-Environment': environment } : {}),
        },
        credentials: 'include', // Important for session cookies
    };

    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers,
        },
    };

    const response = await fetch(url, mergedOptions);
    
    // Don't redirect to login if we're already on the login page or if it's a getUser call
    const isLoginPage = window.location.pathname.includes('/login');
    const isGetUserCall = url.includes('/admin/user');
    
    if (response.status === 401 && !isLoginPage && !isGetUserCall) {
        // Unauthorized - redirect to login (only if not already on login page and not getUser)
        window.location.href = '/login';
        throw new Error('Unauthorized');
    }

    return response;
};

const authUpload = async (url, formData) => {
    const tenantId = typeof window !== 'undefined' ? window.localStorage.getItem('adminTenantId') : null;
    const environment = typeof window !== 'undefined' ? window.localStorage.getItem('adminEnvironment') : null;
    const response = await fetch(url, {
        method: 'POST',
        body: formData,
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(tenantId ? { 'X-Tenant-ID': tenantId } : {}),
            ...(environment ? { 'X-Environment': environment } : {}),
        },
    });
    return response;
};

export const api = {
    // Admin Authentication
    admin: {
        getSetupStatus: async () => {
            const response = await authFetch(`${API_BASE_URL}/admin/setup`, {
                method: 'GET',
            });
            return response.json();
        },
        createInitialAdmin: async (data) => {
            const response = await authFetch(`${API_BASE_URL}/admin/setup`, {
                method: 'POST',
                body: JSON.stringify(data),
            });
            return response.json();
        },
        login: async (email, password, remember = false) => {
            const response = await authFetch(`${API_BASE_URL}/admin/login`, {
                method: 'POST',
                body: JSON.stringify({ email, password, remember }),
            });
            return response.json();
        },
        getSsoStatus: async () => {
            const response = await authFetch(`${API_BASE_URL}/admin/sso/status`, {
                method: 'GET',
            });
            return response.json();
        },
        requestTwoFactor: async () => {
            const response = await authFetch(`${API_BASE_URL}/admin/2fa/request`, {
                method: 'POST',
            });
            return response.json();
        },
        verifyTwoFactor: async (code) => {
            const response = await authFetch(`${API_BASE_URL}/admin/2fa/verify`, {
                method: 'POST',
                body: JSON.stringify({ code }),
            });
            return response.json();
        },

        logout: async () => {
            const response = await authFetch(`${API_BASE_URL}/admin/logout`, {
                method: 'POST',
            });
            return response.json();
        },

        getUser: async () => {
            try {
                const response = await authFetch(`${API_BASE_URL}/admin/user`, {
                    method: 'GET',
                });
                
                // If response is not ok (401, etc.), return null user
                if (!response.ok) {
                    return { user: null };
                }
                
                const data = await response.json();
                return data;
            } catch (error) {
                // Return null user if not authenticated (don't throw)
                // This prevents console errors for expected 401 responses
                return { user: null };
            }
        },
        getBranding: async () => {
            const response = await fetch(`${API_BASE_URL}/admin/branding`);
            return response.json();
        },
        updatePassword: async (currentPassword, newPassword, confirmPassword) => {
            const response = await authFetch(`${API_BASE_URL}/admin/user/password`, {
                method: 'POST',
                body: JSON.stringify({
                    current_password: currentPassword,
                    password: newPassword,
                    password_confirmation: confirmPassword,
                }),
            });
            return response.json();
        },

               getDashboardStats: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/dashboard/stats`);
                   return response.json();
               },

               // Themes + Tenants
               getThemes: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/themes`);
                   return response.json();
               },
               createTheme: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/themes`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               uploadTheme: async (formData) => {
                   const response = await authUpload(`${API_BASE_URL}/admin/themes/upload`, formData);
                   return response.json();
               },
               updateTheme: async (id, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/themes/${id}`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               getThemeVersions: async (themeId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/themes/${themeId}/versions`);
                   return response.json();
               },
               uploadThemeVersion: async (themeId, formData) => {
                   const response = await authUpload(`${API_BASE_URL}/admin/themes/${themeId}/versions`, formData);
                   return response.json();
               },
               restoreThemeVersion: async (themeId, versionId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/themes/${themeId}/versions/${versionId}/restore`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               getMarketplaceThemes: async (params = {}) => {
                   const query = new URLSearchParams();
                   if (params.search) query.set('search', params.search);
                   if (params.category) query.set('category', params.category);
                   const suffix = query.toString() ? `?${query.toString()}` : '';
                   const response = await authFetch(`${API_BASE_URL}/admin/marketplace/themes${suffix}`);
                   return response.json();
               },
               installMarketplaceTheme: async (themeId, tenantId = null) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/marketplace/themes/${themeId}/install`, {
                       method: 'POST',
                       body: tenantId ? JSON.stringify({ tenant_id: tenantId }) : null,
                   });
                   return response.json();
               },
               getPlugins: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/plugins`);
                   return response.json();
               },
               createPlugin: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/plugins`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               updatePlugin: async (id, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/plugins/${id}`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               deletePlugin: async (id) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/plugins/${id}`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },
               getStaging: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/staging`);
                   return response.json();
               },
               getPreview: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/preview`);
                   return response.json();
               },
               getAutomationSettings: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/automation/settings`);
                   return response.json();
               },
               updateAutomationSettings: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/automation/settings`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               testAutomationProvider: async (provider) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/automation/test`, {
                       method: 'POST',
                       body: JSON.stringify({ provider }),
                   });
                   return response.json();
               },
               getCanvaConnectUrl: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/automation/canva/connect`);
                   return response.json();
               },
               createAiDraft: async (payload) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/automation/draft`, {
                       method: 'POST',
                       body: JSON.stringify(payload),
                   });
                   return response.json();
               },
               getAutomationRuns: async (limit = 10) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/automation/runs?limit=${limit}`);
                   return response.json();
               },
               runAutomation: async (payload = {}) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/automation/run`, {
                       method: 'POST',
                       body: JSON.stringify(payload),
                   });
                   return response.json();
               },
               enableStaging: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/staging/enable`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               enablePreview: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/preview/enable`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               syncStaging: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/staging/sync`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               syncPreview: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/preview/sync`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               promoteStaging: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/staging/promote`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               promotePreview: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/preview/promote`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               updateStaging: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/staging`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               updatePreview: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/preview`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               getContentSnapshots: async (environment = 'production', limit = 20) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/staging/snapshots?environment=${encodeURIComponent(environment)}&limit=${limit}`);
                   return response.json();
               },
               createContentSnapshot: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/staging/snapshots`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               restoreContentSnapshot: async (snapshotId, targetEnvironment) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/staging/snapshots/${snapshotId}/restore`, {
                       method: 'POST',
                       body: JSON.stringify({ target_environment: targetEnvironment }),
                   });
                   return response.json();
               },
               deleteContentSnapshot: async (snapshotId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/staging/snapshots/${snapshotId}`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },
               listFiles: async (path = '') => {
                   const query = path ? `?path=${encodeURIComponent(path)}` : '';
                   const response = await authFetch(`${API_BASE_URL}/admin/files${query}`);
                   return response.json();
               },
               uploadFiles: async (formData) => {
                   const response = await authUpload(`${API_BASE_URL}/admin/files/upload`, formData);
                   return response.json();
               },
               createFolder: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/files/folder`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               renameFile: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/files/rename`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               deleteFile: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/files`, {
                       method: 'DELETE',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               downloadFileUrl: (path) => {
                   const tenantId = typeof window !== 'undefined' ? window.localStorage.getItem('adminTenantId') : null;
                   const tenantParam = tenantId && tenantId !== 'all' ? `&tenant_id=${encodeURIComponent(tenantId)}` : '';
                   return `${API_BASE_URL}/admin/files/download?path=${encodeURIComponent(path)}${tenantParam}`;
               },
               getTenants: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants?scope=all`);
                   return response.json();
               },
               getTenantBlueprints: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/blueprints`);
                   return response.json();
               },
               cloneTenant: async (tenantId, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/clone`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               archiveTenant: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/archive`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               unarchiveTenant: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/unarchive`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               getTenantBackups: async (tenantId, limit = 10) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/backups?limit=${limit}`);
                   return response.json();
               },
               createTenantBackup: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/backups`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               updateTenantBackupSettings: async (tenantId, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/backups/settings`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               restoreTenantBackup: async (tenantId, backupId, confirm) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/backups/${backupId}/restore`, {
                       method: 'POST',
                       body: JSON.stringify({ confirm }),
                   });
                   return response.json();
               },
               downloadTenantBackupUrl: (tenantId, backupId) => {
                   return `${API_BASE_URL}/admin/tenants/${tenantId}/backups/${backupId}/download`;
               },
               getTenantQueue: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/queue`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               restartTenantQueue: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/queue/restart`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               flushTenantQueue: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/queue/flush-failed`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               retryTenantQueue: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/queue/retry-failed`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               getTenantLogMeta: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/logs/meta`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               tailTenantLogs: async (tenantId, params) => {
                   const query = new URLSearchParams(params);
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/logs/tail?${query.toString()}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               provisionTenantInstance: async (tenantId, domainId = null) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/instance`, {
                       method: 'POST',
                       body: JSON.stringify(domainId ? { domain_id: domainId } : {}),
                   });
                   return response.json();
               },
               getTenantProvisioningJobs: async (tenantId, limit = 20) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/provisioning-jobs?limit=${limit}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               retryTenantProvisioning: async (tenantId, domainId = null) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/provisioning/retry`, {
                       method: 'POST',
                       body: JSON.stringify(domainId ? { domain_id: domainId } : {}),
                   });
                   return response.json();
               },
               rollbackTenantProvisioning: async (tenantId, domainId = null) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/provisioning/rollback`, {
                       method: 'POST',
                       body: JSON.stringify(domainId ? { domain_id: domainId } : {}),
                   });
                   return response.json();
               },
               getTenantAccessInfo: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/access`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               provisionTenantAccess: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/access/provision`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               rotateTenantAccessPassword: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/access/password`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               installTenantAccessKey: async (tenantId, publicKey) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/access/key`, {
                       method: 'POST',
                       body: JSON.stringify({ public_key: publicKey }),
                   });
                   return response.json();
               },
               getTenantSecurityProfile: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/security-profile`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               updateTenantSecurityProfile: async (tenantId, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/security-profile`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               getTenantMailSettings: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/mail/settings`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               updateTenantMailSettings: async (tenantId, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/mail/settings`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               testTenantMail: async (tenantId, toEmail) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/mail/test`, {
                       method: 'POST',
                       body: JSON.stringify({ to_email: toEmail }),
                   });
                   return response.json();
               },
               listTenantMailboxes: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/mailboxes`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               createTenantMailbox: async (tenantId, payload) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/mailboxes`, {
                       method: 'POST',
                       body: JSON.stringify(payload),
                   });
                   return response.json();
               },
               resetTenantMailboxPassword: async (tenantId, mailboxId, password = null) => {
                   const body = password ? { password } : {};
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/mailboxes/${mailboxId}/password`, {
                       method: 'POST',
                       body: JSON.stringify(body),
                   });
                   return response.json();
               },
               refreshTenantMailboxUsage: async (tenantId, mailboxId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/mailboxes/${mailboxId}/usage`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               deleteTenantMailbox: async (tenantId, mailboxId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/mailboxes/${mailboxId}`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },
               listTenantMailEvents: async (tenantId, limit = 50) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/mail/events?limit=${limit}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               listTenantSecrets: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/secrets`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               storeTenantSecret: async (tenantId, payload) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/secrets`, {
                       method: 'POST',
                       body: JSON.stringify(payload),
                   });
                   return response.json();
               },
               deleteTenantSecret: async (tenantId, secretKey, confirm = 'DELETE_SECRET') => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/secrets/${encodeURIComponent(secretKey)}`, {
                       method: 'DELETE',
                       body: JSON.stringify({ confirm }),
                   });
                   return response.json();
               },
               syncTenantSecretToEnv: async (tenantId, secretKey, envKey = null) => {
                   const body = envKey ? { secret_key: secretKey, env_key: envKey } : { secret_key: secretKey };
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/secrets/sync`, {
                       method: 'POST',
                       body: JSON.stringify(body),
                   });
                   return response.json();
               },
               removeTenantEnvKey: async (tenantId, envKey, confirm = 'DELETE_ENV_KEY') => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/secrets/sync`, {
                       method: 'DELETE',
                       body: JSON.stringify({ env_key: envKey, confirm }),
                   });
                   return response.json();
               },
               getTenantOrchestrationStatus: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/orchestration/status`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               runTenantOrchestration: async (tenantId, action) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/orchestration`, {
                       method: 'POST',
                       body: JSON.stringify({ action }),
                   });
                   return response.json();
               },
               purgeTenantCache: async (tenantId, domainId = null) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/cache/purge`, {
                       method: 'POST',
                       body: JSON.stringify(domainId ? { domain_id: domainId } : {}),
                   });
                   return response.json();
               },
               createTenant: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               deleteTenant: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },
               addTenantDomain: async (tenantId, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/domains`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               provisionDomain: async (domainId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/domains/${domainId}/provision`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               requestDomainSsl: async (domainId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/domains/${domainId}/ssl`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               requestDomainNginx: async (domainId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/domains/${domainId}/nginx`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               getDomainNginxConfig: async (domainId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/domains/${domainId}/nginx/config`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               updateDomainNginxConfig: async (domainId, config) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/domains/${domainId}/nginx/config`, {
                       method: 'PUT',
                       body: JSON.stringify({ config }),
                   });
                   return response.json();
               },
               resetDomainNginxConfig: async (domainId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/domains/${domainId}/nginx/config`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },
               testDomainNginxConfig: async (domainId, config) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/domains/${domainId}/nginx/test`, {
                       method: 'POST',
                       body: JSON.stringify({ config }),
                   });
                   return response.json();
               },
               getDomainNginxVersions: async (domainId, limit = 15) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/domains/${domainId}/nginx/versions?limit=${limit}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               restoreDomainNginxVersion: async (domainId, versionId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/domains/${domainId}/nginx/versions/${versionId}/restore`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               toggleDomainHttp3: async (domainId, enabled) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/domains/${domainId}/http3`, {
                       method: 'POST',
                       body: JSON.stringify({ enabled }),
                   });
                   return response.json();
               },
               checkDomainHttp3: async (domainId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/domains/${domainId}/http3/check`, {
                       method: 'POST',
                   });
                   return response.json();
               },

               // Platform
               getPlatformOverview: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/overview`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               getPlatformSettings: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/settings`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               updatePlatformSettings: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/settings`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               getQueueStatus: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/queue`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               restartQueue: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/queue/restart`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               flushFailedQueue: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/queue/flush-failed`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               getPlatformServices: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/services`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               actionPlatformService: async (service, action) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/services/${encodeURIComponent(service)}/action`, {
                       method: 'POST',
                       body: JSON.stringify({ action }),
                   });
                   return response.json();
               },
               getPlatformServiceLogs: async (service, lines = 120) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/services/${encodeURIComponent(service)}/logs?lines=${lines}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               deployNginxSafe: async (mode = 'deploy', backupPath = null) => {
                   const payload = backupPath ? { mode, backup_path: backupPath } : { mode };
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/nginx/deploy-safe`, {
                       method: 'POST',
                       body: JSON.stringify(payload),
                   });
                   return response.json();
               },
               getBackups: async (page = 1) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/backups?page=${page}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               createBackup: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/backups`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               restoreBackup: async (backupId, confirm) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/backups/${backupId}/restore`, {
                       method: 'POST',
                       body: JSON.stringify({ confirm }),
                   });
                   return response.json();
               },
               getAuditLogs: async (page = 1, search = '') => {
                   const query = new URLSearchParams({ page, search }).toString();
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/audit-logs?${query}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               getAuditExports: async (page = 1) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/audit-exports?page=${page}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               createAuditExport: async (days = null) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/audit-exports`, {
                       method: 'POST',
                       body: JSON.stringify(days ? { days } : {}),
                   });
                   return response.json();
               },
               downloadAuditExportUrl: (exportId) => `${API_BASE_URL}/admin/platform/audit-exports/${exportId}/download`,
               getPlatformAlerts: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/platform/alerts`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               getSearchStatus: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/search/status`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               testSearch: async (query, types = '') => {
                   const response = await authFetch(`${API_BASE_URL}/admin/search/test`, {
                       method: 'POST',
                       body: JSON.stringify({ query, types }),
                   });
                   return response.json();
               },
               reindexSearch: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/search/reindex`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               getLogMeta: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/logs/meta`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               tailLogs: async (params = {}) => {
                   const queryString = new URLSearchParams(params).toString();
                   const response = await authFetch(`${API_BASE_URL}/admin/logs/tail?${queryString}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               getSecurityScans: async (page = 1) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/security/scans?page=${page}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               runSecurityScan: async (path, type = 'malware') => {
                   const response = await authFetch(`${API_BASE_URL}/admin/security/scans`, {
                       method: 'POST',
                       body: JSON.stringify({ path, type }),
                   });
                   return response.json();
               },
               getSecurityBaselines: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/security/baselines`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               createSecurityBaseline: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/security/baselines`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               runIntegrityCheck: async (baselineId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/security/baselines/${baselineId}/check`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               getIntegrityChecks: async (baselineId, page = 1) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/security/baselines/${baselineId}/checks?page=${page}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               getFirewallRules: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/firewall`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               createFirewallRule: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/firewall`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               updateFirewallRule: async (id, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/firewall/${id}`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               deleteFirewallRule: async (id) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/firewall/${id}`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },
               applyFirewallRules: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/firewall/apply`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               getFirewallStatus: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/firewall/status`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               getTenantAnalytics: async (tenantId, days = 30) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/analytics?days=${days}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               getTenantRealtimeAnalytics: async (tenantId, lines = 1200) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/analytics/realtime?lines=${lines}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               getFeatureFlags: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/feature-flags`, {
                       method: 'GET',
                   });
                   return response.json();
                },
               createFeatureFlag: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/feature-flags`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
                },
               updateFeatureFlag: async (id, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/feature-flags/${id}`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
                },
               deleteFeatureFlag: async (id) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/feature-flags/${id}`, {
                       method: 'DELETE',
                   });
                   return response.json();
                },
               getTenantActivity: async (tenantId, page = 1, search = '') => {
                   const query = new URLSearchParams({ page, search }).toString();
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/activity?${query}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               getTenantUptimeChecks: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/uptime-checks`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               createTenantUptimeCheck: async (tenantId, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/uptime-checks`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               updateTenantUptimeCheck: async (tenantId, checkId, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/uptime-checks/${checkId}`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               deleteTenantUptimeCheck: async (tenantId, checkId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/uptime-checks/${checkId}`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },
               runTenantUptimeCheck: async (tenantId, checkId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/uptime-checks/${checkId}/run`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               getTenantUptimeEvents: async (tenantId, checkId, page = 1) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/uptime-checks/${checkId}/events?page=${page}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               getTenantApiKeys: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/api-keys`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               createTenantApiKey: async (tenantId, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/api-keys`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               rotateTenantApiKey: async (tenantId, apiKeyId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/api-keys/${apiKeyId}/rotate`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               revokeTenantApiKey: async (tenantId, apiKeyId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/api-keys/${apiKeyId}/revoke`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               getTenantWebhooks: async (tenantId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/webhooks`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               createTenantWebhook: async (tenantId, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/webhooks`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               updateTenantWebhook: async (tenantId, webhookId, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/webhooks/${webhookId}`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               deleteTenantWebhook: async (tenantId, webhookId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/webhooks/${webhookId}`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },
               testTenantWebhook: async (tenantId, webhookId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/webhooks/${webhookId}/test`, {
                       method: 'POST',
                   });
                   return response.json();
               },
               getTenantWebhookDeliveries: async (tenantId, webhookId, page = 1) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/webhooks/${webhookId}/deliveries?page=${page}`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               getPlans: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/plans`, {
                       method: 'GET',
                   });
                   return response.json();
               },
               createPlan: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/plans`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               updatePlan: async (id, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/plans/${id}`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               deletePlan: async (id) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/plans/${id}`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },
               assignTenantPlan: async (tenantId, planId) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/tenants/${tenantId}/plan`, {
                       method: 'POST',
                       body: JSON.stringify({ plan_id: planId }),
                   });
                   return response.json();
               },

               // Categories CRUD
               getCategories: async () => {
                   const response = await authFetch(`${API_BASE_URL}/admin/categories`);
                   return response.json();
               },
               getCategory: async (id) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/categories/${id}`);
                   return response.json();
               },
               createCategory: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/categories`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               updateCategory: async (id, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/categories/${id}`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               deleteCategory: async (id) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/categories/${id}`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },

               // Recipes CRUD
               getRecipes: async (params = {}) => {
                   const queryString = new URLSearchParams(params).toString();
                   const url = queryString ? `${API_BASE_URL}/admin/recipes?${queryString}` : `${API_BASE_URL}/admin/recipes`;
                   const response = await authFetch(url);
                   return response.json();
               },
               getRecipe: async (id) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/recipes/${id}`);
                   return response.json();
               },
               createRecipe: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/recipes`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               updateRecipe: async (id, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/recipes/${id}`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               deleteRecipe: async (id) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/recipes/${id}`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },

               // Articles CRUD
               getArticles: async (params = {}) => {
                   const queryString = new URLSearchParams(params).toString();
                   const url = queryString ? `${API_BASE_URL}/admin/articles?${queryString}` : `${API_BASE_URL}/admin/articles`;
                   const response = await authFetch(url);
                   return response.json();
               },
               getArticle: async (id) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/articles/${id}`);
                   return response.json();
               },
               createArticle: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/articles`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               updateArticle: async (id, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/articles/${id}`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               deleteArticle: async (id) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/articles/${id}`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },

               // Users CRUD
               getUsers: async (params = {}) => {
                   const queryString = new URLSearchParams(params).toString();
                   const url = queryString ? `${API_BASE_URL}/admin/users?${queryString}` : `${API_BASE_URL}/admin/users`;
                   const response = await authFetch(url);
                   return response.json();
               },
               getUser: async (id) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/users/${id}`);
                   return response.json();
               },
               createUser: async (data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/users`, {
                       method: 'POST',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               updateUser: async (id, data) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/users/${id}`, {
                       method: 'PUT',
                       body: JSON.stringify(data),
                   });
                   return response.json();
               },
               deleteUser: async (id) => {
                   const response = await authFetch(`${API_BASE_URL}/admin/users/${id}`, {
                       method: 'DELETE',
                   });
                   return response.json();
               },
           },

    // Categories
    getCategories: async () => {
        const response = await fetch(`${API_BASE_URL}/categories`);
        return response.json();
    },

    getCategory: async (slug) => {
        const response = await fetch(`${API_BASE_URL}/categories/${slug}`);
        return response.json();
    },

    // Recipes
    getRecipes: async (params = {}) => {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${API_BASE_URL}/recipes?${queryString}` : `${API_BASE_URL}/recipes`;
        const response = await fetch(url);
        return response.json();
    },

    getRecipe: async (slug) => {
        const response = await fetch(`${API_BASE_URL}/recipes/${slug}`);
        return response.json();
    },

    searchRecipes: async (searchTerm) => {
        const response = await fetch(`${API_BASE_URL}/recipes?search=${encodeURIComponent(searchTerm)}`);
        return response.json();
    },

    getRecipesByCategory: async (categorySlug) => {
        const response = await fetch(`${API_BASE_URL}/recipes?category=${categorySlug}`);
        return response.json();
    },

    // Articles
    getArticles: async () => {
        const response = await fetch(`${API_BASE_URL}/articles`);
        return response.json();
    },

    getArticle: async (slug) => {
        const response = await fetch(`${API_BASE_URL}/articles/${slug}`);
        return response.json();
    },
};

export default api;
