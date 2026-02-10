@extends('layouts.platform')

@php
    if (!function_exists('format_bytes')) {
        function format_bytes($bytes, $precision = 2) {
            if ($bytes <= 0) return '0 B';
            $base = log($bytes) / log(1024);
            $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
            return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
        }
    }
@endphp

@section('title', 'Manage ' . $tenant->name)
@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">{{ $tenant->name }}</h1>
        <div class="flex space-x-3 text-right">
             @if($tenant->domains->where('is_primary', true)->first())
                <a href="http://{{ $tenant->domains->where('is_primary', true)->first()->hostname }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <i class="ph ph-arrow-square-out mr-2"></i> View Site
                </a>
            @endif
             <form action="{{ route('platform.tenants.toggle-status', $tenant->id) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    {{ $tenant->status === 'active' ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
            <form action="{{ route('platform.tenants.destroy', $tenant->id) }}" method="POST" onsubmit="return confirm('Are you sure? This will delete the tenant and all data.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Delete
                </button>
            </form>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="{ activeTab: localStorage.getItem('tenant_active_tab') || 'overview' }" 
         x-init="$watch('activeTab', value => localStorage.setItem('tenant_active_tab', value))"
         class="space-y-6">
        
        <!-- Tabs -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 overflow-x-auto sidebar-scroll" aria-label="Tabs">
                <button @click="activeTab = 'overview'" 
                        :class="activeTab === 'overview' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-chart-line mr-2"></i> Overview
                </button>
                <button @click="activeTab = 'access'" 
                        :class="activeTab === 'access' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-key mr-2"></i> Access
                </button>
                <button @click="activeTab = 'database'" 
                        :class="activeTab === 'database' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-database mr-2"></i> Database
                </button>
                <button @click="activeTab = 'mail'" 
                        :class="activeTab === 'mail' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-envelope mr-2"></i> Mail
                </button>
                <button @click="activeTab = 'security'" 
                        :class="activeTab === 'security' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-shield-check mr-2"></i> Security
                </button>
                <button @click="activeTab = 'ssl'" 
                        :class="activeTab === 'ssl' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-lock mr-2"></i> SSL
                </button>
                <button @click="activeTab = 'php'" 
                        :class="activeTab === 'php' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-code mr-2"></i> PHP
                </button>
                <button @click="activeTab = 'apps'" 
                        :class="activeTab === 'apps' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-app-window mr-2"></i> Apps
                </button>
                <button @click="activeTab = 'vhost'" 
                        :class="activeTab === 'vhost' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-terminal mr-2"></i> Vhost
                </button>
                <button @click="activeTab = 'secrets'" 
                        :class="activeTab === 'secrets' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-fingerprint mr-2"></i> Secrets
                </button>
                <button @click="activeTab = 'logs'" 
                        :class="activeTab === 'logs' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-list-numbers mr-2"></i> Logs
                </button>
                <button @click="activeTab = 'backups'" 
                        :class="activeTab === 'backups' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-archive mr-2"></i> Backups
                </button>
                <button @click="activeTab = 'cron'" 
                        :class="activeTab === 'cron' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
                    <i class="ph ph-calendar-check mr-2"></i> Cron Jobs
                </button>
	                 <a href="{{ route('platform.tenants.staging', $tenant->id) }}"
	                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
	                    Staging <i class="ph ph-arrow-square-out ml-1"></i>
	                </a>
                    <a href="{{ route('platform.tenants.preview', $tenant->id) }}"
	                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
	                    Preview <i class="ph ph-arrow-square-out ml-1"></i>
	                </a>
	                <a href="{{ route('platform.tenants.automation', $tenant->id) }}"
	                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center">
	                    Automation <i class="ph ph-arrow-square-out ml-1"></i>
	                </a>
            </nav>
        </div>

        <!-- Overview Tab -->
        <div x-show="activeTab === 'overview'" style="display: none;" class="space-y-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white overflow-hidden shadow-sm border border-gray-100 rounded-xl relative p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 bg-green-50 rounded-lg">
                            <i class="ph ph-activity text-green-600"></i>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $tenant->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    </div>
                    <dt class="text-sm font-medium text-gray-500 truncate">Platform Status</dt>
                    <dd class="mt-1 text-2xl font-bold text-gray-900">Active</dd>
                </div>

                <div class="bg-white overflow-hidden shadow-sm border border-gray-100 rounded-xl p-5">
                    <div class="flex items-center mb-4">
                        <div class="p-2 bg-blue-50 rounded-lg mr-3">
                            <i class="ph ph-hard-drive text-blue-600"></i>
                        </div>
                        <dt class="text-sm font-medium text-gray-500 truncate">Disk Usage</dt>
                    </div>
                    <dd class="text-2xl font-bold text-gray-900">{{ format_bytes($quota['usage']['disk'] ?? 0) }}</dd>
                    <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: 45%"></div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm border border-gray-100 rounded-xl p-5">
                    <div class="flex items-center mb-4">
                        <div class="p-2 bg-purple-50 rounded-lg mr-3">
                            <i class="ph ph-globe text-purple-600"></i>
                        </div>
                        <dt class="text-sm font-medium text-gray-500 truncate">Domains</dt>
                    </div>
                    <dd class="text-2xl font-bold text-gray-900">{{ $tenant->domains->count() }}</dd>
                </div>

                <div class="bg-white overflow-hidden shadow-sm border border-gray-100 rounded-xl p-5">
                    <div class="flex items-center mb-4">
                        <div class="p-2 bg-orange-50 rounded-lg mr-3">
                            <i class="ph ph-clock text-orange-600"></i>
                        </div>
                        <dt class="text-sm font-medium text-gray-500 truncate">Age</dt>
                    </div>
                    <dd class="text-2xl font-bold text-gray-900">{{ $tenant->created_at->diffForHumans(null, true) }}</dd>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Quick Actions -->
                <div class="lg:col-span-1 bg-white shadow-sm border border-gray-100 rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="text-sm font-semibold text-gray-800">Quick Actions</h3>
                    </div>
                    <div class="p-5 space-y-3">
                        <button @click="activeTab = 'apps'; $dispatch('open-install-modal')" class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-primary-50 hover:text-primary-700 transition-colors rounded-lg text-sm font-medium text-gray-700">
                            <span class="flex items-center"><i class="ph ph-download-simple mr-3"></i> Install App</span>
                            <i class="ph ph-caret-right"></i>
                        </button>
                        <button @click="activeTab = 'ssl'" class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-primary-50 hover:text-primary-700 transition-colors rounded-lg text-sm font-medium text-gray-700">
                            <span class="flex items-center"><i class="ph ph-lock mr-3"></i> Configure SSL</span>
                            <i class="ph ph-caret-right"></i>
                        </button>
                        <button @click="activeTab = 'vhost'" class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-primary-50 hover:text-primary-700 transition-colors rounded-lg text-sm font-medium text-gray-700">
                            <span class="flex items-center"><i class="ph ph-terminal mr-3"></i> Edit Vhost</span>
                            <i class="ph ph-caret-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Server Info -->
                <div class="lg:col-span-2 bg-white shadow-sm border border-gray-100 rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Server Environment</h3>
                    </div>
                    <div class="p-5 overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td class="py-3 text-gray-500">Instance Root</td>
                                    <td class="py-3 font-mono text-xs text-gray-800">{{ $tenant->instance_root }}</td>
                                </tr>
                                <tr>
                                    <td class="py-3 text-gray-500">System User</td>
                                    <td class="py-3 font-mono text-xs text-gray-800">{{ $tenant->instance_system_user }}</td>
                                </tr>
                                <tr>
                                    <td class="py-3 text-gray-500">PHP Version</td>
                                    <td class="py-3 text-gray-800">8.3 (FPM)</td>
                                </tr>
                                <tr>
                                    <td class="py-3 text-gray-500">Database</td>
                                    <td class="py-3 text-gray-800">{{ $tenant->instance_db_name }} (MySQL)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Domains Table -->
             <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Domains</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Domains attached to this tenant.</p>
                </div>
                <ul class="divide-y divide-gray-200">
                    @foreach($tenant->domains as $domain)
                        <li class="px-4 py-4 sm:px-6 flex items-center justify-between hover:bg-gray-50">
                            <div class="flex items-center">
                                <i class="ph ph-globe text-gray-400 text-xl mr-3"></i>
                                <div>
                                    <p class="text-sm font-medium text-primary-600 truncate">
                                        <a href="//{{ $domain->hostname }}" target="_blank" class="hover:underline">{{ $domain->hostname }}</a>
                                    </p>
                                    <p class="flex items-center text-sm text-gray-500">
                                        @if($domain->is_primary)
                                            <span class="mr-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Primary</span>
                                        @else
                                            <span class="mr-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Alias</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $domain->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $domain->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="ph ph-lock-key mr-1"></i> SSL
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Access Tab -->
        <div x-show="activeTab === 'access'" style="display: none;" class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">SSH & SFTP Access</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Connection details for remote access.</p>
                </div>
                <div class="px-4 py-5 sm:p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Host</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="text" readonly value="ssh.tastypanel.com" class="focus:ring-primary-500 focus:border-primary-500 flex-1 block w-full rounded-md sm:text-sm border-gray-300 bg-gray-50">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="text" readonly value="{{ $access['data']['user'] ?? $tenant->instance_ssh_user ?? 'N/A' }}" class="focus:ring-primary-500 focus:border-primary-500 flex-1 block w-full rounded-md sm:text-sm border-gray-300 bg-gray-50">
                            </div>
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700">Port</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="text" readonly value="{{ $access['data']['port'] ?? 22 }}" class="focus:ring-primary-500 focus:border-primary-500 flex-1 block w-full rounded-md sm:text-sm border-gray-300 bg-gray-50">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Tab -->
        <div x-show="activeTab === 'database'" style="display: none;" class="space-y-6">
            @if($errors->has('phpmyadmin'))
                <div class="rounded-md bg-red-50 p-4">
                    <p class="text-sm text-red-800">{{ $errors->first('phpmyadmin') }}</p>
                </div>
            @endif
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Database Connection</h3>
                    <p class="mt-1 text-sm text-gray-500">MySQL credentials for this site. Use them in phpMyAdmin or in the app.</p>
                </div>
                <div class="px-4 py-5 sm:p-6 space-y-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Database</dt>
                            <dd class="mt-1 font-mono text-sm text-gray-900">{{ $tenant->instance_db_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">User</dt>
                            <dd class="mt-1 font-mono text-sm text-gray-900">{{ $tenant->instance_db_user ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Host</dt>
                            <dd class="mt-1 font-mono text-sm text-gray-900">localhost</dd>
                        </div>
                    </dl>

                    <div class="pt-4 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-900 mb-1">phpMyAdmin</h4>
                        @if($pmaUrl ?? null)
                            <p class="text-sm text-gray-500 mb-3">Open the link below and log in with <strong>{{ $tenant->instance_db_user ?? 'user' }}</strong> (or <code class="text-xs bg-gray-100 px-1 rounded">pma_{{ $tenant->slug ?? $tenant->instance_key }}</code> after Setup).</p>
                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                <span class="text-sm text-gray-600 font-mono bg-gray-50 px-2 py-1 rounded border border-gray-200">{{ $pmaUrl }}</span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('platform.tenants.phpmyadmin', $tenant->id) }}" class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                                    <i class="ph ph-browser mr-2"></i> Open in panel
                                </a>
                                <a href="{{ $pmaUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="ph ph-arrow-square-out mr-2"></i> New tab
                                </a>
                                @if($tenant->instance_db_name)
                                    <form action="{{ route('platform.tenants.phpmyadmin.provision', $tenant->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50">
                                            <i class="ph ph-wrench mr-2"></i> Setup pma_ user
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-gray-500 mb-2">Set <code>PMA_URL</code> or <code>PMA_PATH</code> in .env, then reload.</p>
                            @if($tenant->instance_db_name)
                                <form action="{{ route('platform.tenants.phpmyadmin.provision', $tenant->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50">
                                        <i class="ph ph-wrench mr-2"></i> Setup phpMyAdmin user
                                    </button>
                                </form>
                            @endif
                        @endif
                        @if(!$tenant->instance_db_name)
                            <p class="mt-2 text-xs text-gray-400">Provision the site first to get a database.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Mail Tab -->
        <div x-show="activeTab === 'mail'" style="display: none;" class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Mail Configuration</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">SMTP settings for outgoing mail.</p>
                </div>
                <div class="px-4 py-5 sm:p-6 space-y-4">
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Driver</label>
                            <input type="text" readonly value="{{ $mail['data']['settings']['mail_driver'] ?? 'smtp' }}" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700">Host</label>
                            <input type="text" readonly value="{{ $mail['data']['settings']['mail_host'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700">Port</label>
                            <input type="text" readonly value="{{ $mail['data']['settings']['mail_port'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700">From Address</label>
                            <input type="text" readonly value="{{ $mail['data']['settings']['mail_from_address'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                     </div>
                </div>
            </div>
        </div>

        <!-- Security Tab -->
        <div x-show="activeTab === 'security'" style="display: none;" class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Security Profile</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Web Application Firewall (WAF) and protection settings.</p>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">WAF Status</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $security->waf_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $security->waf_enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Block SQL Injection</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $security->waf_block_sqli ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Block XSS</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $security->waf_block_xss ? 'Yes' : 'No' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- SSL Tab -->
        <div x-show="activeTab === 'ssl'" style="display: none;" class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">SSL Certificates</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage Let's Encrypt certificates for your domains.</p>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <ul class="divide-y divide-gray-200">
                        @foreach($tenant->domains as $domain)
                            <li class="py-4 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="p-2 bg-gray-100 rounded-lg mr-4">
                                        <i class="ph ph-lock text-xl {{ $domain->sslCertificate && $domain->sslCertificate->status === 'issued' ? 'text-green-600' : 'text-gray-400' }}"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $domain->hostname }}</p>
                                        <p class="text-xs text-gray-500">
                                            @if($domain->sslCertificate)
                                                Status: {{ ucfirst($domain->sslCertificate->status) }} 
                                                @if($domain->sslCertificate->expires_at) 
                                                    | Expires: {{ $domain->sslCertificate->expires_at->format('M j, Y') }}
                                                @endif
                                            @else
                                                No certificate provisioned.
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <form action="{{ route('platform.tenants.ssl.provision', $domain->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-primary-700 bg-primary-100 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                            Provision Certificate
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- PHP Tab -->
        <div x-show="activeTab === 'php'" style="display: none;" class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">PHP Settings</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Configure PHP-FPM resources for this tenant.</p>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <form action="{{ route('platform.tenants.php.update', $tenant->id) }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Memory Limit (MB)</label>
                                <input type="number" name="memory_limit" value="{{ $phpSettings['memory_limit'] ?? 256 }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">Maximum amount of memory a script may consume.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Max Children</label>
                                <input type="number" name="max_children" value="{{ $phpSettings['max_children'] ?? 10 }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">Maximum number of child processes (FPM workers).</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Max Requests</label>
                                <input type="number" name="max_requests" value="{{ $phpSettings['max_requests'] ?? 500 }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">Number of requests each child process should execute before respawning.</p>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Apps Tab -->
        <div x-show="activeTab === 'apps'" style="display: none;" class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Installed Applications</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage frameworks and applications installed on this tenant.</p>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    @if($tenant->instance_last_error)
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="ph ph-warning-circle text-red-500 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Installation Error</h3>
                                    <div class="mt-2 text-sm text-red-700 font-mono">
                                        {{ $tenant->instance_last_error }}
                                    </div>
                                    <p class="mt-2 text-xs text-red-600">Check the Logs tab for more details on what went wrong.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($tenant->instance_installed_app)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="bg-primary-100 p-3 rounded-full">
                                    <i class="ph ph-app-window text-primary-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900">{{ ucfirst($tenant->instance_installed_app) }}</h4>
                                    <p class="text-sm text-gray-500">Installed on {{ $tenant->instance_installed_at?->format('M j, Y') ?? 'Unknown' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <form action="{{ route('platform.tenants.uninstall-app', $tenant->id) }}" method="POST" onsubmit="return confirm('WARNING: This will PERMANENTLY DELETE all files in the application directory. Are you sure you want to uninstall and wipe this installation?')">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-red-300 text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        <i class="ph ph-trash-simple mr-1"></i> Uninstall & Wipe
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                             <i class="ph ph-app-store-logo text-4xl text-gray-300 mb-4"></i>
                             <h3 class="mt-2 text-sm font-medium text-gray-900">No applications installed</h3>
                             <p class="mt-1 text-sm text-gray-500">Get started by installing a starter kit or framework.</p>
                             <div class="mt-6">
                                <button @click="$dispatch('open-install-modal')" type="button" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <i class="ph ph-plus mr-2"></i> Install Application
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Install App Modal -->
        <div x-data="{ open: false, app: 'wordpress' }" 
             @open-install-modal.window="open = true" 
             x-show="open" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="open = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('platform.tenants.install-app', $tenant->id) }}" method="POST">
                        @csrf
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-primary-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="ph ph-download-simple text-primary-600 text-lg"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Install Application</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 mb-4">Select an application to install on this tenant. Any existing files in the root directory may be overwritten.</p>
                                        
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Application</label>
                                                <select name="app_type" x-model="app" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                                    <option value="wordpress">WordPress</option>
                                                    <option value="laravel">Laravel Starter</option>
                                                    <option value="git">Custom Git Repository</option>
                                                </select>
                                            </div>

                                            <div x-show="app === 'git'">
                                                <label class="block text-sm font-medium text-gray-700">Repository URL</label>
                                                <input type="text" name="repo_url" placeholder="https://github.com/user/repo.git" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                            </div>

                                            <div class="border-t border-gray-200 pt-4">
                                                <h4 class="text-sm font-medium text-gray-900 mb-2">Admin Account</h4>
                                                <div class="grid grid-cols-1 gap-y-3 gap-x-4 sm:grid-cols-2">
                                                    <div class="sm:col-span-2">
                                                        <label class="block text-sm font-medium text-gray-700">Admin Email</label>
                                                        <input type="email" name="admin_email" required value="{{ auth()->user()->email }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Admin Username</label>
                                                        <input type="text" name="admin_user" required value="admin" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Admin Password</label>
                                                        <input type="password" name="admin_password" required placeholder="Generate a secure password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="border-t border-gray-200 pt-4">
                                                <h4 class="text-sm font-medium text-gray-900 mb-2">Database Connection (Auto-generated)</h4>
                                                <div class="grid grid-cols-1 gap-y-3 gap-x-4 sm:grid-cols-2">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">DB Name</label>
                                                        <input type="text" readonly value="{{ $tenant->instance_db_name }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">DB User</label>
                                                        <input type="text" readonly value="{{ $tenant->instance_db_user }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Install
                            </button>
                            <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

		        <!-- Secrets Tab -->
		        <div
		            x-show="activeTab === 'secrets'"
		            style="display: none;"
		            class="space-y-6"
		            x-data="{
		                secretsExport: @json(($tenant->secrets ?? collect())->map(fn($s) => [
		                    'id' => $s->id,
		                    'secret_key' => $s->secret_key,
		                    'updated_at' => optional($s->updated_at)->toDateTimeString(),
		                ])->values()),
		                exportKeys() {
		                    const payload = {
		                        tenant: { id: {{ (int) $tenant->id }}, name: @json((string) ($tenant->name ?? '')) },
		                        keys: this.secretsExport || [],
		                    };
		                    const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
		                    const url = URL.createObjectURL(blob);
		                    const a = document.createElement('a');
		                    a.href = url;
		                    a.download = `tenant-${payload.tenant.id}-secrets-keys.json`;
		                    document.body.appendChild(a);
		                    a.click();
		                    a.remove();
		                    setTimeout(() => URL.revokeObjectURL(url), 1000);
		                },
		            }"
		        >
		            <div class="bg-white shadow sm:rounded-lg">
		                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex justify-between items-center">
		                    <div>
		                        <h3 class="text-lg leading-6 font-medium text-gray-900">Environment Secrets</h3>
		                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage sensitive environment variables for this tenant.</p>
		                    </div>
	                        <div class="flex items-center gap-3">
	                            <button
	                                type="button"
	                                @click="$dispatch('open-env-sync-modal')"
	                                class="inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-semibold rounded-md shadow-sm text-gray-900 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
	                            >
	                                <i class="ph ph-arrows-clockwise mr-2 text-gray-600"></i> Sync all to .env
	                            </button>
	                            <form method="POST" action="{{ route('platform.control.run') }}">
	                                @csrf
	                                <input type="hidden" name="action_id" value="tenant_env_preview_keys">
	                                <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
	                                <button
	                                    type="submit"
	                                    class="inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-semibold rounded-md shadow-sm text-gray-900 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
	                                >
	                                    <i class="ph ph-eye mr-2 text-gray-600"></i> Preview .env keys
	                                </button>
	                            </form>
	                            <form method="POST" action="{{ route('platform.control.run') }}">
	                                @csrf
	                                <input type="hidden" name="action_id" value="tenant_env_diff_secrets">
	                                <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
	                                <button
	                                    type="submit"
	                                    class="inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-semibold rounded-md shadow-sm text-gray-900 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
	                                >
	                                    <i class="ph ph-git-diff mr-2 text-gray-600"></i> Dry-run diff
	                                </button>
	                            </form>
	                            <button
	                                type="button"
	                                @click="exportKeys()"
	                                class="inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-semibold rounded-md shadow-sm text-gray-900 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
	                            >
	                                <i class="ph ph-download-simple mr-2 text-gray-600"></i> Export keys
	                            </button>
	                            <button @click="$dispatch('open-secret-modal')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
	                                <i class="ph ph-plus mr-2"></i> Add Secret
	                            </button>
	                        </div>
		                </div>
	                <div class="px-4 py-5 sm:p-6">
	                        @php
	                            $secretsRunbookIds = ['tenant_secrets_sync_all_to_env', 'tenant_env_preview_keys', 'tenant_env_diff_secrets'];
	                        @endphp
	                        @if(in_array(session('runbook_action_id'), $secretsRunbookIds, true))
	                            <div class="mb-4 rounded-lg border {{ session('runbook_success') ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} p-4">
	                                <div class="flex items-start justify-between gap-4">
	                                    <div>
	                                        <div class="text-sm font-semibold {{ session('runbook_success') ? 'text-green-900' : 'text-red-900' }}">
	                                            {{ session('runbook_action') ?? 'Secrets action' }}
	                                        </div>
	                                        <div class="mt-1 text-xs text-gray-600">Output tail (redacted).</div>
	                                    </div>
	                                    <span class="shrink-0 px-2 py-1 rounded-full text-xs font-semibold {{ session('runbook_success') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
	                                        {{ session('runbook_success') ? 'success' : 'failed' }}
	                                    </span>
	                                </div>
	                                @if(session('runbook_output'))
	                                    <pre class="mt-3 text-xs whitespace-pre-wrap break-words rounded-md bg-white/70 border border-black/5 p-3 max-h-72 overflow-auto">{{ session('runbook_output') }}</pre>
	                                @endif
	                            </div>
	                        @endif
	                    <table class="min-w-full divide-y divide-gray-200">
	                        <thead>
	                            <tr>
	                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                                <th class="px-6 py-3 bg-gray-50"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($tenant->secrets as $secret)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $secret->secret_key }}</td>
	                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">••••••••••••</td>
	                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $secret->updated_at->diffForHumans() }}</td>
	                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
	                                        <div class="inline-flex items-center gap-3">
	                                            <button
	                                                type="button"
	                                                class="text-gray-700 hover:text-gray-900 underline"
	                                                @click="$dispatch('open-rotate-modal', { key: @json((string) $secret->secret_key) })"
	                                            >
	                                                Rotate
	                                            </button>
	                                            <form action="{{ route('platform.tenants.secrets.destroy', [$tenant->id, $secret->id]) }}" method="POST" class="inline">
	                                                @csrf
	                                                @method('DELETE')
	                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</button>
	                                            </form>
	                                        </div>
	                                    </td>
	                                </tr>
	                            @empty
	                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                        No secrets defined for this tenant.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
	            </div>
	        </div>
        </div>

        <!-- Vhost Tab -->
        <div x-show="activeTab === 'vhost'" style="display: none;" class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Nginx Configuration</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Edit the virtual host configuration for the primary domain.</p>
                    </div>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <form action="{{ route('platform.tenants.vhost.update', $tenant->id) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <textarea name="vhost_content" rows="20" class="w-full font-mono text-sm border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 bg-gray-50">{{ $vhostContent }}</textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                Save & Reload Nginx
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Logs Tab -->
        <div x-show="activeTab === 'logs'" style="display: none;" class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">PHP-FPM Logs</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Recent entries from sytem user log.</p>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="bg-gray-900 rounded-lg p-4 font-mono text-xs text-gray-300 overflow-y-auto max-h-64">
                        @if(isset($logs) && !empty($logs))
                            @foreach($logs as $log)
                                <div class="mb-1">{{ $log }}</div>
                            @endforeach
                        @else
                            <div class="text-gray-500 italic">No PHP log entries found.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Nginx Error Logs</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Recent errors from the primary domain.</p>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="bg-gray-900 rounded-lg p-4 font-mono text-xs text-gray-300 overflow-y-auto max-h-64">
                        @if(isset($nginxLogs) && !empty($nginxLogs))
                            @foreach($nginxLogs as $log)
                                <div class="mb-1">{{ $log }}</div>
                            @endforeach
                        @else
                            <div class="text-gray-500 italic">No Nginx error entries found.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Backups Tab -->
        <div x-show="activeTab === 'backups'" style="display: none;" class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Tenant Backups</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">History of database and file backups.</p>
                    </div>
                    <form action="{{ route('platform.tenants.backups.create', $tenant->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <i class="ph ph-archive mr-2"></i> Create Backup
                        </button>
                    </form>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 bg-gray-50"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($tenant->backupRuns as $run)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $run->created_at->format('M j, Y H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ format_bytes($run->size_bytes) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $run->status === 'success' ? 'bg-green-100 text-green-800' : ($run->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ ucfirst($run->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if($run->status === 'success')
                                            <a href="{{ route('platform.tenants.backups.download', [$tenant->id, $run->id]) }}" class="text-primary-600 hover:text-primary-900 mr-4">Download</a>
                                        @endif
                                        <form action="{{ route('platform.tenants.backups.destroy', [$tenant->id, $run->id]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                        No backups found for this tenant.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cron Tab -->
        <div x-show="activeTab === 'cron'" style="display: none;" class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Scheduled Tasks (Cron Jobs)</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage automated tasks for this tenant's system user.</p>
                    </div>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <form action="{{ route('platform.tenants.cron.store', $tenant->id) }}" method="POST" class="mb-6 flex gap-3">
                        @csrf
                        <div class="flex-grow">
                            <input type="text" name="command" placeholder="* * * * * php /var/www/site/artisan schedule:run" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700">
                            Add Job
                        </button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Command</th>
                                    <th class="px-6 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($cronJobs as $index => $job)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-gray-600">{{ $job }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <form action="{{ route('platform.tenants.cron.destroy', [$tenant->id, $index]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Remove this cron job?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-6 py-8 text-center text-gray-400 italic">No cron jobs configured.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

	        <!-- Add Secret Modal -->
	        <div x-data="{ open: false }" 
	             @open-secret-modal.window="open = true" 
	             x-show="open" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="open" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="open = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="open" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('platform.tenants.secrets.store', $tenant->id) }}" method="POST">
                        @csrf
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add Environment Secret</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Key</label>
                                    <input type="text" name="secret_key" required placeholder="API_KEY" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Value</label>
                                    <textarea name="secret_value" required rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="your-secret-value-here"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                            <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
	        </div>

	            <!-- Sync Secrets to .env Modal -->
	            <div
	                x-data="{ open: false, confirmText: '', phrase: 'tenant_secrets_sync_all_to_env' }"
	                @open-env-sync-modal.window="open = true; confirmText = ''"
	                x-show="open"
                style="display: none;"
                class="fixed inset-0 z-50 overflow-y-auto"
                aria-labelledby="modal-title" role="dialog" aria-modal="true"
            >
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="open" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="open = false"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div x-show="open" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form method="POST" action="{{ route('platform.control.run') }}">
                            @csrf
                            <input type="hidden" name="action_id" value="tenant_secrets_sync_all_to_env">
                            <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">

                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">Sync all secrets to .env</h3>
                                <p class="text-sm text-gray-600">
                                    This will upsert environment variables in the tenant <span class="font-mono">.env</span> for every stored secret.
                                    Values are never displayed, but keys can overwrite existing env keys.
                                </p>

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Type <span class="font-mono" x-text="phrase"></span> to confirm
                                    </label>
                                    <input
                                        type="text"
                                        name="confirm"
                                        x-model="confirmText"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm font-mono"
                                        :placeholder="phrase"
                                        autocomplete="off"
                                    >
                                </div>
                            </div>

                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    type="submit"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-900 text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                                    :disabled="confirmText !== phrase"
                                >
                                    Run Sync
                                </button>
                                <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
	            </div>

	            <!-- Rotate Secret Modal -->
	            <div
	                x-data="{
	                    open: false,
	                    secretKey: '',
	                    secretValue: '',
	                    confirmText: '',
	                    phrase: 'rotate_secret',
	                    gen(len = 48) {
	                        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	                        const out = [];
	                        const bytes = new Uint8Array(len);
	                        (window.crypto || window.msCrypto).getRandomValues(bytes);
	                        for (let i = 0; i < bytes.length; i++) out.push(chars[bytes[i] % chars.length]);
	                        return out.join('');
	                    },
	                    regenerate() { this.secretValue = this.gen(48); },
	                    copy() {
	                        if (!navigator.clipboard) return;
	                        navigator.clipboard.writeText(this.secretValue || '');
	                    },
	                }"
	                @open-rotate-modal.window="open = true; secretKey = ($event.detail && $event.detail.key) ? String($event.detail.key) : ''; secretValue = gen(48); confirmText = ''"
	                x-show="open"
	                style="display: none;"
	                class="fixed inset-0 z-50 overflow-y-auto"
	                aria-labelledby="modal-title" role="dialog" aria-modal="true"
	            >
	                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
	                    <div x-show="open" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="open = false"></div>
	                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
	                    <div x-show="open" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
	                        <form action="{{ route('platform.tenants.secrets.store', $tenant->id) }}" method="POST">
	                            @csrf
	                            <input type="hidden" name="secret_key" :value="secretKey">
	                            <input type="hidden" name="secret_value" :value="secretValue">

	                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
	                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">Rotate Secret</h3>
	                                <p class="text-sm text-gray-600">
	                                    This generates a new value for the selected secret key. Copy it now if you need it, then sync to <span class="font-mono">.env</span>.
	                                </p>

	                                <div class="mt-4">
	                                    <div class="text-xs font-semibold text-gray-700">Key</div>
	                                    <div class="mt-1 font-mono text-sm text-gray-900" x-text="secretKey"></div>
	                                </div>

	                                <div class="mt-4">
	                                    <div class="flex items-center justify-between">
	                                        <div class="text-xs font-semibold text-gray-700">New value</div>
	                                        <div class="inline-flex items-center gap-2">
	                                            <button type="button" class="text-xs font-semibold text-gray-700 hover:text-gray-900 underline" @click="regenerate()">Regenerate</button>
	                                            <button type="button" class="text-xs font-semibold text-gray-700 hover:text-gray-900 underline" @click="copy()">Copy</button>
	                                        </div>
	                                    </div>
	                                    <textarea
	                                        rows="3"
	                                        readonly
	                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm font-mono bg-gray-50"
	                                        x-text="secretValue"
	                                    ></textarea>
	                                </div>

	                                <div class="mt-4">
	                                    <label class="block text-sm font-medium text-gray-700">
	                                        Type <span class="font-mono" x-text="phrase"></span> to confirm
	                                    </label>
	                                    <input
	                                        type="text"
	                                        x-model="confirmText"
	                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm font-mono"
	                                        :placeholder="phrase"
	                                        autocomplete="off"
	                                    >
	                                </div>
	                            </div>

	                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
	                                <button
	                                    type="submit"
	                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-900 text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-40 disabled:cursor-not-allowed"
	                                    :disabled="confirmText !== phrase || !secretKey"
	                                >
	                                    Rotate
	                                </button>
	                                <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
	                                    Cancel
	                                </button>
	                            </div>
	                        </form>
	                    </div>
	                </div>
	            </div>
		    </div>
		@endsection
