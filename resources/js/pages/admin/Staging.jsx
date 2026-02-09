import { useEffect, useMemo, useState } from 'react';
import { api } from '../../services/api';
import { Layers, RefreshCw, UploadCloud, ArrowRightCircle } from 'lucide-react';

export default function Staging() {
    const [data, setData] = useState(null);
    const [themes, setThemes] = useState([]);
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [domainForm, setDomainForm] = useState({ hostname: '', zone_id: '' });
    const [stagingThemeId, setStagingThemeId] = useState('');
    const [productionThemeId, setProductionThemeId] = useState('');
    const [snapshots, setSnapshots] = useState([]);
    const [snapshotEnv, setSnapshotEnv] = useState('production');
    const [snapshotsLoading, setSnapshotsLoading] = useState(false);

    const tenantId = useMemo(() => {
        if (typeof window === 'undefined') return null;
        return window.localStorage.getItem('adminTenantId');
    }, []);

    const loadData = async () => {
        setLoading(true);
        setError('');
        try {
            const [stagingRes, themesRes] = await Promise.all([
                api.admin.getStaging(),
                api.admin.getThemes(),
            ]);
            setData(stagingRes?.data || null);
            setThemes(themesRes?.data || []);
            setStagingThemeId(String(stagingRes?.data?.staging?.theme?.id || ''));
            setProductionThemeId(String(stagingRes?.data?.production?.theme?.id || ''));
        } catch {
            setError('Unable to load staging data. Select a tenant first.');
        } finally {
            setLoading(false);
        }
    };

    const loadSnapshots = async (env = snapshotEnv) => {
        setSnapshotsLoading(true);
        try {
            const res = await api.admin.getContentSnapshots(env);
            setSnapshots(res?.data || []);
        } catch {
            setSnapshots([]);
        } finally {
            setSnapshotsLoading(false);
        }
    };

    useEffect(() => {
        loadData();
    }, []);

    useEffect(() => {
        loadSnapshots(snapshotEnv);
    }, [snapshotEnv]);

    const enableStaging = async () => {
        setMessage('');
        setError('');
        try {
            await api.admin.enableStaging();
            setMessage('Staging enabled.');
            await loadData();
        } catch {
            setError('Failed to enable staging.');
        }
    };

    const syncStaging = async () => {
        setMessage('');
        setError('');
        try {
            await api.admin.syncStaging();
            setMessage('Production synced to staging.');
            await loadData();
        } catch {
            setError('Sync failed.');
        }
    };

    const promoteStaging = async () => {
        setMessage('');
        setError('');
        try {
            await api.admin.promoteStaging();
            setMessage('Staging promoted to production.');
            await loadData();
        } catch {
            setError('Promote failed.');
        }
    };

    const saveThemes = async () => {
        setMessage('');
        setError('');
        try {
            await api.admin.updateStaging({
                production_theme_id: productionThemeId || null,
                staging_theme_id: stagingThemeId || null,
            });
            setMessage('Themes updated.');
            await loadData();
        } catch {
            setError('Failed to update themes.');
        }
    };

    const addStagingDomain = async () => {
        if (!domainForm.hostname) return;
        if (!data?.tenant?.id) return;
        setMessage('');
        setError('');
        try {
            await api.admin.addTenantDomain(data.tenant.id, {
                hostname: domainForm.hostname,
                zone_id: domainForm.zone_id || null,
                environment: 'staging',
            });
            setDomainForm({ hostname: '', zone_id: '' });
            await loadData();
            setMessage('Staging domain added.');
        } catch {
            setError('Failed to add staging domain.');
        }
    };

    const createSnapshot = async () => {
        const label = window.prompt('Snapshot label (optional)');
        setError('');
        try {
            await api.admin.createContentSnapshot({
                environment: snapshotEnv,
                label: label || null,
            });
            setMessage('Snapshot created.');
            await loadSnapshots(snapshotEnv);
        } catch {
            setError('Failed to create snapshot.');
        }
    };

    const restoreSnapshot = async (snapshotId, targetEnv) => {
        const confirmText = targetEnv === 'production'
            ? 'RESTORE-PROD'
            : targetEnv === 'preview'
                ? 'RESTORE-PREVIEW'
                : 'RESTORE-STAGING';
        const confirm = window.prompt(`Type ${confirmText} to restore snapshot to ${targetEnv}.`);
        if (confirm !== confirmText) return;
        setError('');
        try {
            await api.admin.restoreContentSnapshot(snapshotId, targetEnv);
            setMessage(`Snapshot restored to ${targetEnv}.`);
            await loadData();
        } catch {
            setError('Failed to restore snapshot.');
        }
    };

    const deleteSnapshot = async (snapshotId) => {
        if (!window.confirm('Delete this snapshot?')) return;
        setError('');
        try {
            await api.admin.deleteContentSnapshot(snapshotId);
            await loadSnapshots(snapshotEnv);
        } catch {
            setError('Failed to delete snapshot.');
        }
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Staging</h1>
                    <p className="text-sm text-gray-500">Preview theme and settings before pushing to production.</p>
                </div>
                <button
                    onClick={loadData}
                    className="inline-flex items-center gap-2 text-sm px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50"
                >
                    <RefreshCw className="w-4 h-4" />
                    Refresh
                </button>
            </div>

            {tenantId === 'all' && (
                <div className="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-lg text-sm">
                    Select a tenant from the header to manage staging.
                </div>
            )}

            {(message || error) && (
                <div className={`text-sm ${error ? 'text-rose-600' : 'text-emerald-600'}`}>
                    {error || message}
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                    <div className="flex items-center gap-2 text-gray-700">
                        <Layers className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Staging Status</h2>
                    </div>
                    {loading ? (
                        <p className="text-sm text-gray-500">Loading...</p>
                    ) : (
                        <>
                            <p className="text-sm text-gray-600">
                                Status: <span className="font-semibold">{data?.tenant?.staging_enabled ? 'Enabled' : 'Disabled'}</span>
                            </p>
                            <div className="flex flex-wrap gap-2">
                                <button
                                    onClick={enableStaging}
                                    className="px-3 py-2 rounded-lg bg-gray-900 text-white text-sm"
                                >
                                    Enable staging
                                </button>
                                <button
                                    onClick={syncStaging}
                                    className="px-3 py-2 rounded-lg border border-gray-200 text-sm"
                                >
                                    Sync from production
                                </button>
                                <button
                                    onClick={promoteStaging}
                                    className="px-3 py-2 rounded-lg border border-gray-200 text-sm"
                                >
                                    Promote to production
                                </button>
                            </div>
                        </>
                    )}
                </div>

                <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                    <div className="flex items-center gap-2 text-gray-700">
                        <UploadCloud className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Themes</h2>
                    </div>
                    <div className="space-y-3 text-sm">
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Production Theme</label>
                            <select
                                value={productionThemeId}
                                onChange={(e) => setProductionThemeId(e.target.value)}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                            >
                                <option value="">Select theme</option>
                                {themes.map((theme) => (
                                    <option key={theme.id} value={theme.id}>{theme.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Staging Theme</label>
                            <select
                                value={stagingThemeId}
                                onChange={(e) => setStagingThemeId(e.target.value)}
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                            >
                                <option value="">Select theme</option>
                                {themes.map((theme) => (
                                    <option key={theme.id} value={theme.id}>{theme.name}</option>
                                ))}
                            </select>
                        </div>
                        <button
                            onClick={saveThemes}
                            className="px-3 py-2 rounded-lg bg-gray-900 text-white text-sm"
                        >
                            Save themes
                        </button>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div className="flex items-center gap-2 text-gray-700">
                    <ArrowRightCircle className="w-4 h-4" />
                    <h2 className="text-sm font-semibold">Content Snapshots</h2>
                </div>
                <div className="flex flex-wrap items-center gap-3 text-sm">
                    <select
                        value={snapshotEnv}
                        onChange={(e) => setSnapshotEnv(e.target.value)}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                    >
                        <option value="production">Production</option>
                        <option value="staging">Staging</option>
                        <option value="preview">Preview</option>
                    </select>
                    <button onClick={createSnapshot} className="px-3 py-2 rounded-lg bg-gray-900 text-white">
                        Create snapshot
                    </button>
                </div>

                {snapshotsLoading ? (
                    <p className="text-sm text-gray-500">Loading snapshots...</p>
                ) : snapshots.length ? (
                    <div className="space-y-2 text-sm">
                        {snapshots.map((snapshot) => (
                            <div key={snapshot.id} className="border border-gray-100 rounded-lg px-3 py-2 flex flex-col gap-2">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="font-medium text-gray-800">{snapshot.label || `Snapshot #${snapshot.id}`}</p>
                                        <p className="text-xs text-gray-400">{snapshot.created_at}</p>
                                    </div>
                                    <div className="text-xs text-gray-500">
                                        {snapshot.total_categories} cats • {snapshot.total_recipes} recipes • {snapshot.total_articles} articles
                                    </div>
                                </div>
                                <div className="flex flex-wrap gap-2 text-xs">
                                    <button
                                        onClick={() => restoreSnapshot(snapshot.id, 'staging')}
                                        className="px-2 py-1 rounded-full bg-amber-100 text-amber-700"
                                    >
                                        Restore to staging
                                    </button>
                                    <button
                                        onClick={() => restoreSnapshot(snapshot.id, 'preview')}
                                        className="px-2 py-1 rounded-full bg-blue-100 text-blue-700"
                                    >
                                        Restore to preview
                                    </button>
                                    <button
                                        onClick={() => restoreSnapshot(snapshot.id, 'production')}
                                        className="px-2 py-1 rounded-full bg-rose-100 text-rose-700"
                                    >
                                        Restore to production
                                    </button>
                                    <button
                                        onClick={() => deleteSnapshot(snapshot.id)}
                                        className="px-2 py-1 rounded-full bg-gray-100 text-gray-600"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <p className="text-sm text-gray-500">No snapshots yet.</p>
                )}
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div className="flex items-center gap-2 text-gray-700">
                    <ArrowRightCircle className="w-4 h-4" />
                    <h2 className="text-sm font-semibold">Staging Domains</h2>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto] gap-3 text-sm">
                    <input
                        value={domainForm.hostname}
                        onChange={(e) => setDomainForm((prev) => ({ ...prev, hostname: e.target.value }))}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="staging.example.com"
                    />
                    <input
                        value={domainForm.zone_id}
                        onChange={(e) => setDomainForm((prev) => ({ ...prev, zone_id: e.target.value }))}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Cloudflare zone id (optional)"
                    />
                    <button onClick={addStagingDomain} className="px-3 py-2 rounded-lg bg-gray-900 text-white">
                        Add
                    </button>
                </div>

                {data?.staging?.domains?.length ? (
                    <div className="space-y-2 text-sm">
                        {data.staging.domains.map((domain) => (
                            <div key={domain.id} className="border border-gray-100 rounded-lg px-3 py-2 flex items-center justify-between">
                                <div>
                                    <p className="font-medium text-gray-800">{domain.hostname}</p>
                                    <p className="text-xs text-gray-400">Status: {domain.status}</p>
                                </div>
                                <span className="text-xs text-gray-500">Staging</span>
                            </div>
                        ))}
                    </div>
                ) : (
                    <p className="text-sm text-gray-500">No staging domains yet.</p>
                )}
            </div>
        </div>
    );
}
