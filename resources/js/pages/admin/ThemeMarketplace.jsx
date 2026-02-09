import { useEffect, useMemo, useState } from 'react';
import { api } from '../../services/api';
import { ShoppingBag, Sparkles } from 'lucide-react';

export default function ThemeMarketplace() {
    const [themes, setThemes] = useState([]);
    const [search, setSearch] = useState('');
    const [category, setCategory] = useState('');
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [currentThemeId, setCurrentThemeId] = useState(null);

    const tenantId = useMemo(() => {
        if (typeof window === 'undefined') return null;
        return window.localStorage.getItem('adminTenantId');
    }, []);

    const loadThemes = async () => {
        setLoading(true);
        setMessage('');
        setError('');
        try {
            const res = await api.admin.getMarketplaceThemes({ search, category });
            setThemes(res?.data || []);
        } catch {
            setError('Unable to load marketplace themes.');
        } finally {
            setLoading(false);
        }
    };

    const loadCurrentTheme = async () => {
        if (!tenantId || tenantId === 'all') return;
        try {
            const res = await api.admin.getTenants();
            const tenant = (res?.data || []).find((item) => String(item.id) === String(tenantId));
            setCurrentThemeId(tenant?.theme_id || tenant?.theme?.id || null);
        } catch {
            setCurrentThemeId(null);
        }
    };

    useEffect(() => {
        loadThemes();
        loadCurrentTheme();
    }, []);

    const categories = useMemo(() => {
        const values = new Set();
        themes.forEach((theme) => {
            if (theme.category) values.add(theme.category);
        });
        return Array.from(values);
    }, [themes]);

    const installTheme = async (themeId) => {
        if (!tenantId || tenantId === 'all') {
            setError('Select a tenant to install a theme.');
            return;
        }
        setMessage('');
        setError('');
        try {
            await api.admin.installMarketplaceTheme(themeId);
            setMessage('Theme installed for selected tenant.');
            setCurrentThemeId(themeId);
        } catch {
            setError('Failed to install theme.');
        }
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Theme Marketplace</h1>
                    <p className="text-sm text-gray-500">Browse ready-made templates and install them per tenant.</p>
                </div>
                <div className="flex items-center gap-2 text-sm text-gray-500">
                    <ShoppingBag className="w-4 h-4" />
                    {themes.length} themes
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search themes..."
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                    />
                    <select
                        value={category}
                        onChange={(e) => setCategory(e.target.value)}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                    >
                        <option value="">All categories</option>
                        {categories.map((cat) => (
                            <option key={cat} value={cat}>{cat}</option>
                        ))}
                    </select>
                    <button
                        onClick={loadThemes}
                        className="px-3 py-2 rounded-lg bg-gray-900 text-white text-sm"
                    >
                        Filter
                    </button>
                </div>

                {tenantId === 'all' && (
                    <div className="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Select a tenant from the header to install themes.
                    </div>
                )}

                {(message || error) && (
                    <div className={`text-sm ${error ? 'text-rose-600' : 'text-emerald-600'}`}>
                        {error || message}
                    </div>
                )}
            </div>

            {loading ? (
                <p className="text-sm text-gray-500">Loading themes...</p>
            ) : themes.length === 0 ? (
                <p className="text-sm text-gray-500">No marketplace themes yet.</p>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    {themes.map((theme) => (
                        <div key={theme.id} className="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                            <div className="h-40 bg-gray-100">
                                {theme.preview_image ? (
                                    <img src={theme.preview_image} alt={theme.name} className="h-full w-full object-cover" />
                                ) : (
                                    <div className="h-full flex items-center justify-center text-gray-400">
                                        <Sparkles className="w-8 h-8" />
                                    </div>
                                )}
                            </div>
                            <div className="p-5 space-y-3">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-semibold text-gray-900">{theme.name}</h3>
                                    {theme.is_featured && (
                                        <span className="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700">Featured</span>
                                    )}
                                </div>
                                <p className="text-sm text-gray-600">{theme.description || 'No description.'}</p>
                                <div className="text-xs text-gray-400">
                                    {theme.category || 'General'} • {theme.author || 'Community'} • {theme.version || '1.0'}
                                </div>
                                {theme.tags && theme.tags.length > 0 && (
                                    <div className="flex flex-wrap gap-2 text-xs text-gray-500">
                                        {theme.tags.map((tag) => (
                                            <span key={tag} className="px-2 py-1 bg-gray-100 rounded-full">{tag}</span>
                                        ))}
                                    </div>
                                )}
                                <button
                                    onClick={() => installTheme(theme.id)}
                                    disabled={currentThemeId === theme.id}
                                    className={`w-full mt-2 px-4 py-2 rounded-lg text-sm font-semibold ${
                                        currentThemeId === theme.id
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-gray-900 text-white'
                                    }`}
                                >
                                    {currentThemeId === theme.id ? 'Installed' : 'Install'}
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
