import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../services/api';
import { Lock, Mail, Loader } from 'lucide-react';

export default function Login() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [remember, setRemember] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [sso, setSso] = useState({
        oidcEnabled: false,
        oidcLabel: 'SSO',
        samlEnabled: false,
        samlLabel: 'SAML SSO',
    });
    const [branding, setBranding] = useState({
        brand_name: 'TastyPanel',
        brand_logo_url: '',
        brand_primary_color: '#2563eb',
        brand_secondary_color: '#111827',
        brand_accent_color: '#f97316',
        brand_login_message: 'Admin Dashboard',
        brand_favicon_url: '',
    });
    const navigate = useNavigate();

    useEffect(() => {
        // Check if user is already logged in (only once on mount)
        let isMounted = true;
        let timeoutId;
        
        const checkAuth = async () => {
            try {
                try {
                    const brand = await api.admin.getBranding();
                    if (isMounted && brand) {
                        setBranding((prev) => ({ ...prev, ...brand }));
                    }
                } catch {
                    // ignore branding errors
                }
                const setup = await api.admin.getSetupStatus();
                if (isMounted && setup?.needs_setup) {
                    navigate('/admin/setup');
                    return;
                }
                try {
                    const ssoStatus = await api.admin.getSsoStatus();
                    if (isMounted && ssoStatus) {
                        setSso({
                            oidcEnabled: !!(ssoStatus.oidc_enabled ?? ssoStatus.enabled),
                            oidcLabel: ssoStatus.oidc_label || ssoStatus.label || 'SSO',
                            samlEnabled: !!ssoStatus.saml_enabled,
                            samlLabel: ssoStatus.saml_label || 'SAML SSO',
                        });
                    }
                } catch {
                    // ignore
                }
                const response = await api.admin.getUser();
                if (isMounted && response && response.user) {
                    if (response.user.two_factor_enabled && !response.two_factor_verified) {
                        navigate('/admin/2fa');
                        return;
                    }
                    if (response.user.force_password_reset) {
                        navigate('/admin/force-password');
                        return;
                    }
                    navigate('/admin/dashboard');
                }
            } catch (err) {
                // Not authenticated, stay on login page
                // 401 is expected for non-authenticated users, so we ignore it silently
            }
        };
        
        // Only check once when component mounts (with small delay to avoid blocking)
        timeoutId = setTimeout(() => {
            checkAuth();
        }, 100);
        
        // Cleanup function to prevent state updates after unmount
        return () => {
            isMounted = false;
            if (timeoutId) clearTimeout(timeoutId);
        };
    }, []); // Empty dependency array - only run once on mount

    useEffect(() => {
        if (branding?.brand_favicon_url) {
            const link = document.querySelector("link[rel='icon']") || document.createElement('link');
            link.rel = 'icon';
            link.href = branding.brand_favicon_url;
            document.head.appendChild(link);
        }
        if (branding?.brand_name) {
            document.title = `${branding.brand_name} - Login`;
        }
    }, [branding]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setLoading(true);

        try {
            const response = await api.admin.login(email, password, remember);
            if (response.success) {
                if (response.requires_2fa) {
                    navigate('/admin/2fa');
                    return;
                }
                if (response.must_change_password) {
                    navigate('/admin/force-password');
                } else {
                    navigate('/admin/dashboard');
                }
            } else {
                setError(response.message || 'فشل تسجيل الدخول');
            }
        } catch (err) {
            setError('حدث خطأ أثناء تسجيل الدخول. يرجى المحاولة مرة أخرى.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
            <div className="max-w-md w-full">
                <div className="bg-white rounded-2xl shadow-xl p-8">
                    {/* Logo/Header */}
                    <div className="text-center mb-8">
                        {branding.brand_logo_url ? (
                            <img src={branding.brand_logo_url} alt={branding.brand_name} className="mx-auto h-12 mb-4" />
                        ) : null}
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">{branding.brand_name || 'TastyPanel'}</h1>
                        <p className="text-gray-600">{branding.brand_login_message || 'Admin Dashboard'}</p>
                    </div>

                    {/* Error Message */}
                    {error && (
                        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                            {error}
                        </div>
                    )}

                    {/* Login Form */}
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Email Field */}
                        <div>
                            <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                                البريد الإلكتروني
                            </label>
                            <div className="relative">
                                <Mail className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                <input
                                    id="email"
                                    type="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    required
                                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                                    placeholder="admin@example.com"
                                />
                            </div>
                        </div>

                        {/* Password Field */}
                        <div>
                            <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-2">
                                كلمة المرور
                            </label>
                            <div className="relative">
                                <Lock className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                <input
                                    id="password"
                                    type="password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    required
                                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                                    placeholder="••••••••"
                                />
                            </div>
                        </div>

                        {/* Remember Me */}
                        <div className="flex items-center">
                            <input
                                id="remember"
                                type="checkbox"
                                checked={remember}
                                onChange={(e) => setRemember(e.target.checked)}
                                className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            />
                            <label htmlFor="remember" className="ml-2 text-sm text-gray-700">
                                تذكرني
                            </label>
                        </div>

                        {/* Submit Button */}
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            style={{ backgroundColor: branding.brand_primary_color || '#2563eb' }}
                        >
                            {loading ? (
                                <>
                                    <Loader className="w-5 h-5 animate-spin" />
                                    جاري تسجيل الدخول...
                                </>
                            ) : (
                                'تسجيل الدخول'
                            )}
                        </button>
                    </form>

                    {sso.oidcEnabled || sso.samlEnabled ? (
                        <div className="mt-6">
                            <div className="flex items-center gap-2 text-xs text-gray-400 mb-3">
                                <span className="flex-1 h-px bg-gray-200" />
                                <span>OR</span>
                                <span className="flex-1 h-px bg-gray-200" />
                            </div>
                            <div className="space-y-3">
                                {sso.oidcEnabled && (
                                    <a
                                        href="/admin/sso/redirect"
                                        className="w-full inline-flex items-center justify-center gap-2 border border-gray-300 rounded-lg py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                    >
                                        Continue with {sso.oidcLabel}
                                    </a>
                                )}
                                {sso.samlEnabled && (
                                    <a
                                        href="/admin/saml/login"
                                        className="w-full inline-flex items-center justify-center gap-2 border border-gray-300 rounded-lg py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                    >
                                        Continue with {sso.samlLabel}
                                    </a>
                                )}
                            </div>
                        </div>
                    ) : null}
                </div>

                {/* Footer */}
                <p className="text-center text-sm text-gray-600 mt-6">
                    © 2025 TastyPanel. All rights reserved.
                </p>
            </div>
        </div>
    );
}
