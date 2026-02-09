import { useEffect, useMemo, useState } from 'react';
import { api } from '../../services/api';
import { Folder, FileText, Upload, Trash2, ArrowLeftRight, ArrowUp, RefreshCw } from 'lucide-react';

export default function FileManager() {
    const [path, setPath] = useState('');
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(false);
    const [newFolderName, setNewFolderName] = useState('');
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    const tenantId = useMemo(() => {
        if (typeof window === 'undefined') return null;
        return window.localStorage.getItem('adminTenantId');
    }, []);

    const loadFiles = async (targetPath = path) => {
        setLoading(true);
        setMessage('');
        setError('');
        try {
            const res = await api.admin.listFiles(targetPath);
            setItems(res?.data?.items || []);
            setPath(res?.data?.path || '');
        } catch (err) {
            setError('Unable to load files. Select a tenant first.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadFiles(path);
    }, [path]);

    const goUp = () => {
        if (!path) return;
        const parts = path.split('/');
        parts.pop();
        setPath(parts.join('/'));
    };

    const createFolder = async () => {
        if (!newFolderName.trim()) return;
        setError('');
        try {
            await api.admin.createFolder({ path, name: newFolderName.trim() });
            setNewFolderName('');
            await loadFiles(path);
        } catch {
            setError('Failed to create folder.');
        }
    };

    const handleUpload = async (event) => {
        const files = Array.from(event.target.files || []);
        if (!files.length) return;
        const formData = new FormData();
        formData.append('path', path);
        files.forEach((file) => formData.append('files[]', file));
        setError('');
        try {
            await api.admin.uploadFiles(formData);
            setMessage(`${files.length} file(s) uploaded.`);
            await loadFiles(path);
        } catch {
            setError('Upload failed.');
        } finally {
            event.target.value = '';
        }
    };

    const deleteItem = async (item) => {
        if (!window.confirm(`Delete ${item.name}?`)) return;
        setError('');
        try {
            await api.admin.deleteFile({ path: item.path });
            await loadFiles(path);
        } catch {
            setError('Delete failed.');
        }
    };

    const renameItem = async (item) => {
        const nextName = window.prompt('New name', item.name);
        if (!nextName || nextName.trim() === item.name) return;
        setError('');
        try {
            await api.admin.renameFile({ path: item.path, name: nextName.trim() });
            await loadFiles(path);
        } catch {
            setError('Rename failed.');
        }
    };

    const handleOpen = (item) => {
        if (item.type === 'dir') {
            setPath(item.path);
            return;
        }
        window.open(api.admin.downloadFileUrl(item.path), '_blank');
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">File Manager</h1>
                    <p className="text-sm text-gray-500">Manage tenant uploads and assets.</p>
                </div>
                <button
                    onClick={() => loadFiles(path)}
                    className="inline-flex items-center gap-2 text-sm px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50"
                >
                    <RefreshCw className="w-4 h-4" />
                    Refresh
                </button>
            </div>

            {tenantId === 'all' && (
                <div className="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-lg text-sm">
                    Select a tenant from the header to browse files.
                </div>
            )}

            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div className="flex flex-wrap items-center gap-3 text-sm text-gray-600">
                    <button
                        onClick={goUp}
                        disabled={!path}
                        className="inline-flex items-center gap-1 px-2 py-1 rounded border border-gray-200 disabled:opacity-40"
                    >
                        <ArrowUp className="w-4 h-4" />
                        Up
                    </button>
                    <span className="text-xs text-gray-400">Path:</span>
                    <span className="font-medium">{path || '/'}</span>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <div className="flex items-center gap-2">
                        <input
                            value={newFolderName}
                            onChange={(e) => setNewFolderName(e.target.value)}
                            placeholder="New folder"
                            className="rounded-lg border border-gray-200 px-3 py-2 text-sm"
                        />
                        <button
                            onClick={createFolder}
                            className="px-3 py-2 rounded-lg bg-gray-900 text-white text-sm"
                        >
                            Create
                        </button>
                    </div>
                    <label className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-sm cursor-pointer hover:bg-gray-50">
                        <Upload className="w-4 h-4" />
                        Upload
                        <input type="file" multiple className="hidden" onChange={handleUpload} />
                    </label>
                </div>

                {(message || error) && (
                    <div className={`text-sm ${error ? 'text-rose-600' : 'text-emerald-600'}`}>
                        {error || message}
                    </div>
                )}

                {loading ? (
                    <p className="text-sm text-gray-500">Loading files...</p>
                ) : items.length === 0 ? (
                    <p className="text-sm text-gray-500">No files yet.</p>
                ) : (
                    <div className="space-y-2 text-sm">
                        {items.map((item) => (
                            <div key={item.path} className="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                                <button
                                    onClick={() => handleOpen(item)}
                                    className="flex items-center gap-2 text-left"
                                >
                                    {item.type === 'dir' ? (
                                        <Folder className="w-4 h-4 text-blue-500" />
                                    ) : (
                                        <FileText className="w-4 h-4 text-gray-500" />
                                    )}
                                    <div>
                                        <p className="font-medium text-gray-800">{item.name}</p>
                                        <p className="text-xs text-gray-400">{item.modified_at || 'n/a'}</p>
                                    </div>
                                </button>
                                <div className="flex items-center gap-3 text-xs text-gray-500">
                                    {item.type === 'file' && item.size !== null && (
                                        <span>{Math.round(item.size / 1024)} KB</span>
                                    )}
                                    <button onClick={() => renameItem(item)} className="text-blue-600">
                                        <ArrowLeftRight className="w-4 h-4" />
                                    </button>
                                    <button onClick={() => deleteItem(item)} className="text-rose-600">
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
