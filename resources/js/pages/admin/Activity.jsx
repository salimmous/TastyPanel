import { useEffect, useState } from 'react';
import { api } from '../../services/api';
import { ClipboardList } from 'lucide-react';

export default function Activity() {
    const [tenants, setTenants] = useState([]);
    const [selectedTenantId, setSelectedTenantId] = useState('');
    const [logs, setLogs] = useState([]);
    const [search, setSearch] = useState('');

    useEffect(() => {
        loadTenants();
    }, []);

    useEffect(() => {
        if (selectedTenantId) {
            loadLogs();
        }
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

    const loadLogs = async () => {
        const res = await api.admin.getTenantActivity(selectedTenantId, 1, search);
        setLogs(res?.data || []);
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Activity Logs</h1>
                    <p className="text-sm text-gray-500">Tenant actions and audit history.</p>
                </div>
                <div className="flex gap-2">
                    <select
                        value={selectedTenantId}
                        onChange={(e) => setSelectedTenantId(e.target.value)}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                    >
                        {tenants.map((tenant) => (
                            <option key={tenant.id} value={tenant.id}>{tenant.name}</option>
                        ))}
                    </select>
                    <input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                        placeholder="Search"
                    />
                    <button
                        onClick={loadLogs}
                        className="px-3 py-2 rounded-lg border border-gray-200 text-sm"
                    >
                        Search
                    </button>
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center gap-2 text-gray-700 mb-4">
                    <ClipboardList className="w-4 h-4" />
                    <h2 className="text-sm font-semibold">Recent Actions</h2>
                </div>
                <div className="space-y-2 text-xs">
                    {logs.length === 0 ? (
                        <p className="text-gray-500">No logs yet.</p>
                    ) : (
                        logs.map((log) => (
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
            </div>
        </div>
    );
}
