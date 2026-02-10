@extends('layouts.platform')

@section('title', 'Platform Overview')
@section('header', 'Platform Overview')

@section('content')
    <!-- System Metrics -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        <!-- CPU Load -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-blue-500">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">CPU Load Average</h3>
                    <div class="p-2 bg-blue-50 rounded-lg">
                        <i class="ph ph-cpu text-blue-500 text-2xl"></i>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">1 min:</span>
                        <span class="font-mono font-bold text-gray-900">{{ $systemMetrics['load']['1m'] }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">5 min:</span>
                        <span class="font-mono font-bold text-gray-900">{{ $systemMetrics['load']['5m'] }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">15 min:</span>
                        <span class="font-mono font-bold text-gray-900">{{ $systemMetrics['load']['15m'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Memory Usage -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-green-500">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Memory Usage</h3>
                    <div class="p-2 bg-green-50 rounded-lg">
                        <i class="ph ph-memory text-green-500 text-2xl"></i>
                    </div>
                </div>
                @if($systemMetrics['memory']['total_mb'])
                    <div class="mt-2">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-500">{{ $systemMetrics['memory']['used_mb'] }} MB / {{ $systemMetrics['memory']['total_mb'] }} MB</span>
                            <span class="font-bold text-gray-900">{{ $systemMetrics['memory']['percent'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-green-500 h-2.5 rounded-full" style="width: {{ $systemMetrics['memory']['percent'] }}%"></div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Used: {{ $systemMetrics['memory']['used_mb'] }} MB</p>
                @endif
            </div>
        </div>

        <!-- Disk Usage -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-purple-500">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Disk Usage</h3>
                    <div class="p-2 bg-purple-50 rounded-lg">
                        <i class="ph ph-hard-drives text-purple-500 text-2xl"></i>
                    </div>
                </div>
                @if($systemMetrics['disk']['total_gb'])
                    <div class="mt-2">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-500">{{ $systemMetrics['disk']['used_gb'] }} GB / {{ $systemMetrics['disk']['total_gb'] }} GB</span>
                            <span class="font-bold text-gray-900">{{ $systemMetrics['disk']['percent'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-purple-500 h-2.5 rounded-full" style="width: {{ $systemMetrics['disk']['percent'] }}%"></div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Disk info unavailable</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg p-5 text-center">
            <div class="text-3xl font-bold text-blue-600">{{ $stats['tenants'] }}</div>
            <div class="text-sm text-gray-500 mt-1">Total Sites</div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg p-5 text-center">
            <div class="text-3xl font-bold text-green-600">{{ $stats['users'] }}</div>
            <div class="text-sm text-gray-500 mt-1">Users</div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg p-5 text-center">
            <div class="text-3xl font-bold text-purple-600">{{ $stats['domains'] }}</div>
            <div class="text-sm text-gray-500 mt-1">Domains</div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg p-5 text-center">
            <div class="text-3xl font-bold text-orange-600">{{ $stats['backups'] }}</div>
            <div class="text-sm text-gray-500 mt-1">Backups</div>
        </div>
    </div>

    <!-- Services & Queue -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Services Status -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Services Status</h3>
            </div>
            <div class="border-t border-gray-200 divide-y divide-gray-200">
                @foreach($services as $name => $status)
                    <div class="px-4 py-4 sm:px-6 flex items-center justify-between hover:bg-gray-50 transition">
                        <div>
                            <div class="text-sm font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $name) }}</div>
                            <div class="text-xs text-gray-500 mt-0.5 capitalize">{{ ucfirst($status) }}</div>
                        </div>
                        <span class="flex-shrink-0 inline-block h-3 w-3 rounded-full {{ $status === 'running' ? 'bg-green-500' : 'bg-red-500' }}"></span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Queue Status -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Queue Status</h3>
            </div>
            <div class="p-4 space-y-4">
                <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                    <div class="flex-shrink-0 p-3 rounded-md bg-blue-100 text-blue-600">
                        <i class="ph ph-hourglass text-2xl"></i>
                    </div>
                    <div class="ml-4 flex-1">
                        <h4 class="text-sm font-medium text-blue-900">Pending Jobs</h4>
                        <div class="mt-1 text-2xl font-bold text-blue-600">{{ $queueSize }}</div>
                    </div>
                </div>

                <div class="flex items-center p-4 bg-red-50 rounded-lg">
                    <div class="flex-shrink-0 p-3 rounded-md bg-red-100 text-red-600">
                        <i class="ph ph-warning-circle text-2xl"></i>
                    </div>
                    <div class="ml-4 flex-1">
                        <h4 class="text-sm font-medium text-red-900">Failed Jobs</h4>
                        <div class="mt-1 text-2xl font-bold text-red-600">{{ $failedJobs }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Tenants -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Sites</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                @forelse($recentTenants as $tenant)
                    <li>
                        <a href="{{ route('platform.tenants') }}" class="block hover:bg-gray-50 transition">
                            <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center">
                                        <span class="font-medium text-slate-600">{{ substr($tenant->name, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-blue-600 truncate">{{ $tenant->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $tenant->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                                <div class="ml-2 flex-shrink-0">
                                    <i class="ph ph-caret-right text-gray-400"></i>
                                </div>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-4 sm:px-6 text-center text-sm text-gray-500">No sites yet</li>
                @endforelse
            </ul>
        </div>

        <!-- Recent Backups -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Backups</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                @forelse($recentBackups as $backup)
                    <li>
                        <a href="{{ route('platform.backups') }}" class="block hover:bg-gray-50 transition">
                            <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full {{ $backup->status === 'success' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }} flex items-center justify-center">
                                        <i class="ph {{ $backup->status === 'success' ? 'ph-check' : 'ph-x' }} text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $backup->disk }}</div>
                                        <div class="text-xs text-gray-500">{{ $backup->created_at->format('Y-m-d H:i') }}</div>
                                    </div>
                                </div>
                                <div>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $backup->status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($backup->status) }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-4 sm:px-6 text-center text-sm text-gray-500">No backups yet</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
