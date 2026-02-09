import { useEffect, useState } from 'react';
import { api } from '../../services/api';
import { Activity, Plus } from 'lucide-react';

const emptyForm = {
    name: '',
    url: '',
    expected_status: 200,
    is_active: true,
};

export default function Monitoring() {
    const [tenants, setTenants] = useState([]);
    const [selectedTenantId, setSelectedTenantId] = useState('');
    const [checks, setChecks] = useState([]);
    const [selectedCheckId, setSelectedCheckId] = useState('');
    const [events, setEvents] = useState([]);
    const [form, setForm] = useState(emptyForm);

    useEffect(() => {
        loadTenants();
    }, []);

    useEffect(() => {
        if (selectedTenantId) {
            loadChecks();
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

    const loadChecks = async () => {
        const res = await api.admin.getTenantUptimeChecks(selectedTenantId);
        const list = res?.data || [];
        setChecks(list);
        if (list.length) {
            setSelectedCheckId(String(list[0].id));
            await loadEvents(String(list[0].id));
        }
    };

    const loadEvents = async (checkId) => {
        if (!checkId) return;
        const res = await api.admin.getTenantUptimeEvents(selectedTenantId, checkId, 1);
        setEvents(res?.data || []);
    };

    const createCheck = async () => {
        if (!form.name || !form.url) return;
        await api.admin.createTenantUptimeCheck(selectedTenantId, form);
        setForm(emptyForm);
        await loadChecks();
    };

    const runCheck = async (checkId) => {
        await api.admin.runTenantUptimeCheck(selectedTenantId, checkId);
        await loadChecks();
        await loadEvents(checkId);
    };

    const deleteCheck = async (checkId) => {
        await api.admin.deleteTenantUptimeCheck(selectedTenantId, checkId);
        await loadChecks();
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Monitoring</h1>
                    <p className="text-sm text-gray-500">Uptime checks per tenant.</p>
                </div>
                <select
                    value={selectedTenantId}
                    onChange={(e) => setSelectedTenantId(e.target.value)}
                    className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                >
                    {tenants.map((tenant) => (
                        <option key={tenant.id} value={tenant.id}>{tenant.name}</option>
                    ))}
                </select>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center gap-2 text-gray-700 mb-4">
                    <Plus className="w-4 h-4" />
                    <h2 className="text-sm font-semibold">Create Check</h2>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-2 text-xs">
                    <input
                        value={form.name}
                        onChange={(e) => setForm({ ...form, name: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Check name"
                    />
                    <input
                        value={form.url}
                        onChange={(e) => setForm({ ...form, url: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="https://example.com"
                    />
                    <input
                        type="number"
                        value={form.expected_status}
                        onChange={(e) => setForm({ ...form, expected_status: Number(e.target.value) })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Expected status"
                    />
                    <button
                        onClick={createCheck}
                        className="px-3 py-2 rounded-lg bg-gray-900 text-white"
                    >
                        Add Check
                    </button>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <div className="flex items-center gap-2 text-gray-700 mb-4">
                        <Activity className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Checks</h2>
                    </div>
                    <div className="space-y-2 text-xs">
                        {checks.map((check) => (
                            <div key={check.id} className="border border-gray-100 rounded-lg px-3 py-2">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="font-semibold text-gray-700">{check.name}</p>
                                        <p className="text-[11px] text-gray-500">{check.url}</p>
                                        <p className="text-[11px] text-gray-400">Last: {check.last_status || 'n/a'} • {check.last_response_ms || 0}ms</p>
                                    </div>
                                    <div className="flex gap-2">
                                        <button onClick={() => runCheck(check.id)} className="text-xs text-blue-600">Run</button>
                                        <button onClick={() => deleteCheck(check.id)} className="text-xs text-rose-600">Delete</button>
                                    </div>
                                </div>
                                <button
                                    onClick={() => {
                                        setSelectedCheckId(String(check.id));
                                        loadEvents(String(check.id));
                                    }}
                                    className="mt-2 text-[11px] text-gray-500"
                                >
                                    View events
                                </button>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <div className="flex items-center gap-2 text-gray-700 mb-4">
                        <Activity className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Events</h2>
                    </div>
                    <div className="space-y-2 text-xs">
                        {events.map((event) => (
                            <div key={event.id} className="border border-gray-100 rounded-lg px-3 py-2">
                                <div className="flex items-center justify-between">
                                    <span className="font-semibold">{event.status || 'error'}</span>
                                    <span className="text-gray-400">{event.checked_at}</span>
                                </div>
                                <p className="text-[11px] text-gray-500">{event.response_ms || 0}ms • {event.error || 'ok'}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
