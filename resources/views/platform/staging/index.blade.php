@extends('layouts.platform')

@section('title', 'Staging Environment - ' . $tenant->name)
@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('platform.tenants.show', $tenant->id) }}" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <span>Staging Environment: {{ $tenant->name }}</span>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Status Card -->
        <div class="card p-6 lg:col-span-1">
            <h3 class="font-semibold text-lg mb-4">Environment Status</h3>
            
            <div class="flex items-center justify-between mb-6">
                <span class="text-sm font-medium text-gray-700">Status</span>
                <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $tenant->staging_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $tenant->staging_enabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>

            @if($tenant->staging_enabled)
                <div class="space-y-4">
                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                        <div class="text-sm text-blue-800 font-medium mb-1">Staging URL</div>
                        <div class="text-xs text-blue-600 break-all">
                             http://staging.{{ $tenant->domains->firstWhere('is_primary', true)->domain ?? 'unknown' }}
                        </div>
                    </div>

                    <form action="{{ route('platform.tenants.staging.disable', $tenant->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to disable staging? This will remove access to the staging environment.');">
                        @csrf
                        <button type="submit" class="w-full btn btn-danger">Disable Staging</button>
                    </form>
                </div>
            @else
                <div class="text-sm text-gray-500 mb-6">
                    Enable staging to create a safe environment for testing changes before they go live. A separate database and file structure will be created.
                </div>
                <form action="{{ route('platform.tenants.staging.enable', $tenant->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full btn btn-primary">Enable Staging Environment</button>
                </form>
            @endif
        </div>

        @if($tenant->staging_enabled)
        <!-- Sync Controls -->
        <div class="card p-6 lg:col-span-2">
            <h3 class="font-semibold text-lg mb-4">Environment Synchronization</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Prod -> Staging -->
                <div class="border rounded-lg p-6 bg-gray-50">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-blue-100 rounded-lg text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">Pull to Staging</div>
                            <div class="text-xs text-gray-500">Overwrite staging with production data</div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        This will copy the production theme, settings, and database content to the staging environment. 
                        <strong>All changes in staging will be lost.</strong>
                    </p>
                    <form action="{{ route('platform.tenants.staging.sync', $tenant->id) }}" method="POST" onsubmit="return confirm('Overwrite staging with production data?');">
                        @csrf
                        <input type="hidden" name="direction" value="prod_to_staging">
                        <button type="submit" class="btn btn-secondary w-full">Pull from Production</button>
                    </form>
                </div>

                <!-- Staging -> Prod -->
                <div class="border rounded-lg p-6 bg-yellow-50 border-yellow-100">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-yellow-100 rounded-lg text-yellow-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">Push to Production</div>
                            <div class="text-xs text-gray-500">Go Live with staging changes</div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        This will apply the current staging theme and settings to production. 
                        <strong>Production data may be overwritten.</strong>
                    </p>
                    <form action="{{ route('platform.tenants.staging.sync', $tenant->id) }}" method="POST" onsubmit="return confirm('WARNING: You are about to push staging changes to PRODUCTION. Continue?');">
                        @csrf
                        <input type="hidden" name="direction" value="staging_to_prod">
                        <button type="submit" class="btn btn-primary w-full">Promote to Production</button>
                    </form>
                </div>
            </div>

            <div class="mt-8">
                <h4 class="font-semibold text-gray-800 mb-2">Comparison</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase font-medium">
                            <tr>
                                <th class="px-4 py-3">Feature</th>
                                <th class="px-4 py-3">Production (Live)</th>
                                <th class="px-4 py-3">Staging (Test)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">Current Theme</td>
                                <td class="px-4 py-3">{{ $tenant->theme->name ?? 'None' }}</td>
                                <td class="px-4 py-3">{{ $tenant->stagingTheme->name ?? ($tenant->theme->name ?? 'None') }}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">Last Synced</td>
                                <td class="px-4 py-3 text-gray-500">-</td>
                                <td class="px-4 py-3 text-gray-500">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection
