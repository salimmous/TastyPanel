@extends('layouts.platform')

@section('title', 'Preview Environment - ' . ($tenant->name ?? 'Tenant'))
@section('header', 'Preview: ' . ($tenant->name ?? 'Tenant'))

@section('content')
    <div class="mb-6 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('platform.tenants.show', $tenant->id) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700 hover:text-gray-900">
                <i class="ph ph-arrow-left"></i>
                Back to Site
            </a>
            <span class="text-xs text-gray-500">Tenant #{{ $tenant->id }}</span>
        </div>
        <a href="{{ route('platform.control') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700 hover:text-gray-900">
            <i class="ph ph-command"></i>
            Control Center
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Status -->
        <div class="bg-white shadow rounded-lg overflow-hidden lg:col-span-1">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Preview Status</h2>
            </div>
            <div class="p-5 space-y-5">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-gray-700">Enabled</div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $tenant->preview_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $tenant->preview_enabled ? 'yes' : 'no' }}
                    </span>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Primary Domain</div>
                    <div class="mt-2 text-sm font-semibold text-gray-900">
                        {{ $primaryDomain?->hostname ?? '—' }}
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Preview Domains</div>
                    <div class="mt-2 space-y-1">
                        @forelse(($previewDomains ?? []) as $d)
                            <a href="//{{ $d->hostname }}" target="_blank" class="block text-sm font-semibold text-primary-700 hover:underline">
                                {{ $d->hostname }}
                            </a>
                        @empty
                            <div class="text-sm text-gray-600">No preview domains yet.</div>
                            @if($primaryDomain?->hostname)
                                @php
                                    $host = (string) $primaryDomain->hostname;
                                    $suggested = 'preview.' . $host;
                                @endphp
                                <div class="mt-2 text-xs text-gray-500">
                                    Suggestion: <span class="font-mono">{{ $suggested }}</span>
                                </div>
                            @endif
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Themes</div>
                    <div class="mt-2 text-sm text-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Production</span>
                            <span class="font-semibold text-gray-900">{{ $tenant->theme->name ?? '—' }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-gray-500">Preview</span>
                            <span class="font-semibold text-gray-900">{{ $tenant->previewTheme->name ?? ($tenant->theme->name ?? '—') }}</span>
                        </div>
                    </div>
                </div>

                @if($tenant->preview_enabled)
                    <form action="{{ route('platform.tenants.preview.disable', $tenant->id) }}" method="POST" onsubmit="return confirm('Disable preview environment?');">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-100">
                            <i class="ph ph-power mr-2"></i>
                            Disable Preview
                        </button>
                    </form>
                @else
                    <form action="{{ route('platform.tenants.preview.enable', $tenant->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800">
                            <i class="ph ph-play mr-2"></i>
                            Enable Preview
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white shadow rounded-lg overflow-hidden lg:col-span-2">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Preview Actions</h2>
            </div>
            <div class="p-5">
                @if(!$tenant->preview_enabled)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        Preview is disabled. Enable it first to sync and promote changes.
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="rounded-xl border border-gray-200 p-5 bg-gray-50/60">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="h-10 w-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
                                    <i class="ph ph-arrow-down text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Pull to Preview</div>
                                    <div class="text-xs text-gray-600">Overwrite preview with production</div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-700 mb-4">
                                This copies production theme, settings, and content to preview.
                                <span class="font-semibold">Preview changes will be lost.</span>
                            </p>
                            <form action="{{ route('platform.tenants.preview.sync', $tenant->id) }}" method="POST" onsubmit="return confirm('Overwrite preview with production data?');">
                                @csrf
                                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-semibold text-gray-900 bg-white hover:bg-gray-50 border border-gray-200">
                                    Pull from Production
                                </button>
                            </form>
                        </div>

                        <div class="rounded-xl border border-amber-200 p-5 bg-amber-50/60">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="h-10 w-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                                    <i class="ph ph-rocket-launch text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Promote to Production</div>
                                    <div class="text-xs text-gray-700">Go live with preview changes</div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-800 mb-4">
                                This applies preview theme and settings to production and syncs preview content to production.
                                <span class="font-semibold">This can overwrite production.</span>
                            </p>
                            <form action="{{ route('platform.tenants.preview.promote', $tenant->id) }}" method="POST" onsubmit="return confirm('WARNING: Promote preview to PRODUCTION? Continue?');">
                                @csrf
                                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800">
                                    Promote to Production
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-8 rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-200 bg-white flex items-center justify-between">
                            <div class="text-sm font-semibold text-gray-900">Comparison</div>
                            <div class="text-xs text-gray-500">Themes and settings only</div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Item</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Production</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Preview</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                    <tr>
                                        <td class="px-5 py-3 font-semibold text-gray-900">Theme</td>
                                        <td class="px-5 py-3 text-gray-700">{{ $tenant->theme->name ?? '—' }}</td>
                                        <td class="px-5 py-3 text-gray-700">{{ $tenant->previewTheme->name ?? ($tenant->theme->name ?? '—') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-5 py-3 font-semibold text-gray-900">Settings keys</td>
                                        <td class="px-5 py-3 text-gray-700">{{ is_array($tenant->settings?->data) ? count($tenant->settings->data) : 0 }}</td>
                                        <td class="px-5 py-3 text-gray-700">{{ is_array($tenant->previewSettings?->data) ? count($tenant->previewSettings->data) : 0 }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

