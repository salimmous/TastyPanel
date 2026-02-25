@extends('layouts.platform')

@section('header', 'Platform Logs')

@section('content')
    <div x-data="{
        logType: 'laravel',
        logs: 'Loading...',
        loading: false,
        fetchLogs() {
            this.loading = true;
            this.logs = 'Fetching...';
            fetch('{{ route('platform.logs.fetch') }}?service=' + this.logType)
                .then(res => res.json())
                .then(data => {
                    this.logs = data.logs || 'No logs found.';
                    this.loading = false;
                })
                .catch(err => {
                    this.logs = 'Error fetching logs.';
                    this.loading = false;
                });
        }
    }" x-init="fetchLogs()" class="h-[calc(100vh-200px)] flex flex-col">

        <div class="mb-4 flex gap-2">
            <button @click="logType = 'laravel'; fetchLogs()"
                :class="logType === 'laravel' ? 'bg-stone-800 text-white' : 'bg-white text-stone-700 border border-stone-200 hover:bg-stone-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Laravel Log
            </button>
            <button @click="logType = 'nginx'; fetchLogs()"
                :class="logType === 'nginx' ? 'bg-stone-800 text-white' : 'bg-white text-stone-700 border border-stone-200 hover:bg-stone-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Nginx Error
            </button>
            <button @click="logType = 'php8.3-fpm'; fetchLogs()"
                :class="logType === 'php8.3-fpm' ? 'bg-stone-800 text-white' : 'bg-white text-stone-700 border border-stone-200 hover:bg-stone-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                PHP-FPM Log
            </button>
            <button @click="logType = 'mysql'; fetchLogs()"
                :class="logType === 'mysql' ? 'bg-stone-800 text-white' : 'bg-white text-stone-700 border border-stone-200 hover:bg-stone-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                MySQL Log
            </button>
            <button @click="logType = 'redis'; fetchLogs()"
                :class="logType === 'redis' ? 'bg-stone-800 text-white' : 'bg-white text-stone-700 border border-stone-200 hover:bg-stone-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Redis Log
            </button>
            <div class="ml-auto">
                <button @click="fetchLogs()" class="p-2 text-stone-500 hover:text-stone-800 rounded-lg hover:bg-stone-100" title="Refresh">
                    <i class="ph ph-arrows-clockwise text-lg" :class="loading ? 'animate-spin' : ''"></i>
                </button>
            </div>
        </div>

        <div class="flex-1 bg-stone-900 rounded-xl overflow-hidden shadow-inner border border-stone-700 relative">
            <pre class="w-full h-full p-4 overflow-auto text-xs font-mono text-stone-300 leading-relaxed" x-text="logs"></pre>

            <div x-show="loading" class="absolute inset-0 bg-stone-900/50 backdrop-blur-sm flex items-center justify-center">
                <i class="ph ph-spinner animate-spin text-3xl text-white"></i>
            </div>
        </div>
    </div>
@endsection
