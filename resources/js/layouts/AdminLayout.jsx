import { useState, useEffect } from 'react';
import { useNavigate, Outlet, useLocation } from 'react-router-dom';
import { api } from '../services/api';
import {
    Menu, X, RefreshCw, Eye, Grid, User,
    LogOut, ChevronRight, Sparkles, Folder,
    Archive, Megaphone, Rss, User as ReporterIcon,
    MessageSquare, CheckSquare, Video, FileText,
    Search, RefreshCw as AutoPostIcon, Settings,
    Users, Image as ImageIcon, Globe, Palette,
    Activity, Key, Shield, ClipboardList, ShoppingBag, Layers, PlugZap
} from 'lucide-react';

const menuItems = [
    { name: 'Platform', path: '/admin/platform', icon: Settings, requiresSuperadmin: true },
    { name: 'Ops Center', path: '/admin/ops', icon: Shield, requiresSuperadmin: true },
    { name: 'Sites', tenantLabel: 'Domains', path: '/admin/tenants', icon: Globe, requiresInfrastructure: true },
    { name: 'Analytics', path: '/admin/analytics', icon: Activity, requiresInfrastructure: true },
    { name: 'Monitoring', path: '/admin/monitoring', icon: Activity, requiresInfrastructure: true },
    { name: 'Activity Logs', path: '/admin/activity', icon: ClipboardList, requiresUsers: true },
    { name: 'Integrations', path: '/admin/integrations', icon: Key, requiresUsers: true },
    { name: 'Plugins', path: '/admin/plugins', icon: PlugZap, requiresSuperadmin: true },
    { name: 'Feature Flags', path: '/admin/feature-flags', icon: Sparkles, requiresSuperadmin: true },
    { name: 'Templates', path: '/admin/themes', icon: Palette, requiresSuperadmin: true },
    { name: 'Marketplace', tenantLabel: 'Themes', path: '/admin/marketplace', icon: ShoppingBag, requiresInfrastructure: true },
    { name: 'Staging', path: '/admin/staging', icon: Layers, requiresInfrastructure: true },
    { name: 'Preview', path: '/admin/preview', icon: Layers, requiresInfrastructure: true },
    { name: 'Files', path: '/admin/files', icon: Folder, requiresInfrastructure: true },
    { name: 'AI Writer', path: '/admin/ai-writer', icon: Sparkles },
    { name: 'Categories', path: '/admin/categories', icon: Folder },
    { name: 'Media', path: '/admin/media', icon: ImageIcon },
    { name: 'Users', path: '/admin/users', icon: Users, requiresUsers: true },
    { name: 'Archive', path: '/admin/archive', icon: Archive },
    { name: 'Advertisement', path: '/admin/advertisement', icon: Megaphone },
    { name: 'Rss Feeds', path: '/admin/rss-feeds', icon: Rss },
    { name: 'Reporter', path: '/admin/reporter', icon: ReporterIcon },
    { name: 'Opinions', path: '/admin/opinions', icon: MessageSquare },
    { name: 'Polls', path: '/admin/polls', icon: CheckSquare, hasSubmenu: true },
    { name: 'Video Post', path: '/admin/video-post', icon: Video },
    { name: 'Page', path: '/admin/page', icon: FileText },
    { name: 'SEO', path: '/admin/seo', icon: Search },
    { name: 'Automation', path: '/admin/auto-post-settings', icon: AutoPostIcon },
    { name: 'Web Setup', path: '/admin/web-setup', icon: Settings },
    { name: 'Settings', path: '/admin/settings', icon: Settings },
    { name: 'Subscribers', path: '/admin/subscribers', icon: Users },
];

// Add Dashboard and Posts to menu items at the beginning
const allMenuItems = [
    { name: 'Dashboard', path: '/admin/dashboard', icon: FileText },
    { name: 'Posts', path: '/admin/posts', icon: FileText },
    ...menuItems,
];

