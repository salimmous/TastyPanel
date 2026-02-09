<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Platform') - TastyPanel</title>
    
    <!-- Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons: Phosphor -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
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
    </style>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="h-full font-sans antialiased text-gray-900">
    
    <div class="min-h-full flex" x-data="{ sidebarOpen: false }">

        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" class="fixed inset-0 z-40 flex lg:hidden" role="dialog" aria-modal="true">
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="sidebarOpen = false"></div>

            <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex-1 flex flex-col max-w-xs w-full bg-slate-900 pt-5 pb-4">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button type="button" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" @click="sidebarOpen = false">
                        <span class="sr-only">Close sidebar</span>
                        <i class="ph ph-x text-white text-2xl"></i>
                    </button>
                </div>
                <div class="flex-shrink-0 flex items-center px-4">
                    <div class="flex flex-col">
                        <span class="text-xl font-bold text-white tracking-tight">TastyPanel</span>
                        <span class="text-xs text-slate-400 uppercase tracking-widest font-semibold">Platform</span>
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
        <div class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0 border-r border-slate-800 bg-slate-900">
            <div class="flex-1 flex flex-col min-h-0">
                <div class="flex items-center h-16 flex-shrink-0 px-6 bg-slate-900 border-b border-slate-800">
                     <div class="flex flex-col">
                        <span class="text-xl font-bold text-white tracking-tight">TastyPanel</span>
                        <span class="text-xs text-slate-400 uppercase tracking-widest font-semibold">Platform</span>
                    </div>
                </div>
                <div class="flex-1 flex flex-col overflow-y-auto sidebar-scroll">
                    <nav class="flex-1 px-3 py-4 space-y-1">
                        @include('layouts.partials.nav-links')
                    </nav>
                </div>
                
                <!-- Use Profile / Logout -->
                <div class="flex-shrink-0 flex border-t border-slate-800 p-4">
                    <div class="flex-shrink-0 w-full group block">
                        <div class="flex items-center">
                            <div class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-slate-700 text-slate-300">
                                <span class="font-medium leading-none">{{ substr(Auth::user()->name ?? 'A', 0, 1) }}</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-white">{{ Auth::user()->name ?? 'Admin User' }}</p>
                                <form action="{{ route('platform.logout') }}" method="POST" class="mt-1">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-slate-400 hover:text-white transition-colors">Sign out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:pl-64 flex flex-col flex-1">
            <div class="sticky top-0 z-10 flex-shrink-0 flex h-16 bg-white border-b border-gray-200 lg:hidden">
                <button type="button" class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 lg:hidden" @click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <i class="ph ph-list text-2xl"></i>
                </button>
                <div class="flex-1 px-4 flex justify-between">
                    <div class="flex-1 flex items-center">
                        <h1 class="text-lg font-semibold text-gray-900">@yield('header')</h1>
                    </div>
                </div>
            </div>

            <main class="flex-1 pb-8">
                <!-- Page Header (Desktop) -->
                <div class="hidden lg:flex bg-white border-b border-gray-200 px-8 py-5 justify-between items-center shadow-sm">
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">@yield('header')</h1>
                    <div class="flex items-center space-x-4">
                         <!-- Header Actions Slot -->
                         @yield('header_actions')
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
</body>
</html>