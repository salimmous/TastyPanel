import { useEffect, useState } from 'react';
import { Sparkles, Plus, RefreshCw } from 'lucide-react';
import { api } from '../../services/api';

export default function AIWriter() {
    const [loading, setLoading] = useState(true);
    const [settingsStatus, setSettingsStatus] = useState({});
    const [form, setForm] = useState({
        title: '',
        summary: '',
        status: 'draft',
    });
    const [creating, setCreating] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    useEffect(() => {
        loadStatus();
    }, []);

    const loadStatus = async () => {
        setLoading(true);
        try {
            const response = await api.admin.getAutomationSettings();
            setSettingsStatus(response?.status || {});
        } catch (err) {
            setSettingsStatus({});
        } finally {
            setLoading(false);
        }
    };

    const createDraft = async () => {
        if (!form.title) return;
        setCreating(true);
        setMessage('');
        setError('');
        try {
            const response = await api.admin.createAiDraft({
                title: form.title,
                summary: form.summary,
                status: form.status,
            });
            if (response?.data?.id) {
                setMessage('Draft created in Posts.');
                setForm({ title: '', summary: '', status: form.status });
            } else {
                setError('Failed to create draft.');
            }
        } catch (err) {
            setError('Failed to create draft.');
        } finally {
            setCreating(false);
        }
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">AI Writer</h1>
                    <p className="text-sm text-gray-500">Generate new drafts using your automation settings.</p>
                </div>
                <button
                    onClick={loadStatus}
                    className="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50"
                >
                    <RefreshCw className="w-4 h-4" />
                    Refresh
                </button>
            </div>

            {loading ? (
                <div className="bg-white rounded-2xl border border-gray-200 p-6 text-sm text-gray-500">Loading...</div>
            ) : (
                <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                        <Sparkles className="w-4 h-4" />
                        <span>OpenAI: {settingsStatus.openai ? 'Ready' : 'Not configured'}</span>
                    </div>
                    {!settingsStatus.openai && (
                        <p className="text-xs text-gray-500">
                            Add your OpenAI key in Automation Studio to unlock full AI writing.
                        </p>
                    )}

                    <div className="grid gap-3">
                        <input
                            className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                            placeholder="Article title"
                            value={form.title}
                            onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))}
                        />
                        <textarea
                            className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                            rows={4}
                            placeholder="Prompt or summary for the draft"
                            value={form.summary}
                            onChange={(e) => setForm((prev) => ({ ...prev, summary: e.target.value }))}
                        />
                        <select
                            className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                            value={form.status}
                            onChange={(e) => setForm((prev) => ({ ...prev, status: e.target.value }))}
                        >
                            <option value="draft">Draft</option>
                            <option value="review">Review</option>
                            <option value="approved">Approved</option>
                            <option value="published">Published</option>
                        </select>
                    </div>

                    {message && (
                        <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
                            {message}
                        </div>
                    )}
                    {error && (
                        <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-700">
                            {error}
                        </div>
                    )}

                    <button
                        onClick={createDraft}
                        disabled={creating}
                        className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-60"
                    >
                        <Plus className="w-4 h-4" />
                        {creating ? 'Creating...' : 'Generate Draft'}
                    </button>
                </div>
            )}
        </div>
    );
}
