@extends('layouts.platform')

@section('title', 'Security Center')
@section('header', 'Security Center')

@section('content')
    <div class="grid grid-cols-1 gap-6">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Access Control</h2>
                <p class="mt-1 text-sm text-gray-600">IP allowlist + 2FA enforcement for platform admin.</p>
            </div>
            <div class="p-5">
                <div class="mb-4 text-sm text-gray-700">
                    Your current IP: <span class="font-mono px-2 py-1 rounded bg-gray-100">{{ $currentIp ?? '—' }}</span>
                </div>

                <form method="POST" action="{{ route('platform.security.update') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Allowed IPs</label>
                        <input
                            type="text"
                            name="panel_allowed_ips"
                            value="{{ $settings['panel_allowed_ips'] ?? '' }}"
                            class="mt-1 block w-full max-w-3xl border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm font-mono"
                            placeholder="1.2.3.4, 10.0.0.0/24"
                        >
                        <p class="mt-2 text-xs text-gray-500">Comma-separated. Supports exact IP, CIDR, or `*`.</p>
                    </div>

                    <div class="flex items-start gap-3">
                        <input
                            id="force_2fa"
                            name="force_2fa"
                            type="checkbox"
                            value="1"
                            {{ ($settings['force_2fa'] ?? false) ? 'checked' : '' }}
                            class="mt-1 h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                        >
                        <div>
                            <label for="force_2fa" class="text-sm font-medium text-gray-900">Force 2FA (superadmins)</label>
                            <p class="text-xs text-gray-600">If enabled, superadmins without 2FA enabled cannot log in.</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rate limit (per minute)</label>
                        <input
                            type="number"
                            min="1"
                            name="rate_limit_per_minute"
                            value="{{ $settings['rate_limit_per_minute'] ?? 120 }}"
                            class="mt-1 block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                        >
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 shadow-sm">
                            <i class="ph ph-shield-check mr-2"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Two-Factor (Your Account)</h2>
            </div>
            <div class="p-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">
                            Status:
                            @if(Auth::user()?->two_factor_enabled)
                                <span class="ml-2 px-2 py-1 rounded-full text-[11px] font-semibold bg-green-100 text-green-800">enabled</span>
                            @else
                                <span class="ml-2 px-2 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-800">disabled</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-gray-600">When enabled, platform login requires a code sent to your email.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        @if(!Auth::user()?->two_factor_enabled)
                            <form method="POST" action="{{ route('platform.security.2fa.enable') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 shadow-sm">
                                    Enable 2FA
                                </button>
                            </form>
                        @else
                            <a href="{{ route('platform.2fa') }}" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm">
                                <i class="ph ph-key mr-2 text-gray-600"></i> Verify now
                            </a>

                            <form method="POST" action="{{ route('platform.security.2fa.disable') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-red-900 bg-red-50 border border-red-200 hover:bg-red-100 shadow-sm">
                                    Disable
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Sessions</h2>
                <form method="POST" action="{{ route('platform.security.sessions.revoke_other') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm">
                        <i class="ph ph-sign-out mr-2 text-gray-600"></i> Revoke other sessions
                    </button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Session</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IP</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User Agent</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Last activity</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse(($sessions ?? []) as $s)
                            @php
                                $isCurrent = ($s->id ?? '') === ($currentSessionId ?? '');
                                $last = !empty($s->last_activity) ? \Illuminate\Support\Carbon::createFromTimestamp((int) $s->last_activity) : null;
                            @endphp
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-5 py-4 text-sm">
                                    <span class="font-mono text-xs text-gray-700">{{ $s->id ?? '—' }}</span>
                                    @if($isCurrent)
                                        <span class="ml-2 px-2 py-1 rounded-full text-[11px] font-semibold bg-primary-100 text-primary-800">current</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700 font-mono">{{ $s->ip_address ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">
                                    <span class="truncate block max-w-[560px]">{{ $s->user_agent ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700 text-right">{{ $last ? $last->format('Y-m-d H:i') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-600">No sessions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden border border-red-100">
            <div class="px-5 py-4 border-b border-red-100 bg-red-50">
                <h2 class="text-sm font-semibold text-red-900 uppercase tracking-wider">Emergency Lock</h2>
                <p class="mt-1 text-sm text-red-800">Danger zone. This can lock you out if your IP changes.</p>
            </div>
            <div class="p-5">
                <form method="POST" action="{{ route('platform.security.emergency_lock') }}" class="space-y-4">
                    @csrf
                    <div class="text-sm text-gray-700">
                        This will set <span class="font-mono">panel_allowed_ips</span> to your current IP, enable <span class="font-mono">force_2fa</span>, and revoke other sessions.
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Type <span class="font-mono">emergency_lock</span> to confirm
                        </label>
                        <input
                            type="text"
                            name="confirm"
                            class="mt-1 block w-full max-w-md border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm font-mono"
                            placeholder="emergency_lock"
                            required
                            autocomplete="off"
                        >
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-red-600 hover:bg-red-700 shadow-sm">
                        <i class="ph ph-warning mr-2"></i> Apply emergency lock
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

