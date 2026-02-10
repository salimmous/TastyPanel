@extends('layouts.platform')

@section('title', 'Monitoring Rules')
@section('header', 'Monitoring Rules')

@section('content')
    <div class="sm:flex sm:items-center sm:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Monitoring Rules</h1>
            <p class="mt-2 text-sm text-gray-700">Per-tenant alert rules + notification channels (email/Slack).</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('platform.monitoring') }}" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm">
                <i class="ph ph-waveform mr-2 text-gray-600"></i> Monitoring Center
            </a>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Platform Defaults</h2>
            <p class="mt-1 text-xs text-gray-600">Used when a tenant rule is missing or fields are empty.</p>
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Interval</div>
                <div class="mt-2 text-xl font-bold text-gray-900">{{ (int) ($platformDefaults['alerts_interval_hours'] ?? 24) }}h</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">SSL Days</div>
                <div class="mt-2 text-xl font-bold text-gray-900">{{ (int) ($platformDefaults['ssl_alert_days'] ?? 14) }}d</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Emails</div>
                <div class="mt-2 text-sm text-gray-900 truncate">{{ ($platformDefaults['alerts_emails'] ?? '') ?: '—' }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Slack</div>
                <div class="mt-2 text-sm text-gray-900 truncate">{{ ($platformDefaults['alerts_slack_webhook'] ?? '') ? 'configured' : '—' }}</div>
            </div>
        </div>
    </div>

    <div
        class="bg-white shadow rounded-lg overflow-hidden"
        x-data="{
            open: false,
            resetOpen: false,
            current: {
                tenant_id: null,
                tenant_name: '',
                enabled: true,
                emails: '',
                slack_webhook: '',
                interval_hours: '',
                ssl_days: '',
                notify_ssl: true,
                notify_uptime: true,
                notify_backup: true,
                notify_http3: false,
                notify_storage: true,
                has_rule: false,
            },
            openEdit(payload) {
                this.current = Object.assign({
                    tenant_id: null,
                    tenant_name: '',
                    enabled: true,
                    emails: '',
                    slack_webhook: '',
                    interval_hours: '',
                    ssl_days: '',
                    notify_ssl: true,
                    notify_uptime: true,
                    notify_backup: true,
                    notify_http3: false,
                    notify_storage: true,
                    has_rule: false,
                }, payload || {});
                this.open = true;
            },
            openReset(payload) {
                this.current = Object.assign(this.current, payload || {});
                this.resetOpen = true;
            },
        }"
    >
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Tenants</h2>

            <form method="GET" action="{{ route('platform.monitoring.rules') }}" class="flex items-center gap-2">
                <div class="relative">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q ?? '' }}"
                        placeholder="Search tenant…"
                        class="pl-9 pr-3 py-2 rounded-md border border-gray-200 bg-white text-sm w-[260px] focus:ring-primary-500 focus:border-primary-500 shadow-sm"
                    >
                </div>
                <button type="submit" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 shadow-sm">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tenant</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Channels</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rules</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Interval</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">SSL Days</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse(($tenants ?? []) as $t)
                        @php
                            $r = $t->alertRule;
                            $hasEmails = !empty(trim((string) ($r?->emails ?? '')));
                            $hasSlack = !empty(trim((string) ($r?->slack_webhook ?? '')));
                        @endphp
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-4 text-sm">
                                <div class="font-semibold text-gray-900">{{ $t->name }}</div>
                                <div class="mt-1 text-xs text-gray-500 font-mono">{{ $t->slug }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ $hasEmails ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        email {{ $hasEmails ? 'on' : 'off' }}
                                    </span>
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ $hasSlack ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        slack {{ $hasSlack ? 'on' : 'off' }}
                                    </span>
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ ($r?->enabled ?? false) ? 'bg-primary-100 text-primary-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ ($r?->enabled ?? false) ? 'enabled' : 'disabled' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                <div class="flex flex-wrap gap-2">
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ ($r?->notify_uptime ?? false) ? 'bg-slate-100 text-slate-800' : 'bg-gray-100 text-gray-600' }}">uptime</span>
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ ($r?->notify_ssl ?? false) ? 'bg-slate-100 text-slate-800' : 'bg-gray-100 text-gray-600' }}">ssl</span>
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ ($r?->notify_backup ?? false) ? 'bg-slate-100 text-slate-800' : 'bg-gray-100 text-gray-600' }}">backup</span>
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ ($r?->notify_http3 ?? false) ? 'bg-slate-100 text-slate-800' : 'bg-gray-100 text-gray-600' }}">http3</span>
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ ($r?->notify_storage ?? false) ? 'bg-slate-100 text-slate-800' : 'bg-gray-100 text-gray-600' }}">storage</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700 font-mono">
                                {{ $r?->interval_hours ? ($r->interval_hours . 'h') : 'default' }}
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700 font-mono">
                                {{ $r?->ssl_days ? ($r->ssl_days . 'd') : 'default' }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2 justify-end">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                                        @click="openEdit({
                                            tenant_id: {{ (int) $t->id }},
                                            tenant_name: @js($t->name),
                                            enabled: {{ ($r?->enabled ?? true) ? 'true' : 'false' }},
                                            emails: @js((string) ($r?->emails ?? '')),
                                            slack_webhook: @js((string) ($r?->slack_webhook ?? '')),
                                            interval_hours: @js($r?->interval_hours ? (string) $r->interval_hours : ''),
                                            ssl_days: @js($r?->ssl_days ? (string) $r->ssl_days : ''),
                                            notify_ssl: {{ ($r?->notify_ssl ?? true) ? 'true' : 'false' }},
                                            notify_uptime: {{ ($r?->notify_uptime ?? true) ? 'true' : 'false' }},
                                            notify_backup: {{ ($r?->notify_backup ?? true) ? 'true' : 'false' }},
                                            notify_http3: {{ ($r?->notify_http3 ?? false) ? 'true' : 'false' }},
                                            notify_storage: {{ ($r?->notify_storage ?? true) ? 'true' : 'false' }},
                                            has_rule: {{ $r ? 'true' : 'false' }},
                                        })"
                                    >
                                        <i class="ph ph-pencil-simple mr-1 text-gray-600"></i> Edit
                                    </button>

                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold text-red-900 bg-red-50 border border-red-200 hover:bg-red-100 shadow-sm"
                                        @click="openReset({ tenant_id: {{ (int) $t->id }}, tenant_name: @js($t->name) })"
                                    >
                                        <i class="ph ph-trash mr-1"></i> Reset
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-600">No tenants found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($tenants, 'hasPages') && $tenants->hasPages())
            <div class="px-5 py-4 border-t border-gray-200 bg-white">
                {{ $tenants->links() }}
            </div>
        @endif

        <!-- Edit Modal -->
        <div x-show="open" x-transition.opacity style="display:none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="open = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form method="POST" :action="`{{ url('/platform/monitoring/rules') }}/${current.tenant_id}`">
                        @csrf

                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2" id="modal-title">Edit tenant rule</h3>
                            <p class="text-sm text-gray-600">
                                Tenant: <span class="font-semibold text-gray-900" x-text="current.tenant_name"></span>
                            </p>

                            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2 flex items-start gap-3">
                                    <input type="hidden" name="enabled" value="0">
                                    <input type="checkbox" name="enabled" value="1" x-model="current.enabled" class="mt-1 h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Enabled</div>
                                        <div class="text-xs text-gray-600">If disabled, this tenant will not receive tenant-scoped notifications.</div>
                                    </div>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Emails</label>
                                    <input type="text" name="emails" x-model="current.emails" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="ops@example.com, owner@example.com">
                                    <p class="mt-2 text-xs text-gray-500">Comma-separated list.</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Slack webhook</label>
                                    <input type="url" name="slack_webhook" x-model="current.slack_webhook" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm font-mono" placeholder="https://hooks.slack.com/...">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Interval (hours)</label>
                                    <input type="number" min="1" name="interval_hours" x-model="current.interval_hours" class="mt-1 block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="default">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">SSL days</label>
                                    <input type="number" min="1" name="ssl_days" x-model="current.ssl_days" class="mt-1 block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="default">
                                </div>

                                <div class="md:col-span-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Categories</div>
                                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <label class="flex items-center gap-2 text-sm text-gray-800">
                                            <input type="hidden" name="notify_uptime" value="0">
                                            <input type="checkbox" name="notify_uptime" value="1" x-model="current.notify_uptime" class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                            Uptime failures
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-gray-800">
                                            <input type="hidden" name="notify_ssl" value="0">
                                            <input type="checkbox" name="notify_ssl" value="1" x-model="current.notify_ssl" class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                            SSL expiring
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-gray-800">
                                            <input type="hidden" name="notify_backup" value="0">
                                            <input type="checkbox" name="notify_backup" value="1" x-model="current.notify_backup" class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                            Backup failures
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-gray-800">
                                            <input type="hidden" name="notify_http3" value="0">
                                            <input type="checkbox" name="notify_http3" value="1" x-model="current.notify_http3" class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                            HTTP/3 issues
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-gray-800">
                                            <input type="hidden" name="notify_storage" value="0">
                                            <input type="checkbox" name="notify_storage" value="1" x-model="current.notify_storage" class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                            Storage overages
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-900 text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Save
                            </button>
                            <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reset Modal -->
        <div x-show="resetOpen" x-transition.opacity style="display:none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="resetOpen = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form method="POST" :action="`{{ url('/platform/monitoring/rules') }}/${current.tenant_id}`">
                        @csrf
                        @method('DELETE')
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2" id="modal-title">Reset tenant rule</h3>
                            <p class="text-sm text-gray-700">
                                This will delete the custom rule for <span class="font-semibold" x-text="current.tenant_name"></span> and fall back to platform defaults.
                            </p>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Reset
                            </button>
                            <button type="button" @click="resetOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

