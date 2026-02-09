import { useEffect, useState } from 'react';
import { Save, Sparkles, Image, Share2, RefreshCw } from 'lucide-react';
import { api } from '../../services/api';

const defaultSettings = {
    openai: {
        enabled: false,
        api_key: '',
        model: 'gpt-4o-mini',
        temperature: 0.7,
        max_tokens: 1200,
        system_prompt: '',
    },
    midjourney: {
        enabled: false,
        bot_token: '',
        guild_id: '',
        channel_id: '',
        webhook_url: '',
        default_style: '',
    },
    canva: {
        enabled: false,
        template_id: '',
        brand_kit_id: '',
        auto_brand: true,
        profile: {
            display_name: '',
        },
        oauth: {
            access_token: '',
            refresh_token: '',
            expires_at: null,
            token_type: '',
            scopes: [],
        },
    },
    pinterest: {
        enabled: false,
        access_token: '',
        board_id: '',
        default_title: '',
        default_description: '',
        link_format: 'canonical',
    },
    pipeline: {
        generate_title: true,
        generate_excerpt: true,
        generate_image: true,
        auto_tag: true,
        auto_category: true,
        auto_publish: false,
    },
    content: {
        topics: [],
        language: 'en',
        voice: '',
        min_words: 400,
        max_words: 900,
    },
    schedule: {
        enabled: false,
        posts_per_day: 1,
        publish_status: 'draft',
        timezone: 'UTC',
        window_start: '08:00',
        window_end: '22:00',
        environment: 'production',
    },
};

