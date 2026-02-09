import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../services/api';
import {
    Activity,
    AlertCircle,
    ArrowRight,
    ArrowUpRight,
    BarChart3,
    Bell,
    Calendar,
    Clock,
    ExternalLink,
    FileText,
    Folder,
    Plus,
    Sparkles,
    TrendingUp,
    Users,
} from 'lucide-react';

export default function AdminDashboard() {
    const [loading, setLoading] = useState(true);
    const [stats, setStats] = useState(null);
    const navigate = useNavigate();

    useEffect(() => {
        loadDashboard();
    }, []);

    const loadDashboard = async () => {
        try {
            const statsResponse = await api.admin.getDashboardStats();
            setStats(statsResponse);
        } catch (err) {
            navigate('/login');
        } finally {
            setLoading(false);
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

    const totalPosts = stats?.stats?.total_posts || 0;
    const totalRecipes = stats?.stats?.total_recipes || 0;
    const totalArticles = stats?.stats?.total_articles || 0;
    const totalCategories = stats?.stats?.total_categories || 0;
    const totalUsers = stats?.stats?.total_users || 0;
    const todayPosts = stats?.stats?.today_posts || 0;
    const todayRecipes = stats?.stats?.today_recipes || 0;
    const todayArticles = stats?.stats?.today_articles || 0;

    const weekEntries = Object.entries(stats?.last_week_performance || {});
    const weekValues = weekEntries.map((entry) => entry[1]);
    const weekTotal = weekValues.reduce((sum, value) => sum + value, 0);
    const weekMax = weekValues.length ? Math.max(...weekValues) : 0;
    const weekAvg = weekValues.length ? Math.round(weekTotal / weekValues.length) : 0;

    const midpoint = Math.max(1, Math.floor(weekValues.length / 2));
    const firstHalf = weekValues.slice(0, midpoint);
    const secondHalf = weekValues.slice(midpoint);
    const firstAvg = firstHalf.length
        ? firstHalf.reduce((sum, value) => sum + value, 0) / firstHalf.length
        : 0;
    const secondAvg = secondHalf.length
        ? secondHalf.reduce((sum, value) => sum + value, 0) / secondHalf.length
        : 0;
    const trendDelta = Math.round(secondAvg - firstAvg);
    const trendUp = trendDelta >= 0;

    const latestPosts = stats?.latest_posts || [];
    const popularPosts = stats?.popular_posts || [];

    const recipesPct = totalPosts ? Math.round((totalRecipes / totalPosts) * 100) : 0;
    const articlesPct = totalPosts ? Math.round((totalArticles / totalPosts) * 100) : 0;

    const now = new Date();
    const formattedDate = new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(now);
    const greeting = now.getHours() < 12 ? 'Good morning' : now.getHours() < 18 ? 'Good afternoon' : 'Good evening';

    const statCards = [
        {
            label: 'Total Posts',
            value: totalPosts,
            hint: `${todayPosts} today`,
            icon: FileText,
            accent: 'from-emerald-500/20 via-emerald-500/5 to-transparent',
            iconStyle: 'bg-emerald-500/15 text-emerald-600',
        },
        {
            label: 'Recipes',
            value: totalRecipes,
            hint: `${todayRecipes} today`,
            icon: FileText,
            accent: 'from-sky-500/20 via-sky-500/5 to-transparent',
            iconStyle: 'bg-sky-500/15 text-sky-600',
        },
        {
            label: 'Articles',
            value: totalArticles,
            hint: `${todayArticles} today`,
            icon: FileText,
            accent: 'from-amber-500/25 via-amber-500/10 to-transparent',
            iconStyle: 'bg-amber-500/20 text-amber-700',
        },
        {
            label: 'Active Users',
            value: totalUsers,
            hint: `${totalCategories} categories`,
            icon: Users,
            accent: 'from-slate-500/20 via-slate-500/5 to-transparent',
            iconStyle: 'bg-slate-500/15 text-slate-600',
        },
    ];

    const quickActions = [
        {
            label: 'New Post',
            description: 'Recipe or article',
            icon: Plus,
            onClick: () => navigate('/admin/posts'),
            gradient: 'from-slate-900 to-slate-700',
            text: 'text-white',
        },
        {
            label: 'Categories',
            description: 'Organize content',
            icon: Folder,
            onClick: () => navigate('/admin/categories'),
            gradient: 'from-emerald-500 to-emerald-600',
            text: 'text-white',
        },
        {
            label: 'View Site',
            description: 'Open live page',
            icon: ExternalLink,
            onClick: () => window.open('/', '_blank'),
            gradient: 'from-amber-300 to-orange-400',
            text: 'text-slate-900',
        },
        {
            label: 'All Posts',
            description: 'Manage content',
            icon: FileText,
            onClick: () => navigate('/admin/posts'),
            gradient: 'from-sky-500 to-indigo-500',
            text: 'text-white',
        },
    ];

    return (
        <div className="relative p-6 lg:p-10" style={{ fontFamily: 'var(--font-dashboard)' }}>
            <div className="pointer-events-none absolute inset-0 overflow-hidden">
                <div className="absolute -top-20 -right-24 h-72 w-72 rounded-full bg-gradient-to-br from-amber-200/70 to-rose-200/40 blur-3xl animate-[float-soft_10s_ease-in-out_infinite]"></div>
                <div className="absolute -bottom-28 -left-24 h-72 w-72 rounded-full bg-gradient-to-br from-emerald-200/60 to-sky-200/50 blur-3xl animate-[float-soft_12s_ease-in-out_infinite]"></div>
            </div>

            <div className="relative space-y-6">
                <section className="grid grid-cols-1 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-6 animate-[fade-in_0.6s_ease-out]">
                    <div className="relative overflow-hidden rounded-3xl border border-slate-200/70 bg-white/80 shadow-sm backdrop-blur">
                        <div className="absolute -top-10 -right-16 h-40 w-40 rounded-full bg-gradient-to-br from-slate-900/10 to-slate-900/0 blur-2xl"></div>
                        <div className="relative p-6 lg:p-8">
                            <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <div className="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600 ring-1 ring-slate-200/80">
                                        <Sparkles className="h-4 w-4 text-amber-500" />
                                        Pro Dashboard
                                    </div>
                                    <h1 className="mt-3 text-3xl font-semibold text-slate-900 lg:text-4xl">
                                        {greeting}, ready to ship more?
                                    </h1>
                                    <p className="mt-2 text-sm text-slate-600">
                                        Here is your editorial command center. Track momentum, spot gaps, and keep the team focused.
                                    </p>
                                    <div className="mt-4 flex flex-wrap gap-3 text-xs text-slate-500">
                                        <span className="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1 ring-1 ring-slate-200/70">
                                            <Calendar className="h-4 w-4" />
                                            {formattedDate}
                                        </span>
                                        <span className="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1 ring-1 ring-slate-200/70">
                                            <TrendingUp className="h-4 w-4" />
                                            {weekTotal} posts this week
                                        </span>
                                        <span className="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1 ring-1 ring-slate-200/70">
                                            <Activity className="h-4 w-4" />
                                            {weekAvg} avg/day
                                        </span>
                                    </div>
                                </div>

                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="rounded-2xl border border-slate-200/70 bg-white/80 p-4 shadow-sm">
                                        <div className="flex items-center justify-between">
                                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                Today Pulse
                                            </p>
                                            <Bell className="h-4 w-4 text-amber-500" />
                                        </div>
                                        <div className="mt-3 space-y-2">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-slate-600">Posts</span>
                                                <span className="font-semibold text-slate-900">{todayPosts}</span>
                                            </div>
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-slate-600">Recipes</span>
                                                <span className="font-semibold text-slate-900">{todayRecipes}</span>
                                            </div>
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-slate-600">Articles</span>
                                                <span className="font-semibold text-slate-900">{todayArticles}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="rounded-2xl border border-slate-200/70 bg-white/80 p-4 shadow-sm">
                                        <div className="flex items-center justify-between">
                                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                Weekly Momentum
                                            </p>
                                            <ArrowUpRight className={`h-4 w-4 ${trendUp ? 'text-emerald-600' : 'text-rose-600'}`} />
                                        </div>
                                        <div className="mt-4">
                                            <p className="text-2xl font-semibold text-slate-900">{weekTotal}</p>
                                            <p className="text-xs text-slate-500">Total posts</p>
                                        </div>
                                        <div className="mt-3 flex items-center gap-2 text-xs">
                                            <span
                                                className={`inline-flex items-center gap-1 rounded-full px-2 py-1 font-semibold ${
                                                    trendUp
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : 'bg-rose-100 text-rose-700'
                                                }`}
                                            >
                                                {trendUp ? '+' : ''}
                                                {trendDelta} avg/day
                                            </span>
                                            <span className="text-slate-500">vs last half</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                {quickActions.map((action) => {
                                    const Icon = action.icon;
                                    return (
                                        <button
                                            key={action.label}
                                            onClick={action.onClick}
                                            className={`group relative overflow-hidden rounded-2xl border border-white/70 bg-gradient-to-br ${
                                                action.gradient
                                            } p-4 text-left shadow-md transition-all hover:-translate-y-0.5`}
                                        >
                                            <div className="flex items-center justify-between">
                                                <div className={`rounded-xl bg-white/20 p-2 ${action.text}`}>
                                                    <Icon className="h-5 w-5" />
                                                </div>
                                                <ArrowRight className={`h-4 w-4 ${action.text} opacity-80`} />
                                            </div>
                                            <div className={`mt-4 ${action.text}`}>
                                                <p className="text-sm font-semibold">{action.label}</p>
                                                <p className="text-xs opacity-80">{action.description}</p>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-4 animate-[fade-in_0.6s_ease-out]" style={{ animationDelay: '120ms' }}>
                        <div className="rounded-2xl border border-slate-200/70 bg-white/80 p-5 shadow-sm">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Content Mix
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold text-slate-900">{totalPosts}</p>
                                    <p className="text-xs text-slate-500">All live posts</p>
                                </div>
                                <div className="rounded-2xl bg-slate-100 p-3">
                                    <BarChart3 className="h-6 w-6 text-slate-600" />
                                </div>
                            </div>
                            <div className="mt-4 space-y-3">
                                <div>
                                    <div className="flex items-center justify-between text-xs text-slate-600">
                                        <span>Recipes</span>
                                        <span>{recipesPct}%</span>
                                    </div>
                                    <div className="mt-2 h-2 rounded-full bg-slate-100">
                                        <div className="h-2 rounded-full bg-emerald-500" style={{ width: `${recipesPct}%` }}></div>
                                    </div>
                                </div>
                                <div>
                                    <div className="flex items-center justify-between text-xs text-slate-600">
                                        <span>Articles</span>
                                        <span>{articlesPct}%</span>
                                    </div>
                                    <div className="mt-2 h-2 rounded-full bg-slate-100">
                                        <div className="h-2 rounded-full bg-amber-500" style={{ width: `${articlesPct}%` }}></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-200/70 bg-white/80 p-5 shadow-sm">
                            <h3 className="text-sm font-semibold text-slate-700">System Status</h3>
                            <div className="mt-4 space-y-3">
                                {[
                                    { label: 'Database', status: 'Connected' },
                                    { label: 'API', status: 'Online' },
                                    { label: 'Storage', status: 'Healthy' },
                                ].map((item) => (
                                    <div
                                        key={item.label}
                                        className="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-sm"
                                    >
                                        <div className="flex items-center gap-2">
                                            <span className="h-2 w-2 rounded-full bg-emerald-500"></span>
                                            <span className="text-slate-600">{item.label}</span>
                                        </div>
                                        <span className="font-semibold text-emerald-600">{item.status}</span>
                                    </div>
                                ))}
                            </div>
                            <button
                                onClick={() => window.location.reload()}
                                className="mt-4 w-full rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200"
                            >
                                Refresh Status
                            </button>
                        </div>
                    </div>
                </section>

                <section
                    className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4 animate-[fade-in_0.6s_ease-out]"
                    style={{ animationDelay: '200ms' }}
                >
                    {statCards.map((card) => {
                        const Icon = card.icon;
                        return (
                            <div
                                key={card.label}
                                className="relative overflow-hidden rounded-2xl border border-slate-200/70 bg-white/80 p-5 shadow-sm"
                            >
                                <div className={`absolute inset-0 bg-gradient-to-br ${card.accent}`}></div>
                                <div className="relative">
                                    <div className="flex items-center justify-between">
                                        <div className={`rounded-xl p-2 ${card.iconStyle}`}>
                                            <Icon className="h-5 w-5" />
                                        </div>
                                        <span className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            Overview
                                        </span>
                                    </div>
                                    <p className="mt-4 text-2xl font-semibold text-slate-900">{card.value}</p>
                                    <p className="text-xs text-slate-500">{card.label}</p>
                                    <p className="mt-3 text-xs font-semibold text-slate-600">{card.hint}</p>
                                </div>
                            </div>
                        );
                    })}
                </section>

                <section
                    className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] animate-[fade-in_0.6s_ease-out]"
                    style={{ animationDelay: '280ms' }}
                >
                    <div className="rounded-2xl border border-slate-200/70 bg-white/80 p-6 shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 className="text-lg font-semibold text-slate-900">Last Week Performance</h3>
                                <p className="text-xs text-slate-500">Daily publishing volume</p>
                            </div>
                            <div className="flex items-center gap-2 text-xs text-slate-500">
                                <span className="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1">
                                    <span className="h-2 w-2 rounded-full bg-sky-500"></span>
                                    Posts
                                </span>
                                <span className="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1">
                                    <Clock className="h-3 w-3" />
                                    {weekAvg} avg/day
                                </span>
                            </div>
                        </div>

                        <div className="mt-6 flex h-44 items-end gap-2">
                            {weekEntries.length ? (
                                weekEntries.map(([date, count]) => {
                                    const height = weekMax > 0 ? (count / weekMax) * 100 : 0;
                                    return (
                                        <div key={date} className="flex flex-1 flex-col items-center gap-2">
                                            <div
                                                className="w-full rounded-t-xl bg-gradient-to-b from-sky-500 to-indigo-500 transition hover:from-sky-400 hover:to-indigo-400"
                                                style={{ height: `${height}%`, minHeight: height > 0 ? '8px' : '0' }}
                                                title={`${date}: ${count} posts`}
                                            ></div>
                                            <span className="text-[10px] uppercase tracking-wide text-slate-400">
                                                {new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                                            </span>
                                        </div>
                                    );
                                })
                            ) : (
                                <div className="flex h-full w-full items-center justify-center text-slate-400">
                                    <div className="text-center">
                                        <BarChart3 className="mx-auto mb-2 h-12 w-12" />
                                        <p className="text-sm">No data for last week</p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200/70 bg-white/80 p-6 shadow-sm">
                        <h3 className="text-lg font-semibold text-slate-900">Activity Summary</h3>
                        <div className="mt-5 space-y-4">
                            <div className="rounded-2xl bg-slate-50 p-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="rounded-xl bg-emerald-100 p-2">
                                            <FileText className="h-5 w-5 text-emerald-600" />
                                        </div>
                                        <div>
                                            <p className="text-xs text-slate-500">Total Content</p>
                                            <p className="text-xl font-semibold text-slate-900">{totalPosts}</p>
                                        </div>
                                    </div>
                                    <span className="text-xs font-semibold text-emerald-600">{todayPosts} today</span>
                                </div>
                            </div>
                            <div className="rounded-2xl bg-slate-50 p-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="rounded-xl bg-amber-100 p-2">
                                            <Folder className="h-5 w-5 text-amber-600" />
                                        </div>
                                        <div>
                                            <p className="text-xs text-slate-500">Categories</p>
                                            <p className="text-xl font-semibold text-slate-900">{totalCategories}</p>
                                        </div>
                                    </div>
                                    <span className="text-xs font-semibold text-slate-500">Active</span>
                                </div>
                            </div>
                            <div className="rounded-2xl bg-slate-50 p-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="rounded-xl bg-sky-100 p-2">
                                            <Users className="h-5 w-5 text-sky-600" />
                                        </div>
                                        <div>
                                            <p className="text-xs text-slate-500">Users</p>
                                            <p className="text-xl font-semibold text-slate-900">{totalUsers}</p>
                                        </div>
                                    </div>
                                    <span className="text-xs font-semibold text-slate-500">Registered</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] animate-[fade-in_0.6s_ease-out]"
                    style={{ animationDelay: '360ms' }}
                >
                    <div className="rounded-2xl border border-slate-200/70 bg-white/80 p-6 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-semibold text-slate-900">Latest Posts</h3>
                                <p className="text-xs text-slate-500">Most recent content drops</p>
                            </div>
                            <button
                                onClick={() => navigate('/admin/posts')}
                                className="flex items-center gap-1 text-xs font-semibold text-sky-600 hover:text-sky-700"
                            >
                                View All <ArrowRight className="h-4 w-4" />
                            </button>
                        </div>
                        <div className="mt-5 space-y-2">
                            {latestPosts.length ? (
                                latestPosts.slice(0, 6).map((post) => (
                                    <button
                                        key={post.id}
                                        onClick={() => navigate('/admin/posts')}
                                        className="group flex w-full items-center gap-3 rounded-2xl border border-transparent p-3 text-left transition hover:border-slate-200 hover:bg-slate-50"
                                    >
                                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                            <FileText className="h-5 w-5" />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-semibold text-slate-900 group-hover:text-sky-700">
                                                {post.title}
                                            </p>
                                            <div className="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                                <span>{post.category?.name || 'Uncategorized'}</span>
                                                <span className="text-slate-300">•</span>
                                                <span className="inline-flex items-center gap-1">
                                                    <Clock className="h-3 w-3" />
                                                    {new Date(post.created_at).toLocaleDateString()}
                                                </span>
                                            </div>
                                        </div>
                                        <span
                                            className={`rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-wide ${
                                                post.type === 'recipe'
                                                    ? 'bg-sky-100 text-sky-700'
                                                    : 'bg-emerald-100 text-emerald-700'
                                            }`}
                                        >
                                            {post.type || 'post'}
                                        </span>
                                    </button>
                                ))
                            ) : (
                                <div className="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                                    No posts yet. Start by creating your first recipe or article.
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="space-y-6">
                        <div className="rounded-2xl border border-slate-200/70 bg-white/80 p-6 shadow-sm">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-slate-900">Popular Posts</h3>
                                <button
                                    onClick={() => navigate('/admin/posts')}
                                    className="flex items-center gap-1 text-xs font-semibold text-sky-600 hover:text-sky-700"
                                >
                                    View All <ArrowRight className="h-4 w-4" />
                                </button>
                            </div>
                            <div className="mt-5 space-y-3">
                                {popularPosts.length ? (
                                    popularPosts.slice(0, 4).map((post, index) => (
                                        <button
                                            key={post.id}
                                            onClick={() => navigate('/admin/posts')}
                                            className="flex w-full items-center gap-3 rounded-xl bg-slate-50 p-3 text-left transition hover:bg-slate-100"
                                        >
                                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-white text-slate-500">
                                                <TrendingUp className="h-4 w-4" />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-semibold text-slate-900">{post.title}</p>
                                                <p className="text-xs text-slate-500">{post.category?.name || 'Uncategorized'}</p>
                                            </div>
                                            <span className="text-xs font-semibold text-slate-400">#{index + 1}</span>
                                        </button>
                                    ))
                                ) : (
                                    <div className="rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-500">
                                        Popular content will appear once readers engage.
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-200/70 bg-gradient-to-br from-slate-900 to-slate-800 p-6 text-white shadow-lg">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs uppercase tracking-wide text-slate-300">Need attention</p>
                                    <h3 className="mt-2 text-lg font-semibold">Editorial checklist</h3>
                                </div>
                                <AlertCircle className="h-5 w-5 text-amber-300" />
                            </div>
                            <div className="mt-4 space-y-3 text-sm text-slate-200">
                                <div className="flex items-center justify-between">
                                    <span>Schedule next week</span>
                                    <span className="rounded-full bg-white/10 px-2 py-1 text-xs">Pending</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span>Review trending recipes</span>
                                    <span className="rounded-full bg-white/10 px-2 py-1 text-xs">In progress</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span>Archive stale posts</span>
                                    <span className="rounded-full bg-white/10 px-2 py-1 text-xs">Optional</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    );
}
