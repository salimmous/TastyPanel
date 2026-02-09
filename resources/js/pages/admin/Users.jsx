import { useState, useEffect } from 'react';
import { api } from '../../services/api';
import { User, Plus, Edit, Trash2, Search, Mail, Calendar, Shield } from 'lucide-react';

export default function Users() {
    const [users, setUsers] = useState([]);
    const [tenants, setTenants] = useState([]);
    const [currentUser, setCurrentUser] = useState(null);
    const [isTenantMode, setIsTenantMode] = useState(false);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [form, setForm] = useState({
        name: '',
        email: '',
        password: '',
        role: 'writer',
        tenant_id: '',
        two_factor_enabled: false,
    });
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        loadUsers();
    }, [searchTerm]);

    useEffect(() => {
        loadMeta();
    }, []);

    const loadMeta = async () => {
        try {
            const [userRes, tenantRes] = await Promise.all([
                api.admin.getUser(),
                api.admin.getTenants(),
            ]);
            setCurrentUser(userRes?.user || null);
            setIsTenantMode(userRes?.user?.app_mode === 'tenant');
            setTenants(tenantRes?.data || []);
        } catch (err) {
            setTenants([]);
        }
    };

    const loadUsers = async () => {
        setLoading(true);
        try {
            const data = await api.admin.getUsers({ search: searchTerm });
            setUsers(Array.isArray(data.data) ? data.data : data);
        } catch (err) {
            console.error('Error loading users:', err);
        } finally {
            setLoading(false);
        }
    };

    const handleFormChange = (field) => (event) => {
        setForm((prev) => ({ ...prev, [field]: event.target.value }));
    };

    const handleCreate = async (event) => {
        event.preventDefault();
        if (!form.name || !form.email || !form.password) {
            return;
        }
        setSaving(true);
        try {
            await api.admin.createUser({
                name: form.name,
                email: form.email,
                password: form.password,
                role: form.role,
                tenant_id: form.tenant_id ? Number(form.tenant_id) : null,
                two_factor_enabled: form.two_factor_enabled,
            });
            setForm({ name: '', email: '', password: '', role: 'writer', tenant_id: '', two_factor_enabled: false });
            loadUsers();
        } catch (err) {
            console.error('Error creating user:', err);
            alert('Failed to create user');
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm('Are you sure you want to delete this user?')) {
            return;
        }
        try {
            await api.admin.deleteUser(id);
            loadUsers();
        } catch (err) {
            console.error('Error deleting user:', err);
            alert('Failed to delete user');
        }
    };

    if (loading) {
        return (
            <div className="p-6 flex items-center justify-center min-h-screen">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p className="mt-4 text-gray-600">جاري التحميل...</p>
                </div>
            </div>
        );
    }

    const isSuperadmin = currentUser?.is_superadmin || currentUser?.role === 'superadmin';
    const canManageUsers = isSuperadmin || currentUser?.role === 'tenant-admin';
    const roleOptions = isSuperadmin
        ? ['superadmin', 'tenant-admin', 'editor', 'writer']
        : (isTenantMode ? ['tenant-admin', 'editor', 'writer'] : ['editor', 'writer']);

    return (
        <div className="p-6">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">Users</h1>
            </div>

            {canManageUsers ? (
                <div className="bg-white rounded-lg shadow mb-6">
                    <form onSubmit={handleCreate} className="p-4 space-y-4">
                        <div className="flex items-center gap-2 text-sm text-gray-600">
                            <Shield className="w-4 h-4" />
                            Create user with role + tenant permissions
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <input
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                placeholder="Name"
                                value={form.name}
                                onChange={handleFormChange('name')}
                            />
                            <input
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                placeholder="Email"
                                value={form.email}
                                onChange={handleFormChange('email')}
                            />
                            <input
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                placeholder="Password"
                                type="password"
                                value={form.password}
                                onChange={handleFormChange('password')}
                            />
                            <select
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                value={form.role}
                                onChange={handleFormChange('role')}
                            >
                                {roleOptions.map((role) => (
                                    <option key={role} value={role}>
                                        {role}
                                    </option>
                                ))}
                            </select>
                            {isSuperadmin && (
                                <select
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                    value={form.tenant_id}
                                    onChange={handleFormChange('tenant_id')}
                                >
                                    <option value="">Assign tenant</option>
                                    {tenants.map((tenant) => (
                                        <option key={tenant.id} value={tenant.id}>
                                            {tenant.name}
                                        </option>
                                    ))}
                                </select>
                            )}
                            {isSuperadmin && (
                                <label className="flex items-center gap-2 text-xs text-gray-600">
                                    <input
                                        type="checkbox"
                                        checked={form.two_factor_enabled}
                                        onChange={(e) => setForm((prev) => ({ ...prev, two_factor_enabled: e.target.checked }))}
                                    />
                                    Enable 2FA
                                </label>
                            )}
                        </div>
                        <button
                            type="submit"
                            disabled={saving}
                            className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-60"
                        >
                            <Plus className="w-4 h-4" />
                            {saving ? 'Creating...' : 'Add User'}
                        </button>
                    </form>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow mb-6 p-4 text-sm text-gray-500">
                    You do not have permission to manage users.
                </div>
            )}

            <div className="bg-white rounded-lg shadow">
                <div className="p-4 border-b">
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                        <input
                            type="text"
                            placeholder="Search users..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">2FA</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tenant</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {users.length > 0 ? (
                                users.map((user) => (
                                    <tr key={user.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{user.id}</td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                                    <User className="w-5 h-5 text-gray-600" />
                                                </div>
                                                <span className="text-sm font-medium text-gray-900">{user.name}</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div className="flex items-center gap-2">
                                                <Mail className="w-4 h-4" />
                                                {user.email}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {user.role || (user.is_superadmin ? 'superadmin' : 'tenant-admin')}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {user.two_factor_enabled ? 'On' : 'Off'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {tenants.find((tenant) => tenant.id === user.tenant_id)?.name || '—'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div className="flex items-center gap-2">
                                                <Calendar className="w-4 h-4" />
                                                {new Date(user.created_at).toLocaleDateString()}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            <div className="flex items-center gap-2">
                                                <button className="p-2 text-blue-600 hover:bg-blue-50 rounded">
                                                    <Edit className="w-4 h-4" />
                                                </button>
                                                {canManageUsers && (
                                                    <button 
                                                        onClick={() => handleDelete(user.id)}
                                                        className="p-2 text-red-600 hover:bg-red-50 rounded">
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="5" className="px-6 py-8 text-center text-gray-500">
                                        No users found
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
