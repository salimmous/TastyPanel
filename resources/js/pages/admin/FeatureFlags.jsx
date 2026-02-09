import { useEffect, useState } from 'react';
import { api } from '../../services/api';

const emptyForm = {
    key: '',
    name: '',
    description: '',
    enabled: false,
    rollout_percentage: 0,
    environment: '',
    tenant_id: '',
};

export default function FeatureFlags() {
    const [flags, setFlags] = useState([]);
    const [tenants, setTenants] = useState([]);
    const [form, setForm] = useState(emptyForm);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        load();
    }, []);

    const load = async () => {
        const res = await api.admin.getFeatureFlags();
        setFlags(res?.data || []);
        setTenants(res?.tenants || []);
    };

    const submit = async (e) => {
        e.preventDefault();
        if (!form.key) return;
        setSaving(true);
        try {
            await api.admin.createFeatureFlag({
                ...form,
                tenant_id: form.tenant_id || null,
                rollout_percentage: Number(form.rollout_percentage || 0),
            });
            setForm(emptyForm);
            await load();
        } finally {
            setSaving(false);
        }
    };

    const toggle = async (flag) => {
        await api.admin.updateFeatureFlag(flag.id, { enabled: !flag.enabled });
        await load();
    };

    const remove = async (flag) => {
        if (!window.confirm('Delete flag?')) return;
        await api.admin.deleteFeatureFlag(flag.id);
        await load();
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Feature Flags</h1>
                    <p className="text-sm text-gray-500">Global or per-tenant rollout with percentage.</p>
                </div>
            </div>

            <form onSubmit={submit} className="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm space-y-3">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <input
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                        placeholder="key (e.g. ai.new_pipeline)"
                        value={form.key}
                        onChange={(e) => setForm({ ...form, key: e.target.value })}
                        required
                    />
                    <input
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                        placeholder="name"
                        value={form.name}
                        onChange={(e) => setForm({ ...form, name: e.target.value })}
                    />
                    <select
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                        value={form.tenant_id}
                        onChange={(e) => setForm({ ...form, tenant_id: e.target.value })}
                    >
                        <option value="">Global</option>
                        {tenants.map((t) => (
                            <option key={t.id} value={t.id}>{t.name}</option>
                        ))}
                    </select>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                        placeholder="environment (optional)"
                        value={form.environment}
                        onChange={(e) => setForm({ ...form, environment: e.target.value })}
                    />
                    <input
                        type="number"
                        min="0"
                        max="100"
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                        placeholder="rollout %"
                        value={form.rollout_percentage}
                        onChange={(e) => setForm({ ...form, rollout_percentage: e.target.value })}
                    />
                    <label className="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            checked={form.enabled}
                            onChange={(e) => setForm({ ...form, enabled: e.target.checked })}
                        />
                        Enabled
                    </label>
                    <button
                        type="submit"
                        className="px-3 py-2 rounded-lg bg-gray-900 text-white text-sm"
                        disabled={saving}
                    >
                        {saving ? 'Saving...' : 'Create'}
                    </button>
                </div>
                <textarea
                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                    rows={2}
                    placeholder="description"
                    value={form.description}
                    onChange={(e) => setForm({ ...form, description: e.target.value })}
                />
            </form>

            <div className="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                    {flags.map((flag) => (
                        <div key={flag.id} className="border border-gray-100 rounded-xl p-3 space-y-2">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="font-semibold text-gray-800">{flag.key}</p>
                                    <p className="text-[12px] text-gray-500">{flag.name}</p>
                                </div>
                                <button
                                    onClick={() => toggle(flag)}
                                    className={`text-xs px-2 py-1 rounded-full ${flag.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'}`}
                                >
                                    {flag.enabled ? 'On' : 'Off'}
                                </button>
                            </div>
                            <p className="text-[12px] text-gray-500">{flag.description}</p>
                            <p className="text-[12px] text-gray-500">Rollout: {flag.rollout_percentage ?? 0}%</p>
                            <p className="text-[12px] text-gray-500">
                                Scope: {flag.tenant ? flag.tenant.name : 'Global'} {flag.environment || ''}
                            </p>
                            <div className="flex justify-end">
                                <button onClick={() => remove(flag)} className="text-xs text-rose-600">Delete</button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
