import { useEffect, useState } from 'react';
import { api } from '../../services/api';
import { Key, Webhook } from 'lucide-react';

export default function Integrations() {
    const [tenants, setTenants] = useState([]);
    const [selectedTenantId, setSelectedTenantId] = useState('');
    const [isTenantMode, setIsTenantMode] = useState(false);
    const [apiKeys, setApiKeys] = useState([]);
    const [webhooks, setWebhooks] = useState([]);
    const [newKeyName, setNewKeyName] = useState('');
    const [newKeyScopes, setNewKeyScopes] = useState('');
    const [newKeyRate, setNewKeyRate] = useState('');
    const [newKeyExpires, setNewKeyExpires] = useState('');
    const [newKeyPlain, setNewKeyPlain] = useState('');
    const [webhookForm, setWebhookForm] = useState({ event: '', url: '', secret: '' });

    useEffect(() => {
        loadTenants();
    }, []);

    useEffect(() => {
        if (selectedTenantId) {
            refreshData();
        }
    }, [selectedTenantId]);

    const loadTenants = async () => {
        const user = await api.admin.getUser();
        const tenantMode = user?.user?.app_mode === 'tenant';
        setIsTenantMode(tenantMode);
        const tenantsRes = await api.admin.getTenants();
        const list = tenantsRes?.data || [];
        setTenants(list);

        if (tenantMode) {
            const tenantId = user?.user?.tenant_id || list[0]?.id;
            setSelectedTenantId(tenantId ? String(tenantId) : '');
        } else if (user?.user?.is_superadmin || user?.user?.role === 'superadmin') {
            setSelectedTenantId(list[0]?.id ? String(list[0].id) : '');
        } else if (user?.user?.tenant_id) {
            setSelectedTenantId(String(user.user.tenant_id));
        }
    };

    const refreshData = async () => {
        const [keysRes, webhooksRes] = await Promise.all([
            api.admin.getTenantApiKeys(selectedTenantId),
            api.admin.getTenantWebhooks(selectedTenantId),
        ]);
        setApiKeys(keysRes?.data || []);
        setWebhooks(webhooksRes?.data || []);
    };

    const createKey = async () => {
        if (!newKeyName) return;
        const scopes = newKeyScopes
            ? newKeyScopes.split(',').map((s) => s.trim()).filter(Boolean)
            : [];
        const res = await api.admin.createTenantApiKey(selectedTenantId, {
            name: newKeyName,
            scopes,
            rate_limit_per_minute: newKeyRate ? Number(newKeyRate) : 0,
            expires_at: newKeyExpires || null,
        });
        setNewKeyPlain(res?.plain || '');
        setNewKeyName('');
        setNewKeyScopes('');
        setNewKeyRate('');
        setNewKeyExpires('');
        await refreshData();
    };

    const revokeKey = async (id) => {
        await api.admin.revokeTenantApiKey(selectedTenantId, id);
        await refreshData();
    };

    const rotateKey = async (id) => {
        const res = await api.admin.rotateTenantApiKey(selectedTenantId, id);
        setNewKeyPlain(res?.plain || '');
        await refreshData();
    };

    const createWebhook = async () => {
        if (!webhookForm.event || !webhookForm.url) return;
        await api.admin.createTenantWebhook(selectedTenantId, webhookForm);
        setWebhookForm({ event: '', url: '', secret: '' });
        await refreshData();
    };

    const deleteWebhook = async (id) => {
        await api.admin.deleteTenantWebhook(selectedTenantId, id);
        await refreshData();
    };

    const toggleWebhook = async (webhook) => {
        await api.admin.updateTenantWebhook(selectedTenantId, webhook.id, {
            event: webhook.event,
            url: webhook.url,
            secret: webhook.secret,
            is_active: !webhook.is_active,
        });
        await refreshData();
    };

    const testWebhook = async (id) => {
        await api.admin.testTenantWebhook(selectedTenantId, id);
        await refreshData();
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">
                        {isTenantMode ? 'Integrations' : 'Tenant Integrations'}
                    </h1>
                    <p className="text-sm text-gray-500">
                        {isTenantMode ? 'API keys and webhooks for this site.' : 'API keys and webhooks per tenant.'}
                    </p>
                </div>
                {!isTenantMode && (
                    <select
                        value={selectedTenantId}
                        onChange={(e) => setSelectedTenantId(e.target.value)}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                    >
                        {tenants.map((tenant) => (
                            <option key={tenant.id} value={tenant.id}>{tenant.name}</option>
                        ))}
                    </select>
                )}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <div className="flex items-center gap-2 text-gray-700 mb-4">
                        <Key className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">API Keys</h2>
                    </div>
                    <div className="space-y-2 text-xs">
                        <input
                            value={newKeyName}
                            onChange={(e) => setNewKeyName(e.target.value)}
                            className="w-full rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="Key name"
                        />
                        <input
                            value={newKeyScopes}
                            onChange={(e) => setNewKeyScopes(e.target.value)}
                            className="w-full rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="Scopes (comma separated)"
                        />
                        <input
                            value={newKeyRate}
                            onChange={(e) => setNewKeyRate(e.target.value)}
                            className="w-full rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="Rate limit / min (0 = unlimited)"
                        />
                        <input
                            type="date"
                            value={newKeyExpires}
                            onChange={(e) => setNewKeyExpires(e.target.value)}
                            className="w-full rounded-lg border border-gray-200 px-3 py-2"
                        />
                        <button
                            onClick={createKey}
                            className="px-3 py-2 rounded-lg bg-gray-900 text-white"
                        >
                            Create API Key
                        </button>
                        {newKeyPlain ? (
                            <div className="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-[11px] text-emerald-700">
                                New key: <span className="font-mono">{newKeyPlain}</span>
                            </div>
                        ) : null}
                    </div>

                    <div className="mt-4 space-y-2 text-xs">
                        {apiKeys.map((key) => (
                            <div key={key.id} className="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                                <div>
                                    <p className="font-semibold text-gray-700">{key.name}</p>
                                    <p className="text-[11px] text-gray-500">
                                        {key.token_prefix}•••• • {key.revoked_at ? 'revoked' : 'active'} • {key.rate_limit_per_minute || 0}/min
                                    </p>
                                    <p className="text-[11px] text-gray-400">
                                        Expires: {key.expires_at || 'never'} • Last used: {key.last_used_at || 'never'}
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => rotateKey(key.id)}
                                        disabled={!!key.revoked_at}
                                        className="text-xs text-blue-600"
                                    >
                                        Rotate
                                    </button>
                                    <button
                                        onClick={() => revokeKey(key.id)}
                                        disabled={!!key.revoked_at}
                                        className="text-xs text-rose-600"
                                    >
                                        Revoke
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <div className="flex items-center gap-2 text-gray-700 mb-4">
                        <Webhook className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Webhooks</h2>
                    </div>
                    <div className="space-y-2 text-xs">
                        <input
                            value={webhookForm.event}
                            onChange={(e) => setWebhookForm({ ...webhookForm, event: e.target.value })}
                            className="w-full rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="Event (e.g. article.created, recipe.updated, *)"
                        />
                        <input
                            value={webhookForm.url}
                            onChange={(e) => setWebhookForm({ ...webhookForm, url: e.target.value })}
                            className="w-full rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="Webhook URL"
                        />
                        <input
                            value={webhookForm.secret}
                            onChange={(e) => setWebhookForm({ ...webhookForm, secret: e.target.value })}
                            className="w-full rounded-lg border border-gray-200 px-3 py-2"
                            placeholder="Secret (optional)"
                        />
                        <button
                            onClick={createWebhook}
                            className="px-3 py-2 rounded-lg bg-gray-900 text-white"
                        >
                            Add Webhook
                        </button>
                    </div>

                    <div className="mt-4 space-y-2 text-xs">
                        {webhooks.map((hook) => (
                            <div key={hook.id} className="border border-gray-100 rounded-lg px-3 py-2 space-y-2">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="font-semibold text-gray-700">{hook.event}</p>
                                        <p className="text-[11px] text-gray-500">{hook.url}</p>
                                    </div>
                                    <div className="flex gap-2">
                                        <button onClick={() => testWebhook(hook.id)} className="text-xs text-blue-600">Test</button>
                                        <button onClick={() => toggleWebhook(hook)} className="text-xs text-amber-600">{hook.is_active ? 'Disable' : 'Enable'}</button>
                                        <button onClick={() => deleteWebhook(hook.id)} className="text-xs text-rose-600">Delete</button>
                                    </div>
                                </div>
                                <p className="text-[11px] text-gray-500">Last: {hook.last_status || 'n/a'} • {hook.last_sent_at || 'never'}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
