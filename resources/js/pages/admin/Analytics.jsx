import { useEffect, useState } from 'react';
import { api } from '../../services/api';
import { Activity, Database, TrendingUp } from 'lucide-react';

export default function Analytics() {
    const [tenants, setTenants] = useState([]);
    const [selectedTenantId, setSelectedTenantId] = useState('');
    const [days, setDays] = useState(30);
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [realtime, setRealtime] = useState(null);
    const [realtimeLoading, setRealtimeLoading] = useState(false);

    useEffect(() => {
        loadTenants();
    }, []);

    useEffect(() => {
        if (selectedTenantId) {
            loadAnalytics();
        }
    }, [selectedTenantId, days]);

    useEffect(() => {
        if (!selectedTenantId) return;
        let intervalId;
        const fetchRealtime = async () => {
            setRealtimeLoading(true);
            try {
                const res = await api.admin.getTenantRealtimeAnalytics(selectedTenantId);
                setRealtime(res || null);
            } finally {
                setRealtimeLoading(false);
            }
        };
        fetchRealtime();
        intervalId = setInterval(fetchRealtime, 30000);
        return () => clearInterval(intervalId);
    }, [selectedTenantId]);

    const loadTenants = async () => {
        const user = await api.admin.getUser();
        const tenantsRes = await api.admin.getTenants();
        const list = tenantsRes?.data || [];
        setTenants(list);

        if (user?.user?.is_superadmin || user?.user?.role === 'superadmin') {
            setSelectedTenantId(list[0]?.id ? String(list[0].id) : '');
        } else if (user?.user?.tenant_id) {
            setSelectedTenantId(String(user.user.tenant_id));
        }
    };

    const loadAnalytics = async () => {
        setLoading(true);
        try {
            const res = await api.admin.getTenantAnalytics(selectedTenantId, days);
            setData(res || null);
        } finally {
            setLoading(false);
        }
    };

    const traffic = data?.traffic || [];
    const totals = traffic.reduce(
        (acc, row) => {
            acc.requests += Number(row.requests || 0);
            acc.unique_ips += Number(row.unique_ips || 0);
            acc.bytes += Number(row.bytes || 0);
            acc.status_4xx += Number(row.status_4xx || 0);
            acc.status_5xx += Number(row.status_5xx || 0);
            return acc;
        },
        { requests: 0, unique_ips: 0, bytes: 0, status_4xx: 0, status_5xx: 0 }
    );

    const costs = data?.costs || null;
    const observability = data?.observability || null;

    const formatBytes = (bytes) => {
        if (!bytes) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unit = 0;
        while (size >= 1024 && unit < units.length - 1) {
            size /= 1024;
            unit++;
        }
        return `${size.toFixed(1)} ${units[unit]}`;
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Tenant Analytics</h1>
                    <p className="text-sm text-gray-500">Traffic, storage, and content growth per tenant.</p>
                </div>
                <div className="flex items-center gap-3">
                    <select
                        value={selectedTenantId}
                        onChange={(e) => setSelectedTenantId(e.target.value)}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                    >
                        {tenants.map((tenant) => (
                            <option key={tenant.id} value={tenant.id}>{tenant.name}</option>
                        ))}
                    </select>
                    <select
                        value={days}
                        onChange={(e) => setDays(Number(e.target.value))}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                    >
                        <option value={7}>Last 7 days</option>
                        <option value={30}>Last 30 days</option>
                        <option value={90}>Last 90 days</option>
                    </select>
                </div>
            </div>

            {loading ? (
                <p className="text-sm text-gray-500">Loading analytics...</p>
            ) : (
                <>
                    <div className="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-6">
                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                            <div className="flex items-center gap-2 text-gray-700 mb-3">
                                <Activity className="w-4 h-4" />
                                <h2 className="text-sm font-semibold">Traffic</h2>
                            </div>
                            <div className="text-xs text-gray-600 space-y-2">
                                <p>Requests: {totals.requests}</p>
                                <p>Unique IPs (approx): {totals.unique_ips}</p>
                                <p>Bandwidth: {formatBytes(totals.bytes)}</p>
                                <p>4xx: {totals.status_4xx} • 5xx: {totals.status_5xx}</p>
                            </div>
                        </div>
                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                            <div className="flex items-center gap-2 text-gray-700 mb-3">
                                <Database className="w-4 h-4" />
                                <h2 className="text-sm font-semibold">Cost (est.)</h2>
                            </div>
                            <div className="text-xs text-gray-600 space-y-2">
                                <p>Bandwidth: {costs ? `${costs.bandwidth_gb} GB ($${costs.bandwidth_cost})` : 'n/a'}</p>
                                <p>Storage: {costs ? `${costs.storage_gb} GB ($${costs.storage_cost})` : 'n/a'}</p>
                                <p>Total: {costs ? `$${costs.total_cost}` : 'n/a'}</p>
                                <p className="text-[11px] text-gray-400">Window: {costs ? `${costs.from} → ${costs.to}` : ''}</p>
                            </div>
                        </div>
                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                            <div className="flex items-center gap-2 text-gray-700 mb-3">
                                <Activity className="w-4 h-4" />
                                <h2 className="text-sm font-semibold">Realtime</h2>
                            </div>
                            {realtimeLoading ? (
                                <p className="text-xs text-gray-500">Loading realtime...</p>
                            ) : (
                                <div className="text-xs text-gray-600 space-y-2">
                                    <p>Requests (5m): {realtime?.requests_5m ?? 0}</p>
                                    <p>Requests (60m): {realtime?.requests_60m ?? 0}</p>
                                    <p>4xx: {realtime?.status_4xx ?? 0} • 5xx: {realtime?.status_5xx ?? 0}</p>
                                    <p className="text-[11px] text-gray-400">Updated: {realtime?.generated_at || 'n/a'}</p>
                                </div>
                            )}
                        </div>
                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                            <div className="flex items-center gap-2 text-gray-700 mb-3">
                                <Database className="w-4 h-4" />
                                <h2 className="text-sm font-semibold">Storage</h2>
                            </div>
                            <div className="text-xs text-gray-600 space-y-2">
                                <p>Usage: {formatBytes(data?.storage?.bytes || 0)}</p>
                                <p>Limit: {data?.storage?.limit_bytes ? formatBytes(data.storage.limit_bytes) : 'unlimited'}</p>
                                <p>Usage %: {data?.storage?.usage_percent ?? 'n/a'}</p>
                                <p className="text-[11px] text-gray-400">Paths: {(data?.storage?.paths || []).join(', ')}</p>
                            </div>
                        </div>
                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                            <div className="flex items-center gap-2 text-gray-700 mb-3">
                                <TrendingUp className="w-4 h-4" />
                                <h2 className="text-sm font-semibold">Content</h2>
                            </div>
                            <div className="text-xs text-gray-600 space-y-2">
                                <p>Total articles: {data?.totals?.articles ?? 0}</p>
                                <p>Total recipes: {data?.totals?.recipes ?? 0}</p>
                            </div>
                        </div>
                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                            <div className="flex items-center gap-2 text-gray-700 mb-3">
                                <Activity className="w-4 h-4" />
                                <h2 className="text-sm font-semibold">Observability</h2>
                            </div>
                            <div className="text-xs text-gray-600 space-y-2">
                                <p>Status: <span className={`font-semibold ${observability?.status === 'critical' ? 'text-rose-600' : observability?.status === 'degraded' ? 'text-amber-600' : 'text-emerald-600'}`}>{observability?.status || 'n/a'}</span></p>
                                <p>Avg/P95: {observability ? `${observability.performance.avg_response_time_ms}ms / ${observability.performance.p95_response_time_ms}ms` : 'n/a'}</p>
                                <p>5xx rate: {observability ? `${observability.performance.error_rate_5xx_percent}%` : 'n/a'}</p>
                                <p>Unresolved critical: {observability?.errors?.unresolved_critical ?? 0}</p>
                                <p className="text-[11px] text-gray-400">Window: {observability?.window_hours ?? 0}h</p>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                            <h2 className="text-sm font-semibold text-gray-700 mb-3">Traffic by Day</h2>
                            <div className="text-xs text-gray-600 space-y-2 max-h-72 overflow-auto">
                                {traffic.length === 0 ? (
                                    <p className="text-gray-500">No traffic metrics yet.</p>
                                ) : (
                                    traffic.map((row) => (
                                        <div key={row.id} className="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                                            <span>{row.date}</span>
                                            <span>{row.requests} req • {formatBytes(row.bytes)}</span>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                            <h2 className="text-sm font-semibold text-gray-700 mb-3">Content Growth</h2>
                            <div className="text-xs text-gray-600 space-y-4">
                                <div>
                                    <h3 className="font-semibold text-gray-600 mb-2">Articles</h3>
                                    {(data?.content_growth?.articles || []).map((row, idx) => (
                                        <div key={`a-${idx}`} className="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                                            <span>{row.date}</span>
                                            <span>{row.total}</span>
                                        </div>
                                    ))}
                                </div>
                                <div>
                                    <h3 className="font-semibold text-gray-600 mb-2">Recipes</h3>
                                    {(data?.content_growth?.recipes || []).map((row, idx) => (
                                        <div key={`r-${idx}`} className="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                                            <span>{row.date}</span>
                                            <span>{row.total}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                            <h2 className="text-sm font-semibold text-gray-700 mb-3">Anomalies</h2>
                            <div className="text-xs text-gray-600 space-y-2 max-h-72 overflow-auto">
                                {(observability?.anomalies || []).length === 0 ? (
                                    <p className="text-gray-500">No active anomalies.</p>
                                ) : (
                                    (observability?.anomalies || []).map((item, idx) => (
                                        <div key={`anomaly-${idx}`} className="border border-gray-100 rounded-lg px-3 py-2">
                                            <p className={`font-semibold ${item.level === 'critical' ? 'text-rose-600' : 'text-amber-600'}`}>
                                                {item.code}
                                            </p>
                                            <p className="text-gray-600">{item.message}</p>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
