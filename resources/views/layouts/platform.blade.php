<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Platform') - TastyPanel</title>
    
    <!-- Fonts: Outfit (rounded, modern) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons: Phosphor -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'system-ui', 'sans-serif'],
                        display: ['Outfit', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background-color: #374151; border-radius: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background-color: transparent; }

        :root {
            --tp-surface: #ffffff;
            --tp-border: rgba(41, 37, 36, 0.12);
            --tp-muted: #78716c;
            --tp-ink: #1c1917;
            --tp-primary: #d97706;
        }

        body {
            background: #fafaf9;
            background-image:
                radial-gradient(ellipse 120% 80% at 10% 0%, rgba(253, 230, 138, 0.15), transparent 50%),
                radial-gradient(ellipse 80% 60% at 90% 20%, rgba(254, 243, 199, 0.12), transparent 45%);
        }

        .fade-in-up {
            animation: tpFadeInUp 220ms ease-out both;
        }

        @keyframes tpFadeInUp {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .tp-kbd {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 11px;
            border: 1px solid rgba(41, 37, 36, 0.15);
            border-bottom-width: 2px;
            border-radius: 6px;
            padding: 2px 6px;
            background: rgba(255, 255, 255, 0.9);
            color: rgba(41, 37, 36, 0.85);
        }

        /* Legacy utility classes used across some platform pages (staging/automation/roles). */
        .card {
            background: var(--tp-surface);
            border: 1px solid var(--tp-border);
            border-radius: 14px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid rgba(15, 23, 42, 0.12);
            background: rgba(255, 255, 255, 0.9);
            color: rgba(15, 23, 42, 0.92);
            cursor: pointer;
            transition: background 140ms ease, border-color 140ms ease, transform 140ms ease, box-shadow 140ms ease;
        }
        .btn:hover {
            background: #fff;
            border-color: rgba(15, 23, 42, 0.18);
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
            transform: translateY(-1px);
        }
        .btn:active { transform: translateY(0); box-shadow: none; }

        .btn-sm { padding: 7px 10px; border-radius: 9px; font-size: 12px; font-weight: 700; }

        .btn-primary {
            background: var(--tp-primary);
            border-color: rgba(217, 119, 6, 0.5);
            color: #fff;
        }
        .btn-primary:hover { background: #b45309; }

        .btn-secondary {
            background: rgba(148, 163, 184, 0.18);
            border-color: rgba(148, 163, 184, 0.30);
            color: rgba(15, 23, 42, 0.92);
        }
        .btn-secondary:hover { background: rgba(148, 163, 184, 0.24); }

        .btn-danger {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.25);
            color: rgba(153, 27, 27, 0.95);
        }
        .btn-danger:hover { background: rgba(239, 68, 68, 0.16); }

        .btn-success {
            background: rgba(16, 185, 129, 0.12);
            border-color: rgba(16, 185, 129, 0.25);
            color: rgba(6, 95, 70, 0.95);
        }
        .btn-success:hover { background: rgba(16, 185, 129, 0.16); }

        .input {
            width: 100%;
            border: 1px solid rgba(15, 23, 42, 0.14);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.96);
            color: rgba(15, 23, 42, 0.92);
            outline: none;
            transition: border-color 140ms ease, box-shadow 140ms ease, background 140ms ease;
        }
        .input:focus {
            border-color: rgba(217, 119, 6, 0.5);
            box-shadow: 0 0 0 3px rgba(253, 230, 138, 0.35);
            background: #fff;
        }

        .table { width: 100%; border-collapse: collapse; }
        .table th {
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(100, 116, 139, 0.95);
            background: rgba(248, 250, 252, 1);
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }
        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            font-size: 14px;
        }
    </style>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="h-full font-sans antialiased text-stone-900">
    
    @php
        $paletteItems = [
            ['label' => 'Dashboard', 'href' => route('platform.dashboard'), 'keywords' => 'home dashboard'],
            ['label' => 'Overview', 'href' => route('platform.overview'), 'keywords' => 'overview system metrics'],
            ['label' => 'Control Center', 'href' => route('platform.control'), 'keywords' => 'control ops runbook'],
            ['label' => 'Deploy Center', 'href' => route('platform.deploy'), 'keywords' => 'deploy restart migrate'],
            ['label' => 'Monitoring Center', 'href' => route('platform.monitoring'), 'keywords' => 'monitoring uptime ssl backups'],
            ['label' => 'Monitoring Rules', 'href' => route('platform.monitoring.rules'), 'keywords' => 'monitoring rules alerts channels slack email'],
            ['label' => 'Sites', 'href' => route('platform.tenants'), 'keywords' => 'tenants sites domains'],
            ['label' => 'Domain Center', 'href' => route('platform.domains'), 'keywords' => 'domains dns ssl http3 nginx cloudflare'],
            ['label' => 'Services', 'href' => route('platform.services'), 'keywords' => 'services systemd logs'],
            ['label' => 'Queue', 'href' => route('platform.queue'), 'keywords' => 'queue jobs failed'],
            ['label' => 'Backups', 'href' => route('platform.backups'), 'keywords' => 'backup restore'],
            ['label' => 'System Status', 'href' => route('platform.system'), 'keywords' => 'system status mysql redis'],
            ['label' => 'Audit Logs', 'href' => route('platform.audit_logs'), 'keywords' => 'audit logs activity'],
            ['label' => 'Security Center', 'href' => route('platform.security'), 'keywords' => 'security ip allowlist 2fa sessions'],
            ['label' => 'Themes', 'href' => route('platform.themes'), 'keywords' => 'themes templates'],
            ['label' => 'Plugins', 'href' => route('platform.plugins'), 'keywords' => 'plugins'],
            ['label' => 'Settings', 'href' => route('platform.settings'), 'keywords' => 'settings config'],
        ];
    @endphp

    <div
        class="min-h-full flex"
        data-palette-items="{{ e(json_encode($paletteItems)) }}"
        x-data="{
            sidebarOpen: false,
            paletteOpen: false,
            paletteQuery: '',
            paletteIndex: 0,
            paletteItems: [],
            init() {
                try {
                    const raw = this.$el.getAttribute('data-palette-items');
                    this.paletteItems = raw ? JSON.parse(raw) : [];
                } catch (_) { this.paletteItems = []; }
            },
            filteredPalette() {
                const q = (this.paletteQuery || '').toLowerCase().trim();
                const items = Array.isArray(this.paletteItems) ? this.paletteItems : [];
                if (!q) return items;
                return items.filter(i => {
                    const hay = ((i && i.label) || '') + ' ' + ((i && i.keywords) || '');
                    return hay.toLowerCase().includes(q);
                });
            },
            openPalette() {
                this.paletteOpen = true;
                this.paletteQuery = '';
                this.paletteIndex = 0;
                this.$nextTick(() => this.$refs.paletteInput && this.$refs.paletteInput.focus());
            },
            closePalette() {
                this.paletteOpen = false;
            },
            paletteMove(delta) {
                const items = this.filteredPalette();
                if (!items.length) return;
                this.paletteIndex = (this.paletteIndex + delta + items.length) % items.length;
            },
            paletteGo() {
                const items = this.filteredPalette();
                if (!items.length) return;
                const item = items[this.paletteIndex] || items[0];
                if (item && item.href) window.location.href = item.href;
            },
        }"
        @keydown.window.prevent.ctrl.k="openPalette()"
        @keydown.window.prevent.meta.k="openPalette()"
        @keydown.window.escape="closePalette()"
        @tp-open-palette.window="openPalette()"
        @tp-close-palette.window="closePalette()"
    >

        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" class="fixed inset-0 z-40 flex lg:hidden" role="dialog" aria-modal="true">
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="sidebarOpen = false"></div>

            <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex-1 flex flex-col max-w-xs w-full bg-stone-900 pt-5 pb-4">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button type="button" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" @click="sidebarOpen = false">
                        <span class="sr-only">Close sidebar</span>
                        <i class="ph ph-x text-white text-2xl"></i>
                    </button>
                </div>
                <div class="flex-shrink-0 flex items-center px-4">
                    <div class="flex flex-col">
                        <span class="text-xl font-bold text-white tracking-tight font-display">TastyPanel</span>
                        <span class="text-xs text-amber-400/90 uppercase tracking-widest font-semibold">Platform</span>
                    </div>
                </div>
                <div class="mt-5 flex-1 h-0 overflow-y-auto">
                    <nav class="px-2 space-y-1">
                        @include('layouts.partials.nav-links')
                    </nav>
                </div>
            </div>
            <div class="flex-shrink-0 w-14"></div>
        </div>

        <!-- Desktop Sidebar -->
        <div class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0 border-r border-stone-700/80 bg-stone-900">
            <div class="flex-1 flex flex-col min-h-0">
                <div class="flex items-center h-16 flex-shrink-0 px-6 bg-stone-900 border-b border-stone-700/80">
                     <div class="flex flex-col">
                        <span class="text-xl font-bold text-white tracking-tight font-display">TastyPanel</span>
                        <span class="text-xs text-amber-400/90 uppercase tracking-widest font-semibold">Platform</span>
                    </div>
                </div>
                <div class="flex-1 flex flex-col overflow-y-auto sidebar-scroll">
                    <nav class="flex-1 px-3 py-4 space-y-1">
                        @include('layouts.partials.nav-links')
                    </nav>
                </div>
                
                <!-- Use Profile / Logout -->
                <div class="flex-shrink-0 flex border-t border-stone-700/80 p-4">
                    <div class="flex-shrink-0 w-full group block">
                        <div class="flex items-center">
                            <div class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-stone-700 text-amber-200/90">
                                <span class="font-medium leading-none">{{ substr(Auth::user()->name ?? 'A', 0, 1) }}</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-white">{{ Auth::user()->name ?? 'Admin User' }}</p>
                                <form action="{{ route('platform.logout') }}" method="POST" class="mt-1">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-stone-400 hover:text-amber-200/90 transition-colors">Sign out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:pl-64 flex flex-col flex-1">
            <div class="sticky top-0 z-10 flex-shrink-0 flex h-16 bg-white/95 backdrop-blur border-b border-stone-200 lg:hidden">
                <button type="button" class="px-4 border-r border-stone-200 text-stone-600 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 lg:hidden" @click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <i class="ph ph-list text-2xl"></i>
                </button>
                <div class="flex-1 px-4 flex justify-between">
                    <div class="flex-1 flex items-center">
                        <h1 class="text-lg font-semibold text-stone-900 font-display">@yield('header')</h1>
                    </div>
                </div>
            </div>

            <main class="flex-1 pb-8">
                <!-- Page Header (Desktop) -->
                <div class="hidden lg:flex bg-white/95 backdrop-blur border-b border-stone-200 px-8 py-5 justify-between items-center shadow-sm">
                    <h1 class="text-2xl font-bold text-stone-900 tracking-tight font-display">@yield('header')</h1>
                    <div class="flex items-center space-x-4">
                         <!-- Header Actions Slot -->
                         @hasSection('header_actions')
                            @yield('header_actions')
                         @else
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-stone-200 bg-white text-sm font-semibold text-stone-800 hover:bg-stone-50 shadow-sm transition-colors"
                                @click="openPalette()"
                            >
                                <i class="ph ph-magnifying-glass text-lg text-stone-500"></i>
                                Command
                                <span class="tp-kbd">Ctrl K</span>
                            </button>
                         @endif
                    </div>
                </div>

                <div class="px-4 sm:px-6 lg:px-8 py-6">
                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div x-data="{ show: true }" x-show="show" x-transition class="mb-6 rounded-md bg-green-50 p-4 border border-green-100 shadow-sm relative">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="ph ph-check-circle text-green-400 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <div class="-mx-1.5 -my-1.5">
                                        <button @click="show = false" type="button" class="inline-flex bg-green-50 rounded-md p-1.5 text-green-500 hover:bg-green-100 focus:outline-none">
                                            <span class="sr-only">Dismiss</span>
                                            <i class="ph ph-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div x-data="{ show: true }" x-show="show" transition class="mb-6 rounded-md bg-red-50 p-4 border border-red-100 shadow-sm relative">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="ph ph-x-circle text-red-400 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <div class="-mx-1.5 -my-1.5">
                                        <button @click="show = false" type="button" class="inline-flex bg-red-50 rounded-md p-1.5 text-red-500 hover:bg-red-100 focus:outline-none">
                                            <span class="sr-only">Dismiss</span>
                                            <i class="ph ph-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Content -->
                    <div class="content fade-in-up">
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Command Palette -->
    <div x-cloak x-show="paletteOpen" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-stone-900/40 backdrop-blur-sm" @click="closePalette()"></div>
        <div class="absolute inset-0 flex items-start justify-center p-4 sm:p-8 pt-[15vh]">
            <div
                class="w-full max-w-2xl rounded-2xl border border-stone-200 shadow-2xl overflow-hidden bg-white"
                @keydown.stop.prevent.arrow-down="paletteMove(1)"
                @keydown.stop.prevent.arrow-up="paletteMove(-1)"
                @keydown.stop.prevent.enter="paletteGo()"
            >
                <div class="px-4 sm:px-5 py-4 border-b border-stone-200 bg-stone-50/50">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-command text-xl text-amber-500"></i>
                        <input
                            x-ref="paletteInput"
                            type="text"
                            x-model="paletteQuery"
                            class="w-full border-0 bg-transparent focus:ring-0 text-sm text-stone-900 placeholder:text-stone-400"
                            placeholder="Search: tenants, queue, services, backups..."
                            autocomplete="off"
                        />
                        <button type="button" class="text-sm text-stone-500 hover:text-stone-800" @click="closePalette()">
                            <span class="tp-kbd">Esc</span>
                        </button>
                    </div>
                </div>
                <div class="max-h-[50vh] overflow-auto bg-white">
                    <template x-if="filteredPalette().length === 0">
                        <div class="p-6 text-sm text-stone-600">No results.</div>
                    </template>
                    <template x-for="(item, idx) in filteredPalette()" :key="(item.href || '') + '-' + (item.label || idx)">
                        <a
                            :href="item.href"
                            class="flex items-center justify-between px-4 sm:px-5 py-3 border-b border-stone-100 hover:bg-amber-50/50 transition-colors"
                            :class="idx === paletteIndex ? 'bg-amber-50/70' : ''"
                            @mouseenter="paletteIndex = idx"
                        >
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-xl border border-stone-200 bg-stone-50 flex items-center justify-center text-amber-600">
                                    <i class="ph ph-arrow-right text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-stone-900" x-text="item.label"></div>
                                    <div class="text-xs text-stone-500" x-text="item.keywords"></div>
                                </div>
                            </div>
                            <div class="text-xs text-stone-400">Go</div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fallback keyboard handler in case Alpine key modifiers differ across browsers.
        document.addEventListener('keydown', function (e) {
            const key = (e.key || '').toLowerCase();
            if ((e.ctrlKey || e.metaKey) && key === 'k') {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('tp-open-palette'));
            }
            if (key === 'escape') {
                window.dispatchEvent(new CustomEvent('tp-close-palette'));
            }
        });
    </script>
    <!-- Custom JS -->
    <script src="{{ asset('js/script.js') }}"></script>
</body>
</html>
