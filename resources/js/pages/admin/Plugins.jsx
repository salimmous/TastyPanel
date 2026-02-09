import { useEffect, useState } from 'react';
import { api } from '../../services/api';
import { isSuperadmin } from '../../utils/permissions';
import { PlugZap, Plus, Trash2, Save } from 'lucide-react';

const emptyForm = {
    key: '',
    name: '',
    description: '',
    config: '{}',
    is_active: false,
};

export default function Plugins() {
    const [plugins, setPlugins] = useState([]);
    const [form, setForm] = useState(emptyForm);
    const [drafts, setDrafts] = useState({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [currentUser, setCurrentUser] = useState(null);

    useEffect(() => {
        loadUser();
        loadPlugins();
    }, []);

    useEffect(() => {
        const next = {};
        plugins.forEach((plugin) => {
            next[plugin.id] = {
                name: plugin.name || '',
                description: plugin.description || '',
                config: JSON.stringify(plugin.config || {}, null, 2),
                is_active: !!plugin.is_active,
            };
        });
        setDrafts(next);
    }, [plugins]);

    const loadUser = async () => {
        try {
            const response = await api.admin.getUser();
            setCurrentUser(response?.user || null);
        } catch {
            setCurrentUser(null);
        }
    };

    const loadPlugins = async () => {
        setLoading(true);
        try {
            const res = await api.admin.getPlugins();
            setPlugins(res?.data || []);
        } finally {
            setLoading(false);
        }
    };

    const handleFormChange = (field) => (event) => {
        const value = field === 'is_active' ? event.target.checked : event.target.value;
        setForm((prev) => ({ ...prev, [field]: value }));
    };

    const handleDraftChange = (id, field) => (event) => {
        const value = field === 'is_active' ? event.target.checked : event.target.value;
        setDrafts((prev) => ({
            ...prev,
            [id]: { ...prev[id], [field]: value },
        }));
    };

    const createPlugin = async (event) => {
        event.preventDefault();
        if (!form.key || !form.name) return;
        setSaving(true);
        try {
            const config = form.config ? JSON.parse(form.config) : null;
            await api.admin.createPlugin({
                key: form.key.trim(),
                name: form.name.trim(),
                description: form.description.trim() || null,
                config,
                is_active: form.is_active,
            });
            setForm(emptyForm);
            await loadPlugins();
        } catch (err) {
            alert('Invalid JSON config.');
        } finally {
            setSaving(false);
        }
    };

    const savePlugin = async (id) => {
        const draft = drafts[id];
        if (!draft) return;
        try {
            const config = draft.config ? JSON.parse(draft.config) : null;
            await api.admin.updatePlugin(id, {
                name: draft.name,
                description: draft.description,
                config,
                is_active: draft.is_active,
            });
            await loadPlugins();
        } catch {
            alert('Invalid JSON config.');
        }
    };

    const deletePlugin = async (id) => {
        if (!window.confirm('Delete this plugin?')) return;
        await api.admin.deletePlugin(id);
        await loadPlugins();
    };

    if (!isSuperadmin(currentUser)) {
        return (
            <div className="p-6 text-sm text-gray-500">
                Only superadmins can manage plugins.
            </div>
        );
    }

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Plugins</h1>
                    <p className="text-sm text-gray-500">Enable modules and store plugin configuration.</p>
                </div>
                <div className="flex items-center gap-2 text-sm text-gray-500">
                    <PlugZap className="w-4 h-4" />
                    {plugins.length} plugins
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 className="text-lg font-semibold text-gray-900 mb-4">Create plugin</h2>
                <form className="space-y-3" onSubmit={createPlugin}>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input
                            className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                            placeholder="Key (unique)"
                            value={form.key}
                            onChange={handleFormChange('key')}
                        />
                        <input
                            className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                            placeholder="Name"
                            value={form.name}
                            onChange={handleFormChange('name')}
                        />
                    </div>
                    <input
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                        placeholder="Description"
                        value={form.description}
                        onChange={handleFormChange('description')}
                    />
                    <textarea
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm font-mono"
                        rows={4}
                        placeholder='{"enabled": true}'
                        value={form.config}
                        onChange={handleFormChange('config')}
                    />
                    <label className="inline-flex items-center gap-2 text-sm text-gray-600">
                        <input
                            type="checkbox"
                            checked={form.is_active}
                            onChange={handleFormChange('is_active')}
                            className="rounded border-gray-300"
                        />
                        Active
                    </label>
                    <button
                        type="submit"
                        disabled={saving}
                        className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold"
                    >
                        <Plus className="w-4 h-4" />
                        {saving ? 'Saving...' : 'Add plugin'}
                    </button>
                </form>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 className="text-lg font-semibold text-gray-900 mb-4">Installed plugins</h2>
                {loading ? (
                    <p className="text-sm text-gray-500">Loading plugins...</p>
                ) : plugins.length === 0 ? (
                    <p className="text-sm text-gray-500">No plugins yet.</p>
                ) : (
                    <div className="space-y-3">
                        {plugins.map((plugin) => {
                            const draft = drafts[plugin.id] || {};
                            return (
                                <div key={plugin.id} className="border border-gray-100 rounded-xl p-4 space-y-3">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="font-semibold text-gray-900">{plugin.key}</p>
                                            <p className="text-xs text-gray-400">{plugin.name}</p>
                                        </div>
                                        <label className="inline-flex items-center gap-2 text-xs text-gray-600">
                                            <input
                                                type="checkbox"
                                                checked={!!draft.is_active}
                                                onChange={handleDraftChange(plugin.id, 'is_active')}
                                                className="rounded border-gray-300"
                                            />
                                            Active
                                        </label>
                                    </div>
                                    <input
                                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                        value={draft.name || ''}
                                        onChange={handleDraftChange(plugin.id, 'name')}
                                        placeholder="Name"
                                    />
                                    <input
                                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                        value={draft.description || ''}
                                        onChange={handleDraftChange(plugin.id, 'description')}
                                        placeholder="Description"
                                    />
                                    <textarea
                                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm font-mono"
                                        rows={4}
                                        value={draft.config || '{}'}
                                        onChange={handleDraftChange(plugin.id, 'config')}
                                    />
                                    <div className="flex items-center gap-2">
                                        <button
                                            onClick={() => savePlugin(plugin.id)}
                                            className="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-900 text-white text-xs"
                                        >
                                            <Save className="w-3 h-3" />
                                            Save
                                        </button>
                                        <button
                                            onClick={() => deletePlugin(plugin.id)}
                                            className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-red-200 text-red-600 text-xs"
                                        >
                                            <Trash2 className="w-3 h-3" />
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </div>
    );
}
