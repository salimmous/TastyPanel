import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../../services/api';
import { Shield, Loader } from 'lucide-react';

export default function TwoFactor() {
    const [code, setCode] = useState('');
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const navigate = useNavigate();

    useEffect(() => {
        const check = async () => {
            const res = await api.admin.getUser();
            if (!res?.user) {
                navigate('/login');
                return;
            }
            if (!res.user.two_factor_enabled) {
                navigate('/admin/dashboard');
                return;
            }
            if (res.two_factor_verified) {
                navigate('/admin/dashboard');
            }
        };
        check();
    }, [navigate]);

    const handleVerify = async (event) => {
        event.preventDefault();
        setLoading(true);
        setError('');
        setMessage('');
        try {
            const res = await api.admin.verifyTwoFactor(code);
            if (res?.success) {
                navigate('/admin/dashboard');
            } else {
                setError(res?.message || 'Verification failed.');
            }
        } catch (err) {
            setError('Verification failed.');
        } finally {
            setLoading(false);
        }
    };

    const handleResend = async () => {
        setMessage('');
        setError('');
        try {
            await api.admin.requestTwoFactor();
            setMessage('Code sent to your email.');
        } catch (err) {
            setError('Unable to send code.');
        }
    };

    return (
        <div className="min-h-screen bg-slate-950 flex items-center justify-center p-6">
            <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
                <div className="flex items-center gap-3 mb-6">
                    <div className="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center">
                        <Shield className="w-5 h-5" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Two-Factor Login</h1>
                        <p className="text-sm text-slate-500">Enter the code sent to your email.</p>
                    </div>
                </div>

                {error && (
                    <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {error}
                    </div>
                )}
                {message && (
                    <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {message}
                    </div>
                )}

                <form onSubmit={handleVerify} className="space-y-4">
                    <div>
                        <label className="text-xs font-semibold uppercase text-slate-500">Verification code</label>
                        <input
                            value={code}
                            onChange={(e) => setCode(e.target.value)}
                            required
                            className="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
                            placeholder="123456"
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
                                Verifying...
                            </span>
                        ) : (
                            'Verify'
                        )}
                    </button>
                </form>
                <button
                    onClick={handleResend}
                    className="mt-4 w-full text-xs text-slate-500 hover:text-slate-700"
                >
                    Resend code
                </button>
            </div>
        </div>
    );
}
