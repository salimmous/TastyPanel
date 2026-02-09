import { useEffect, useState } from 'react';
import { api } from '../../services/api';
import { Palette, Plus } from 'lucide-react';
import { isSuperadmin } from '../../utils/permissions';

const emptyForm = {
    key: '',
    name: '',
    view: '',
    description: '',
    category: '',
    tags: '',
    author: '',
    version: '',
    is_marketplace: false,
    is_featured: false,
};

export default function Themes() {
    const [themes, setThemes] = useState([]);
    const [form, setForm] = useState(emptyForm);
    const [uploadForm, setUploadForm] = useState({
        key: '',
        name: '',
        description: '',
        category: '',
        tags: '',
        author: '',
        version: '',
        is_marketplace: true,
        is_featured: false,
        file: null,
        preview: null,
    });
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [currentUser, setCurrentUser] = useState(null);
    const [selectedThemeId, setSelectedThemeId] = useState('');
    const [themeVersions, setThemeVersions] = useState([]);
    const [versionsLoading, setVersionsLoading] = useState(false);
    const [versionUploading, setVersionUploading] = useState(false);
    const [versionMessage, setVersionMessage] = useState('');
    const [versionForm, setVersionForm] = useState({ version: '', notes: '', file: null });

    useEffect(() => {
        loadThemes();
        loadUser();
    }, []);

    useEffect(() => {
        if (selectedThemeId) {
            loadThemeVersions(selectedThemeId);
        }
    }, [selectedThemeId]);

    const loadUser = async () => {
        try {
            const response = await api.admin.getUser();
            setCurrentUser(response?.user || null);
        } catch {
            setCurrentUser(null);
        }
    };

    const loadThemes = async () => {
        setLoading(true);
        try {
            const response = await api.admin.getThemes();
            const list = response?.data || [];
            setThemes(list);
            if (!selectedThemeId && list.length) {
                setSelectedThemeId(String(list[0].id));
            }
        } finally {
            setLoading(false);
        }
    };

    const loadThemeVersions = async (themeId) => {
        setVersionsLoading(true);
        try {
            const res = await api.admin.getThemeVersions(themeId);
            setThemeVersions(res?.data || []);
        } finally {
            setVersionsLoading(false);
        }
    };

    const handleChange = (field) => (event) => {
        const value = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
        setForm((prev) => ({ ...prev, [field]: value }));
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        if (!form.key || !form.name || !form.view) {
            return;
        }
        setSaving(true);
        try {
            await api.admin.createTheme({
                key: form.key.trim(),
                name: form.name.trim(),
                view: form.view.trim(),
                description: form.description.trim() || null,
                category: form.category.trim() || null,
                tags: form.tags || null,
                author: form.author.trim() || null,
                version: form.version.trim() || null,
                is_marketplace: form.is_marketplace,
                is_featured: form.is_featured,
            });
            setForm(emptyForm);
            await loadThemes();
        } finally {
            setSaving(false);
        }
    };

    const handleUploadChange = (field) => (event) => {
        const value = field === 'file' || field === 'preview'
            ? event.target.files?.[0]
            : event.target.type === 'checkbox'
                ? event.target.checked
                : event.target.value;
        setUploadForm((prev) => ({ ...prev, [field]: value }));
    };

    const handleUpload = async (event) => {
        event.preventDefault();
        if (!uploadForm.key || !uploadForm.name || !uploadForm.file) {
            return;
        }
        setUploading(true);
        try {
            const formData = new FormData();
            formData.append('key', uploadForm.key);
            formData.append('name', uploadForm.name);
            formData.append('description', uploadForm.description || '');
            formData.append('category', uploadForm.category || '');
            formData.append('tags', uploadForm.tags || '');
            formData.append('author', uploadForm.author || '');
            formData.append('version', uploadForm.version || '');
            formData.append('is_marketplace', uploadForm.is_marketplace ? '1' : '0');
            formData.append('is_featured', uploadForm.is_featured ? '1' : '0');
            formData.append('zip', uploadForm.file);
            if (uploadForm.preview) {
                formData.append('preview', uploadForm.preview);
            }
            await api.admin.uploadTheme(formData);
            setUploadForm({
                key: '',
                name: '',
                description: '',
                category: '',
                tags: '',
                author: '',
                version: '',
                is_marketplace: true,
                is_featured: false,
                file: null,
                preview: null,
            });
            await loadThemes();
        } finally {
            setUploading(false);
        }
    };

    const handleVersionChange = (field) => (event) => {
        const value = field === 'file'
            ? event.target.files?.[0]
            : event.target.value;
        setVersionForm((prev) => ({ ...prev, [field]: value }));
    };

    const handleVersionUpload = async (event) => {
        event.preventDefault();
        if (!selectedThemeId || !versionForm.file) return;
        setVersionUploading(true);
        setVersionMessage('');
        try {
            const formData = new FormData();
            formData.append('version', versionForm.version || '');
            formData.append('notes', versionForm.notes || '');
            formData.append('zip', versionForm.file);
            await api.admin.uploadThemeVersion(selectedThemeId, formData);
            setVersionForm({ version: '', notes: '', file: null });
            setVersionMessage('Version uploaded and applied.');
            await loadThemeVersions(selectedThemeId);
            await loadThemes();
        } finally {
            setVersionUploading(false);
        }
    };

    const restoreVersion = async (versionId) => {
        if (!selectedThemeId) return;
        await api.admin.restoreThemeVersion(selectedThemeId, versionId);
        await loadThemeVersions(selectedThemeId);
        await loadThemes();
    };

    const toggleTheme = async (theme) => {
        await api.admin.updateTheme(theme.id, {
            is_active: !theme.is_active,
        });
        await loadThemes();
    };

    const toggleMarketplace = async (theme) => {
        await api.admin.updateTheme(theme.id, {
            is_marketplace: !theme.is_marketplace,
        });
        await loadThemes();
    };

    const toggleFeatured = async (theme) => {
        await api.admin.updateTheme(theme.id, {
            is_featured: !theme.is_featured,
        });
        await loadThemes();
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Template Manager</h1>
                    <p className="text-sm text-gray-500">Register Blade templates and enable/disable them per niche.</p>
                </div>
                <div className="flex items-center gap-2 text-sm text-gray-500">
                    <Palette className="w-4 h-4" />
                    {themes.length} themes
                </div>
            </div>

            {isSuperadmin(currentUser) ? (
                <div className="grid grid-cols-1 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] gap-6">
                    <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Add new template</h2>
                        <form className="space-y-4" onSubmit={handleSubmit}>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Key</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.key}
                                    onChange={handleChange('key')}
                                    placeholder="food"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Name</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.name}
                                    onChange={handleChange('name')}
                                    placeholder="Food Studio"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Blade View</label>
                            <input
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                value={form.view}
                                onChange={handleChange('view')}
                                placeholder="themes.food.home"
                            />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Description</label>
                            <textarea
                                className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                value={form.description}
                                onChange={handleChange('description')}
                                placeholder="Warm editorial layout for culinary brands."
                                rows={3}
                            />
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Category</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.category}
                                    onChange={handleChange('category')}
                                    placeholder="Food & Beverage"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Tags</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.tags}
                                    onChange={handleChange('tags')}
                                    placeholder="restaurant, menu, chef"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Author</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.author}
                                    onChange={handleChange('author')}
                                    placeholder="TastyPanel"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Version</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={form.version}
                                    onChange={handleChange('version')}
                                    placeholder="1.0.0"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-6 text-sm text-gray-600">
                            <label className="inline-flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.is_marketplace}
                                    onChange={handleChange('is_marketplace')}
                                    className="rounded border-gray-300"
                                />
                                Publish to marketplace
                            </label>
                            <label className="inline-flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.is_featured}
                                    onChange={handleChange('is_featured')}
                                    className="rounded border-gray-300"
                                />
                                Featured
                            </label>
                        </div>
                        <button
                            type="submit"
                            disabled={saving}
                            className="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 disabled:opacity-60"
                        >
                            <Plus className="w-4 h-4" />
                            {saving ? 'Saving...' : 'Add template'}
                        </button>
                        </form>
                    </div>

                    <div className="space-y-6">
                        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Upload template (ZIP)</h2>
                            <form className="space-y-4" onSubmit={handleUpload}>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Key</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={uploadForm.key}
                                    onChange={handleUploadChange('key')}
                                    placeholder="custom-theme"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Name</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={uploadForm.name}
                                    onChange={handleUploadChange('name')}
                                    placeholder="Custom Theme"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Description</label>
                                <textarea
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    value={uploadForm.description}
                                    onChange={handleUploadChange('description')}
                                    rows={3}
                                />
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs font-semibold uppercase text-gray-500">Category</label>
                                    <input
                                        className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                        value={uploadForm.category}
                                        onChange={handleUploadChange('category')}
                                        placeholder="Business"
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-semibold uppercase text-gray-500">Tags</label>
                                    <input
                                        className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                        value={uploadForm.tags}
                                        onChange={handleUploadChange('tags')}
                                        placeholder="agency, saas"
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs font-semibold uppercase text-gray-500">Author</label>
                                    <input
                                        className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                        value={uploadForm.author}
                                        onChange={handleUploadChange('author')}
                                        placeholder="Studio Name"
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-semibold uppercase text-gray-500">Version</label>
                                    <input
                                        className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                        value={uploadForm.version}
                                        onChange={handleUploadChange('version')}
                                        placeholder="1.0.0"
                                    />
                                </div>
                            </div>
                            <div className="flex items-center gap-6 text-sm text-gray-600">
                                <label className="inline-flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={uploadForm.is_marketplace}
                                        onChange={handleUploadChange('is_marketplace')}
                                        className="rounded border-gray-300"
                                    />
                                    Publish to marketplace
                                </label>
                                <label className="inline-flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={uploadForm.is_featured}
                                        onChange={handleUploadChange('is_featured')}
                                        className="rounded border-gray-300"
                                    />
                                    Featured
                                </label>
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">ZIP File</label>
                                <input
                                    type="file"
                                    accept=".zip"
                                    onChange={handleUploadChange('file')}
                                    className="mt-2 w-full text-sm"
                                />
                                <p className="mt-2 text-xs text-gray-400">
                                    Zip must include <code>home.blade.php</code> or <code>index.blade.php</code>.
                                </p>
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-gray-500">Preview Image</label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={handleUploadChange('preview')}
                                    className="mt-2 w-full text-sm"
                                />
                                <p className="mt-2 text-xs text-gray-400">Optional marketplace preview image.</p>
                            </div>
                            <button
                                type="submit"
                                disabled={uploading}
                                className="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 disabled:opacity-60"
                            >
                                <Plus className="w-4 h-4" />
                                {uploading ? 'Uploading...' : 'Upload template'}
                            </button>
                            </form>
                        </div>

                        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Theme Versions</h2>
                            <div className="flex flex-wrap items-center gap-2 text-sm">
                                <select
                                    value={selectedThemeId}
                                    onChange={(e) => setSelectedThemeId(e.target.value)}
                                    className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                >
                                    {themes.map((theme) => (
                                        <option key={theme.id} value={theme.id}>{theme.name}</option>
                                    ))}
                                </select>
                                <button
                                    type="button"
                                    onClick={() => loadThemeVersions(selectedThemeId)}
                                    className="px-3 py-2 rounded-lg border border-gray-200 text-sm"
                                >
                                    Refresh
                                </button>
                            </div>

                            {versionMessage && (
                                <div className="mt-3 text-xs text-emerald-600">{versionMessage}</div>
                            )}

                            <form className="mt-4 space-y-3" onSubmit={handleVersionUpload}>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label className="text-xs font-semibold uppercase text-gray-500">Version</label>
                                        <input
                                            className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                            value={versionForm.version}
                                            onChange={handleVersionChange('version')}
                                            placeholder="1.0.1"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold uppercase text-gray-500">ZIP File</label>
                                        <input
                                            type="file"
                                            accept=".zip"
                                            onChange={handleVersionChange('file')}
                                            className="mt-2 w-full text-sm"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label className="text-xs font-semibold uppercase text-gray-500">Notes</label>
                                    <textarea
                                        className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                        value={versionForm.notes}
                                        onChange={handleVersionChange('notes')}
                                        rows={3}
                                        placeholder="Changelog or fixes"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    disabled={versionUploading}
                                    className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 disabled:opacity-60"
                                >
                                    {versionUploading ? 'Uploading...' : 'Upload New Version'}
                                </button>
                            </form>

                            <div className="mt-4">
                                {versionsLoading ? (
                                    <p className="text-sm text-gray-500">Loading versions...</p>
                                ) : themeVersions.length === 0 ? (
                                    <p className="text-sm text-gray-500">No versions yet.</p>
                                ) : (
                                    <div className="space-y-2 text-xs">
                                        {themeVersions.map((version) => (
                                            <div key={version.id} className="border border-gray-100 rounded-lg px-3 py-2 flex items-center justify-between">
                                                <div>
                                                    <p className="font-semibold text-gray-700">{version.version || 'Unlabeled'}</p>
                                                    <p className="text-[11px] text-gray-400">{version.created_at}</p>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => restoreVersion(version.id)}
                                                    className="text-xs text-blue-600"
                                                >
                                                    Restore
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Templates</h2>
                            {loading ? (
                                <p className="text-sm text-gray-500">Loading...</p>
                            ) : themes.length === 0 ? (
                                <p className="text-sm text-gray-500">No templates yet. Add the first one.</p>
                            ) : (
                                <div className="space-y-3">
                                    {themes.map((theme) => (
                                        <div key={theme.id} className="border border-gray-100 rounded-xl p-4">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <h3 className="font-semibold text-gray-900">{theme.name}</h3>
                                                    <p className="text-xs text-gray-500">{theme.view}</p>
                                                </div>
                                                <button
                                                    onClick={() => toggleTheme(theme)}
                                                    className={`text-xs font-semibold px-3 py-1 rounded-full ${
                                                        theme.is_active
                                                            ? 'bg-green-100 text-green-700'
                                                            : 'bg-gray-100 text-gray-600'
                                                    }`}
                                                >
                                                    {theme.is_active ? 'Active' : 'Disabled'}
                                                </button>
                                            </div>
                                            {theme.description && (
                                                <p className="mt-2 text-sm text-gray-600">{theme.description}</p>
                                            )}
                                            {(theme.category || (theme.tags && theme.tags.length)) && (
                                                <div className="mt-2 text-xs text-gray-500">
                                                    {theme.category && <span className="mr-2">{theme.category}</span>}
                                                    {theme.tags && theme.tags.length ? (
                                                        <span>Tags: {theme.tags.join(', ')}</span>
                                                    ) : null}
                                                </div>
                                            )}
                                            <div className="mt-2 text-xs text-gray-400">Key: {theme.key}</div>
                                            <div className="mt-1 text-xs text-gray-400">
                                                Marketplace: {theme.is_marketplace ? 'Yes' : 'No'} • Featured: {theme.is_featured ? 'Yes' : 'No'}
                                            </div>
                                            <div className="mt-3 flex flex-wrap gap-2 text-xs">
                                                <button
                                                    onClick={() => toggleMarketplace(theme)}
                                                    className={`px-2 py-1 rounded-full ${
                                                        theme.is_marketplace ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600'
                                                    }`}
                                                >
                                                    Marketplace
                                                </button>
                                                <button
                                                    onClick={() => toggleFeatured(theme)}
                                                    className={`px-2 py-1 rounded-full ${
                                                        theme.is_featured ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'
                                                    }`}
                                                >
                                                    Featured
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            ) : (
                <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 text-sm text-gray-500">
                    Only superadmins can manage templates.
                </div>
            )}
        </div>
    );
}