export default function AdminLayout() {
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const [user, setUser] = useState(null);
    const [tenants, setTenants] = useState([]);
    const [selectedTenantId, setSelectedTenantId] = useState(() => {
        if (typeof window === 'undefined') return 'all';
        return window.localStorage.getItem('adminTenantId') || 'all';
    });
    const [selectedEnvironment, setSelectedEnvironment] = useState(() => {
        if (typeof window === 'undefined') return 'production';
        return window.localStorage.getItem('adminEnvironment') || 'production';
    });
    const [branding, setBranding] = useState({
        brand_name: 'TastyPanel',
        brand_logo_url: '',
        brand_primary_color: '#2563eb',
        brand_favicon_url: '',
    });
    const navigate = useNavigate();
    const location = useLocation();
    const isTenantMode = user?.app_mode === 'tenant';

    useEffect(() => {
        loadUser();
        loadBranding();
    }, []);

    useEffect(() => {
        if (user) {
            loadTenants();
            if ((!user.is_superadmin && user.role !== 'superadmin') && user.tenant_id) {
                const tenantValue = String(user.tenant_id);
                window.localStorage.setItem('adminTenantId', tenantValue);
                setSelectedTenantId(tenantValue);
            }
            if (user.is_superadmin || user.role === 'superadmin') {
                const stored = window.localStorage.getItem('adminTenantId');
                if (!stored) {
                    window.localStorage.setItem('adminTenantId', 'all');
                    setSelectedTenantId('all');
                }
            }
        }
    }, [user]);

    const loadUser = async () => {
        try {
            const response = await api.admin.getUser();
            if (response && response.user) {
                if (response.user.two_factor_enabled && !response.two_factor_verified && location.pathname !== '/admin/2fa') {
                    navigate('/admin/2fa');
                    return;
                }
                if (response.user.force_password_reset && location.pathname !== '/admin/force-password') {
                    navigate('/admin/force-password');
                    return;
                }
                setUser(response.user);
            }
        } catch (err) {
            // Not authenticated, redirect to login
            navigate('/login');
        }
    };

    const loadTenants = async () => {
        try {
            const response = await api.admin.getTenants();
            setTenants(response?.data || []);
        } catch (err) {
            setTenants([]);
        }
    };

    const loadBranding = async () => {
        try {
            const brand = await api.admin.getBranding();
            if (brand) {
                setBranding((prev) => ({ ...prev, ...brand }));
                if (brand.brand_favicon_url) {
                    const link = document.querySelector("link[rel='icon']") || document.createElement('link');
                    link.rel = 'icon';
                    link.href = brand.brand_favicon_url;
                    document.head.appendChild(link);
                }
            }
        } catch {
            // ignore
        }
    };

    const handleLogout = async () => {
        try {
            await api.admin.logout();
            navigate('/login');
        } catch (err) {
            navigate('/login');
        }
    };

    const handleTenantChange = (event) => {
        const value = event.target.value;
        window.localStorage.setItem('adminTenantId', value);
        setSelectedTenantId(value);
        window.location.reload();
    };

    const handleEnvironmentChange = (event) => {
        const value = event.target.value;
        window.localStorage.setItem('adminEnvironment', value);
        setSelectedEnvironment(value);
        window.location.reload();
    };

    const handleCachePurge = async () => {
        if (!selectedTenantId || selectedTenantId === 'all') {
            alert('Select a tenant to purge cache.');
            return;
        }
        if (!window.confirm('Purge cache for selected tenant?')) {
            return;
        }
        await api.admin.purgeTenantCache(selectedTenantId);
        alert('Cache purge requested.');
    };

    return (
        <div className="min-h-screen bg-gray-50 flex">
            {/* Sidebar */}
            <aside className={`${sidebarOpen ? 'w-64' : 'w-0'} bg-white border-r border-gray-200 transition-all duration-300 overflow-hidden`}>
                <div className="p-4">
                    <div className="flex items-center justify-between mb-8">
                        <h2 className="text-lg font-semibold text-gray-800">Menu</h2>
                        <button onClick={() => setSidebarOpen(false)} className="lg:hidden">
                            <X className="w-5 h-5" />
                        </button>
                    </div>
                    
                    <nav className="space-y-1">
                        {allMenuItems
                            .filter((item) => !item.requiresSuperadmin || user?.is_superadmin || user?.role === 'superadmin')
                            .filter((item) => {
                                if (!item.requiresUsers) return true;
                                return user?.is_superadmin || user?.role === 'superadmin' || user?.role === 'tenant-admin';
                            })
                            .filter((item) => {
                                if (!item.requiresInfrastructure) return true;
                                return user?.is_superadmin || user?.role === 'superadmin' || user?.role === 'tenant-admin';
                            })
                            .map((item) => {
                            const Icon = item.icon;
                            const isActive = location.pathname === item.path || (item.path === '/admin/dashboard' && location.pathname === '/admin');
                            return (
                                <a
                                    key={item.path}
                                    href={item.path}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        navigate(item.path);
                                    }}
                                    className={`flex items-center justify-between px-4 py-2 text-sm rounded-lg transition-colors ${
                                        isActive
                                            ? 'bg-blue-50 text-blue-700 font-medium'
                                            : 'text-gray-700 hover:bg-gray-100'
                                    }`}
                                >
                                    <div className="flex items-center gap-2">
                                        <Icon className="w-4 h-4" />
                                        <span>{isTenantMode && item.tenantLabel ? item.tenantLabel : item.name}</span>
                                    </div>
                                    {item.hasSubmenu && <ChevronRight className="w-4 h-4" />}
                                </a>
                            );
                        })}
                    </nav>
                </div>
            </aside>

            {/* Main Content */}
            <div className="flex-1 flex flex-col">
                {/* Header */}
                <header className="bg-white border-b border-gray-200 px-6 py-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <button onClick={() => setSidebarOpen(!sidebarOpen)} className="lg:hidden">
                                <Menu className="w-6 h-6" />
                            </button>
                            <div className="flex items-center gap-2">
                                {branding.brand_logo_url ? (
                                    <img src={branding.brand_logo_url} alt={branding.brand_name} className="h-8" />
                                ) : null}
                                <h1 className="text-2xl font-bold" style={{ color: branding.brand_primary_color || '#2563eb' }}>
                                    {branding.brand_name || 'TastyPanel'}
                                </h1>
                            </div>
                        </div>
                        
                        <div className="flex items-center gap-4 flex-1 max-w-2xl mx-4">
                            {/* Search Bar */}
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                <input
                                    type="text"
                                    placeholder="Search posts, categories, users..."
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                />
                            </div>

                            <div className="hidden lg:flex items-center gap-2">
                                <span className="text-xs font-semibold uppercase text-gray-400">Tenant</span>
                            {!isTenantMode && (user?.is_superadmin || user?.role === 'superadmin') ? (
                                <select
                                        value={selectedTenantId}
                                        onChange={handleTenantChange}
                                        className="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white"
                                    >
                                        <option value="all">All tenants</option>
                                        {tenants.map((tenant) => (
                                            <option key={tenant.id} value={tenant.id}>
                                                {tenant.name}
                                            </option>
                                        ))}
                                    </select>
                                ) : (
                                    <span className="text-sm text-gray-600">
                                        {tenants.find((tenant) => tenant.id === user?.tenant_id)?.name || 'Assigned tenant'}
                                    </span>
                                )}
                                <span className="ml-3 text-xs font-semibold uppercase text-gray-400">Env</span>
                                <select
                                    value={selectedEnvironment}
                                    onChange={handleEnvironmentChange}
                                    className="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white"
                                >
                                    <option value="production">Production</option>
                                    <option value="staging">Staging</option>
                                    <option value="preview">Preview</option>
                                </select>
                            </div>
                        </div>
                        
                        <div className="flex items-center gap-4">
                            <button
                                onClick={handleCachePurge}
                                className="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg"
                            >
                                <RefreshCw className="w-4 h-4" />
                                Cache clear
                            </button>
                            <button 
                                onClick={() => window.open('/', '_blank')}
                                className="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">
                                <Eye className="w-4 h-4" />
                                View site
                            </button>
                            <button className="p-2 text-gray-700 hover:bg-gray-100 rounded-lg relative">
                                <Grid className="w-5 h-5" />
                                <span className="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                            </button>
                            <div className="flex items-center gap-3">
                                <div className="text-right">
                                    <p className="text-sm font-medium text-gray-900">{user?.name || 'Admin'}</p>
                                    <p className="text-xs text-gray-500">Admin</p>
                                </div>
                                <div className="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                    <User className="w-5 h-5 text-gray-600" />
                                </div>
                                <button onClick={handleLogout} className="p-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                                    <LogOut className="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </header>

                {/* Page Content */}
                <main className="flex-1 overflow-y-auto">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