export default function AutoPostSettings() {
    const [settings, setSettings] = useState(defaultSettings);
    const [status, setStatus] = useState({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [testing, setTesting] = useState({});
    const [runs, setRuns] = useState([]);
    const [runTopic, setRunTopic] = useState('');
    const [running, setRunning] = useState(false);
    const [topicsText, setTopicsText] = useState('');

    useEffect(() => {
        loadSettings();
    }, []);

    const loadSettings = async () => {
        setLoading(true);
        setError('');
        try {
            const response = await api.admin.getAutomationSettings();
            if (response?.data) {
                setSettings((prev) => ({ ...prev, ...response.data }));
                const topics = response.data?.content?.topics;
                if (Array.isArray(topics)) {
                    setTopicsText(topics.join('\n'));
                } else if (topics) {
                    setTopicsText(String(topics));
                }
            }
            if (response?.status) {
                setStatus(response.status);
            }
            await loadRuns();
        } catch (err) {
            setError('Failed to load automation settings.');
        } finally {
            setLoading(false);
        }
    };

    const updateSection = (section, field, value) => {
        setSettings((prev) => ({
            ...prev,
            [section]: {
                ...prev[section],
                [field]: value,
            },
        }));
    };

    const handleSave = async () => {
        setSaving(true);
        setMessage('');
        setError('');
        try {
            const payload = {
                ...settings,
                content: {
                    ...settings.content,
                    topics: topicsText
                        ? topicsText.split(/\r?\n|,/).map((t) => t.trim()).filter(Boolean)
                        : [],
                },
            };
            const response = await api.admin.updateAutomationSettings(payload);
            if (response?.data) {
                setSettings((prev) => ({ ...prev, ...response.data }));
                const topics = response.data?.content?.topics;
                if (Array.isArray(topics)) {
                    setTopicsText(topics.join('\n'));
                }
            }
            if (response?.status) {
                setStatus(response.status);
            }
            setMessage('Automation settings saved.');
        } catch (err) {
            setError('Failed to save settings.');
        } finally {
            setSaving(false);
        }
    };

    const loadRuns = async () => {
        const res = await api.admin.getAutomationRuns();
        setRuns(res?.data || []);
    };

    const runNow = async () => {
        setRunning(true);
        setMessage('');
        setError('');
        try {
            await api.admin.runAutomation({ topic: runTopic || undefined });
            setRunTopic('');
            await loadRuns();
            setMessage('Automation run triggered.');
        } catch (err) {
            setError('Failed to run automation.');
        } finally {
            setRunning(false);
        }
    };

    const runTest = async (provider) => {
        setTesting((prev) => ({ ...prev, [provider]: true }));
        setMessage('');
        setError('');
        try {
            const response = await api.admin.testAutomationProvider(provider);
            if (response?.success) {
                setMessage(response.message || 'Connection looks good.');
            } else {
                setError(response?.message || 'Test failed.');
            }
        } catch (err) {
            setError('Test failed.');
        } finally {
            setTesting((prev) => ({ ...prev, [provider]: false }));
            await loadSettings();
        }
    };

    const connectCanva = async () => {
        setTesting((prev) => ({ ...prev, canvaConnect: true }));
        setMessage('');
        setError('');
        try {
            const response = await api.admin.getCanvaConnectUrl();
            if (response?.url) {
                window.location.href = response.url;
                return;
            }
            setError(response?.message || 'Failed to start Canva connection.');
        } catch (err) {
            setError('Failed to start Canva connection.');
        } finally {
            setTesting((prev) => ({ ...prev, canvaConnect: false }));
        }
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Automation Studio</h1>
                    <p className="text-sm text-gray-500">Connect OpenAI, Midjourney, Canva, and Pinterest, then schedule auto‑posting.</p>
                </div>
                <button
                    onClick={handleSave}
                    disabled={saving}
                    className="flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 disabled:opacity-60"
                >
                    <Save className="w-4 h-4" />
                    {saving ? 'Saving...' : 'Save Settings'}
                </button>
            </div>

            {loading ? (
                <div className="bg-white rounded-2xl border border-gray-200 p-6 text-sm text-gray-500">Loading...</div>
            ) : (
                <>
                    {message && (
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {message}
                        </div>
                    )}
                    {error && (
                        <div className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {error}
                        </div>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2 text-gray-700">
                                    <Sparkles className="w-4 h-4" />
                                    <h2 className="text-sm font-semibold">OpenAI Writer</h2>
                                </div>
                                <span className={`text-[11px] uppercase ${status?.openai ? 'text-emerald-600' : 'text-gray-400'}`}>
                                    {status?.openai ? 'Configured' : 'Not set'}
                                </span>
                            </div>
                            <div className="grid gap-3 text-xs">
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={settings.openai.enabled}
                                        onChange={(e) => updateSection('openai', 'enabled', e.target.checked)}
                                    />
                                    Enable OpenAI
                                </label>
                                <input
                                    type="password"
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    placeholder="OpenAI API key"
                                    value={settings.openai.api_key}
                                    onChange={(e) => updateSection('openai', 'api_key', e.target.value)}
                                />
                                <div className="grid grid-cols-2 gap-3">
                                    <input
                                        className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                        placeholder="Model"
                                        value={settings.openai.model}
                                        onChange={(e) => updateSection('openai', 'model', e.target.value)}
                                    />
                                    <input
                                        type="number"
                                        step="0.1"
                                        className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                        placeholder="Temperature"
                                        value={settings.openai.temperature}
                                        onChange={(e) => updateSection('openai', 'temperature', Number(e.target.value))}
                                    />
                                </div>
                                <textarea
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    rows={3}
                                    placeholder="System prompt (optional)"
                                    value={settings.openai.system_prompt}
                                    onChange={(e) => updateSection('openai', 'system_prompt', e.target.value)}
                                />
                                <button
                                    onClick={() => runTest('openai')}
                                    disabled={testing.openai}
                                    className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    <RefreshCw className={`w-3 h-3 ${testing.openai ? 'animate-spin' : ''}`} />
                                    {testing.openai ? 'Testing...' : 'Test OpenAI'}
                                </button>
                            </div>
                        </div>

                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2 text-gray-700">
                                    <Image className="w-4 h-4" />
                                    <h2 className="text-sm font-semibold">Midjourney (Discord Bot)</h2>
                                </div>
                                <span className={`text-[11px] uppercase ${status?.midjourney ? 'text-emerald-600' : 'text-gray-400'}`}>
                                    {status?.midjourney ? 'Configured' : 'Not set'}
                                </span>
                            </div>
                            <div className="grid gap-3 text-xs">
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={settings.midjourney.enabled}
                                        onChange={(e) => updateSection('midjourney', 'enabled', e.target.checked)}
                                    />
                                    Enable Midjourney
                                </label>
                                <input
                                    type="password"
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    placeholder="Discord bot token"
                                    value={settings.midjourney.bot_token}
                                    onChange={(e) => updateSection('midjourney', 'bot_token', e.target.value)}
                                />
                                <input
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    placeholder="Guild ID"
                                    value={settings.midjourney.guild_id}
                                    onChange={(e) => updateSection('midjourney', 'guild_id', e.target.value)}
                                />
                                <input
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    placeholder="Channel ID"
                                    value={settings.midjourney.channel_id}
                                    onChange={(e) => updateSection('midjourney', 'channel_id', e.target.value)}
                                />
                                <input
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    placeholder="Webhook URL (optional)"
                                    value={settings.midjourney.webhook_url}
                                    onChange={(e) => updateSection('midjourney', 'webhook_url', e.target.value)}
                                />
                                <input
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    placeholder="Default style (e.g. cinematic)"
                                    value={settings.midjourney.default_style}
                                    onChange={(e) => updateSection('midjourney', 'default_style', e.target.value)}
                                />
                                <button
                                    onClick={() => runTest('midjourney')}
                                    disabled={testing.midjourney}
                                    className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    <RefreshCw className={`w-3 h-3 ${testing.midjourney ? 'animate-spin' : ''}`} />
                                    {testing.midjourney ? 'Testing...' : 'Test Midjourney'}
                                </button>
                            </div>
                        </div>

                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2 text-gray-700">
                                    <Image className="w-4 h-4" />
                                    <h2 className="text-sm font-semibold">Canva Templates</h2>
                                </div>
                                <span className={`text-[11px] uppercase ${status?.canva ? 'text-emerald-600' : 'text-gray-400'}`}>
                                    {status?.canva ? 'Configured' : 'Not set'}
                                </span>
                            </div>
                            <div className="grid gap-3 text-xs">
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={settings.canva.enabled}
                                        onChange={(e) => updateSection('canva', 'enabled', e.target.checked)}
                                    />
                                    Enable Canva
                                </label>
                                {settings.canva?.profile?.display_name && (
                                    <div className="text-[11px] text-gray-500">
                                        Connected as {settings.canva.profile.display_name}
                                    </div>
                                )}
                                <button
                                    onClick={connectCanva}
                                    disabled={testing.canvaConnect}
                                    className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    <RefreshCw className={`w-3 h-3 ${testing.canvaConnect ? 'animate-spin' : ''}`} />
                                    {testing.canvaConnect ? 'Connecting...' : 'Connect Canva'}
                                </button>
                                <input
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    placeholder="Template ID"
                                    value={settings.canva.template_id}
                                    onChange={(e) => updateSection('canva', 'template_id', e.target.value)}
                                />
                                <input
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    placeholder="Brand kit ID (optional)"
                                    value={settings.canva.brand_kit_id}
                                    onChange={(e) => updateSection('canva', 'brand_kit_id', e.target.value)}
                                />
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={settings.canva.auto_brand}
                                        onChange={(e) => updateSection('canva', 'auto_brand', e.target.checked)}
                                    />
                                    Apply brand kit automatically
                                </label>
                                <button
                                    onClick={() => runTest('canva')}
                                    disabled={testing.canva}
                                    className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    <RefreshCw className={`w-3 h-3 ${testing.canva ? 'animate-spin' : ''}`} />
                                    {testing.canva ? 'Testing...' : 'Test Canva'}
                                </button>
                            </div>
                        </div>

                        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2 text-gray-700">
                                    <Share2 className="w-4 h-4" />
                                    <h2 className="text-sm font-semibold">Pinterest Auto‑Publish</h2>
                                </div>
                                <span className={`text-[11px] uppercase ${status?.pinterest ? 'text-emerald-600' : 'text-gray-400'}`}>
                                    {status?.pinterest ? 'Configured' : 'Not set'}
                                </span>
                            </div>
                            <div className="grid gap-3 text-xs">
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={settings.pinterest.enabled}
                                        onChange={(e) => updateSection('pinterest', 'enabled', e.target.checked)}
                                    />
                                    Enable Pinterest
                                </label>
                                <input
                                    type="password"
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    placeholder="Access token"
                                    value={settings.pinterest.access_token}
                                    onChange={(e) => updateSection('pinterest', 'access_token', e.target.value)}
                                />
                                <input
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    placeholder="Board ID"
                                    value={settings.pinterest.board_id}
                                    onChange={(e) => updateSection('pinterest', 'board_id', e.target.value)}
                                />
                                <input
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    placeholder="Default pin title"
                                    value={settings.pinterest.default_title}
                                    onChange={(e) => updateSection('pinterest', 'default_title', e.target.value)}
                                />
                                <textarea
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2"
                                    rows={2}
                                    placeholder="Default pin description"
                                    value={settings.pinterest.default_description}
                                    onChange={(e) => updateSection('pinterest', 'default_description', e.target.value)}
                                />
                                <button
                                    onClick={() => runTest('pinterest')}
                                    disabled={testing.pinterest}
                                    className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    <RefreshCw className={`w-3 h-3 ${testing.pinterest ? 'animate-spin' : ''}`} />
                                    {testing.pinterest ? 'Testing...' : 'Test Pinterest'}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-sm font-semibold text-gray-700">Automation Pipeline</h2>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={settings.pipeline.generate_title}
                                    onChange={(e) => updateSection('pipeline', 'generate_title', e.target.checked)}
                                />
                                Generate titles
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={settings.pipeline.generate_excerpt}
                                    onChange={(e) => updateSection('pipeline', 'generate_excerpt', e.target.checked)}
                                />
                                Generate excerpts
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={settings.pipeline.generate_image}
                                    onChange={(e) => updateSection('pipeline', 'generate_image', e.target.checked)}
                                />
                                Generate cover images
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={settings.pipeline.auto_tag}
                                    onChange={(e) => updateSection('pipeline', 'auto_tag', e.target.checked)}
                                />
                                Auto tag content
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={settings.pipeline.auto_category}
                                    onChange={(e) => updateSection('pipeline', 'auto_category', e.target.checked)}
                                />
                                Auto assign categories
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={settings.pipeline.auto_publish}
                                    onChange={(e) => updateSection('pipeline', 'auto_publish', e.target.checked)}
                                />
                                Auto publish when ready
                            </label>
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-sm font-semibold text-gray-700">Content Studio</h2>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <div className="md:col-span-2">
                                <label className="text-[11px] uppercase text-gray-500">Topics (one per line)</label>
                                <textarea
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2"
                                    rows={4}
                                    placeholder="e.g. Easy weeknight dinners&#10;Mediterranean salads&#10;Budget travel hacks"
                                    value={topicsText}
                                    onChange={(e) => setTopicsText(e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="text-[11px] uppercase text-gray-500">Language</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2"
                                    value={settings.content.language}
                                    onChange={(e) => updateSection('content', 'language', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="text-[11px] uppercase text-gray-500">Voice / Tone</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2"
                                    placeholder="Friendly, concise, expert"
                                    value={settings.content.voice}
                                    onChange={(e) => updateSection('content', 'voice', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="text-[11px] uppercase text-gray-500">Min words</label>
                                <input
                                    type="number"
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2"
                                    value={settings.content.min_words}
                                    onChange={(e) => updateSection('content', 'min_words', Number(e.target.value))}
                                />
                            </div>
                            <div>
                                <label className="text-[11px] uppercase text-gray-500">Max words</label>
                                <input
                                    type="number"
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2"
                                    value={settings.content.max_words}
                                    onChange={(e) => updateSection('content', 'max_words', Number(e.target.value))}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-sm font-semibold text-gray-700">Scheduling</h2>
                            <label className="flex items-center gap-2 text-xs">
                                <input
                                    type="checkbox"
                                    checked={settings.schedule.enabled}
                                    onChange={(e) => updateSection('schedule', 'enabled', e.target.checked)}
                                />
                                Enable scheduler
                            </label>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <div>
                                <label className="text-[11px] uppercase text-gray-500">Posts / day</label>
                                <input
                                    type="number"
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2"
                                    value={settings.schedule.posts_per_day}
                                    onChange={(e) => updateSection('schedule', 'posts_per_day', Number(e.target.value))}
                                />
                            </div>
                            <div>
                                <label className="text-[11px] uppercase text-gray-500">Publish status</label>
                                <select
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2"
                                    value={settings.schedule.publish_status}
                                    onChange={(e) => updateSection('schedule', 'publish_status', e.target.value)}
                                >
                                    <option value="draft">Draft</option>
                                    <option value="review">Review</option>
                                    <option value="approved">Approved</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                            <div>
                                <label className="text-[11px] uppercase text-gray-500">Environment</label>
                                <select
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2"
                                    value={settings.schedule.environment}
                                    onChange={(e) => updateSection('schedule', 'environment', e.target.value)}
                                >
                                    <option value="production">Production</option>
                                    <option value="staging">Staging</option>
                                    <option value="preview">Preview</option>
                                </select>
                            </div>
                            <div>
                                <label className="text-[11px] uppercase text-gray-500">Timezone</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2"
                                    value={settings.schedule.timezone}
                                    onChange={(e) => updateSection('schedule', 'timezone', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="text-[11px] uppercase text-gray-500">Window start</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2"
                                    value={settings.schedule.window_start}
                                    onChange={(e) => updateSection('schedule', 'window_start', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="text-[11px] uppercase text-gray-500">Window end</label>
                                <input
                                    className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2"
                                    value={settings.schedule.window_end}
                                    onChange={(e) => updateSection('schedule', 'window_end', e.target.value)}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-sm font-semibold text-gray-700">Automation Runs</h2>
                            <button
                                onClick={loadRuns}
                                className="text-xs font-semibold text-gray-600 hover:text-gray-800"
                            >
                                Refresh
                            </button>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                            <input
                                className="rounded-lg border border-gray-200 px-3 py-2"
                                placeholder="Optional topic override"
                                value={runTopic}
                                onChange={(e) => setRunTopic(e.target.value)}
                            />
                            <button
                                onClick={runNow}
                                disabled={running}
                                className="px-3 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800 disabled:opacity-60"
                            >
                                {running ? 'Running...' : 'Run Now'}
                            </button>
                        </div>
                        <div className="space-y-2 text-xs">
                            {runs.length === 0 ? (
                                <p className="text-gray-500">No automation runs yet.</p>
                            ) : (
                                runs.map((run) => (
                                    <div key={run.id} className="rounded-lg border border-gray-100 px-3 py-2">
                                        <div className="flex items-center justify-between">
                                            <span className="font-semibold text-gray-700">
                                                {run.status} • {run.trigger}
                                            </span>
                                            <span className="text-[11px] text-gray-400">
                                                {run.finished_at ? new Date(run.finished_at).toLocaleString() : '—'}
                                            </span>
                                        </div>
                                        <div className="text-[11px] text-gray-500 mt-1">
                                            {run.title || run.topic || 'Untitled'}
                                        </div>
                                        {run.error && (
                                            <div className="text-[11px] text-rose-600 mt-1">{run.error}</div>
                                        )}
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
