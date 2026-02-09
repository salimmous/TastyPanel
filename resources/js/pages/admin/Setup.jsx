import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../../services/api';
import { Shield, Copy, CheckCircle, Loader } from 'lucide-react';

export default function Setup() {
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [loading, setLoading] = useState(false);
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [copied, setCopied] = useState(false);
    const navigate = useNavigate();

    useEffect(() => {
        let active = true;
        const checkSetup = async () => {
            try {
                const response = await api.admin.getSetupStatus();
                if (active && !response?.needs_setup) {
                    navigate('/login');
                }
            } catch {
                // If setup status fails, keep page visible
            }
        };
        checkSetup();
        return () => {
            active = false;
        };
    }, [navigate]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError('');
        setLoading(true);
        try {
            const response = await api.admin.createInitialAdmin({ name, email });
            if (response?.temporary_password) {
                setPassword(response.temporary_password);
            } else {
                setError(response?.message || 'Setup failed.');
            }
        } catch (err) {
            setError('Setup failed. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const handleCopy = async () => {
        if (!password) return;
        try {
            await navigator.clipboard.writeText(password);
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        } catch {
            setCopied(false);
        }
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-950 flex items-center justify-center p-6">
            <div className="max-w-lg w-full">
                <div className="bg-white rounded-2xl shadow-2xl p-8">
                    <div className="flex items-center gap-3 mb-6">
                        <div className="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center">
                            <Shield className="w-6 h-6" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">Platform Setup</h1>
                            <p className="text-sm text-slate-500">Create the first superadmin account.</p>
                        </div>
                    </div>

                    {error && (
                        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {error}
                        </div>
                    )}

                    {password ? (
                        <div className="space-y-4">
                            <div className="flex items-center gap-2 text-emerald-600 text-sm font-semibold">
                                <CheckCircle className="w-4 h-4" />
                                Admin created. Save the temporary password.
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p className="text-xs uppercase text-slate-500">Temporary password</p>
                                <div className="mt-2 flex items-center justify-between gap-3">
                                    <code className="text-lg font-semibold text-slate-900 break-all">{password}</code>
                                    <button
                                        onClick={handleCopy}
                                        className="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 hover:text-slate-900"
                                    >
                                        {copied ? 'Copied' : 'Copy'}
                                        <Copy className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                            <p className="text-sm text-slate-500">
                                Log in with this password, then you will be forced to set a new password.
                            </p>
                            <button
                                onClick={() => navigate('/login')}
                                className="w-full rounded-lg bg-slate-900 text-white py-3 text-sm font-semibold hover:bg-slate-800"
                            >
                                Go to Login
                            </button>
                        </div>
                    ) : (
                        <form onSubmit={handleSubmit} className="space-y-5">
                            <div>
                                <label className="text-xs font-semibold uppercase text-slate-500">Full name</label>
                                <input
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    required
                                    className="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
                                    placeholder="Admin Name"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold uppercase text-slate-500">Email</label>
                                <input
                                    type="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    required
                                    className="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
                                    placeholder="admin@example.com"
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={loading}
                                className="w-full rounded-lg bg-slate-900 text-white py-3 text-sm font-semibold hover:bg-slate-800 disabled:opacity-60"
                            >
                                {loading ? (
                                    <span className="inline-flex items-center gap-2">
                                        <Loader className="w-4 h-4 animate-spin" />
                                        Creating...
                                    </span>
                                ) : (
                                    'Create Admin'
                                )}
                            </button>
                        </form>
                    )}
                </div>
            </div>
        </div>
    );
}
