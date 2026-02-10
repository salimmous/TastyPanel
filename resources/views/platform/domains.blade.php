@extends('layouts.platform')

@section('title', 'Domain Center')
@section('header', 'Domain Center')

@section('content')
    <div class="sm:flex sm:items-center sm:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Domain Center</h1>
            <p class="mt-2 text-sm text-gray-700">Domain inventory (prod/staging/preview) + SSL/HTTP3/Nginx actions.</p>
        </div>

        <form method="GET" action="{{ route('platform.domains') }}" class="flex items-center gap-2">
            <div class="relative">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input
                    type="text"
                    name="q"
                    value="{{ $q ?? '' }}"
                    placeholder="Search hostname or tenant…"
                    class="pl-9 pr-3 py-2 rounded-md border border-gray-200 bg-white text-sm w-[260px] focus:ring-primary-500 focus:border-primary-500 shadow-sm"
                >
            </div>
            <select name="env" class="py-2 rounded-md border border-gray-200 bg-white text-sm focus:ring-primary-500 focus:border-primary-500 shadow-sm">
                <option value="">All envs</option>
                <option value="production" {{ ($env ?? '') === 'production' ? 'selected' : '' }}>production</option>
                <option value="staging" {{ ($env ?? '') === 'staging' ? 'selected' : '' }}>staging</option>
                <option value="preview" {{ ($env ?? '') === 'preview' ? 'selected' : '' }}>preview</option>
            </select>
            <button type="submit" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 shadow-sm">
                Filter
            </button>
        </form>
    </div>

    @if(isset($lastAction) && $lastAction)
        <div class="mb-6 rounded-lg border {{ $lastSuccess ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} p-4 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-semibold {{ $lastSuccess ? 'text-green-900' : 'text-red-900' }}">
                        Last action: {{ $lastAction }}
                        @if(!empty($lastDomainId))
                            <span class="text-xs font-mono text-gray-700">domain#{{ $lastDomainId }}</span>
                        @endif
                    </p>
                    @if(isset($lastOutput) && $lastOutput)
                        <pre class="mt-3 text-xs whitespace-pre-wrap break-words rounded-md bg-white/70 border border-black/5 p-3 max-h-80 overflow-auto">{{ $lastOutput }}</pre>
                    @endif
                </div>
                <a href="{{ route('platform.audit_logs') }}" class="shrink-0 text-sm font-medium text-gray-700 hover:text-gray-900">
                    View audit logs
                </a>
            </div>
        </div>
    @endif

    @php
        $stats = $stats ?? [];
        $sslDays = $sslDays ?? 14;
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white shadow rounded-lg p-4 border border-gray-100">
            <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Domains</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($stats['total'] ?? 0) }}</div>
            <div class="mt-1 text-xs text-gray-600">All envs</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4 border border-gray-100">
            <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Production</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($stats['production'] ?? 0) }}</div>
            <div class="mt-1 text-xs text-gray-600">Live</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4 border border-gray-100">
            <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Staging</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($stats['staging'] ?? 0) }}</div>
            <div class="mt-1 text-xs text-gray-600">Sync enabled</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4 border border-gray-100">
            <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Preview</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($stats['preview'] ?? 0) }}</div>
            <div class="mt-1 text-xs text-gray-600">Full page preview</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4 border border-gray-100">
            <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Warnings</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($stats['ssl_expiring'] ?? 0) + (int) ($stats['http3_issues'] ?? 0) }}</div>
            <div class="mt-1 text-xs text-gray-600">SSL expiring + HTTP/3 issues</div>
        </div>
    </div>

    <div
        class="bg-white shadow rounded-lg overflow-hidden"
        x-data="{
            open: false,
            domainId: '',
            actionId: '',
            confirmText: '',
            actions: @js($domainActions ?? []),
            openModal(domainId, actionId) {
                this.domainId = String(domainId || '');
                const keys = Object.keys(this.actions || {});
                const requested = String(actionId || '');
                this.actionId = (requested && (this.actions || {})[requested])
                    ? requested
                    : (keys.length ? keys[0] : '');
                this.confirmText = '';
                this.open = true;
            },
            phrase() {
                const action = (this.actions || {})[this.actionId] || {};
                return String(action.confirm || '');
            },
            canRun() {
                const p = this.phrase();
                return !p || this.confirmText === p;
            },

            bulkOpen: false,
            bulkConfirmText: '',
            bulkPhrase: 'ssl_renew_expiring',
            canBulkRun() {
                return this.bulkConfirmText === this.bulkPhrase;
            },

            dnsOpen: false,
            dnsLoading: false,
            dnsError: '',
            dnsDomainId: '',
            dnsHostname: '',
            dnsZoneId: '',
            dnsRecordId: '',
            dnsRecordById: null,
            dnsRecordsByName: [],
            openDns(domainId, hostname, url) {
                this.dnsDomainId = String(domainId || '');
                this.dnsHostname = String(hostname || '');
                this.dnsZoneId = '';
                this.dnsRecordId = '';
                this.dnsRecordById = null;
                this.dnsRecordsByName = [];
                this.dnsError = '';
                this.dnsLoading = true;
                this.dnsOpen = true;

                fetch(String(url || ''), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(async (r) => {
                        const json = await r.json().catch(() => ({}));
                        if (!r.ok) throw new Error(json.message || 'Failed to load DNS inventory');
                        return json;
                    })
                    .then((data) => {
                        this.dnsZoneId = String(data.zone_id || '');
                        this.dnsRecordId = String((data.domain || {}).cf_record_id || '');
                        this.dnsRecordById = data.record_by_id || null;
                        this.dnsRecordsByName = Array.isArray(data.records_by_name) ? data.records_by_name : [];
                    })
                    .catch((e) => {
                        this.dnsError = String(e && e.message ? e.message : e);
                    })
                    .finally(() => {
                        this.dnsLoading = false;
                    });
            },
        }"
    >
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Domains</h2>
                <div class="mt-1 text-xs text-gray-500">
                    Showing {{ method_exists($domains, 'count') ? $domains->count() : 0 }}
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center px-3 py-2 rounded-md text-sm font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                    @click="bulkConfirmText = ''; bulkOpen = true"
                >
                    <i class="ph ph-certificate mr-2 text-gray-600"></i> Renew expiring SSL
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Hostname</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tenant</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Env</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">DNS</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">SSL</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nginx</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">HTTP/3</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Warnings</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse(($domains ?? []) as $d)
                        @php
                            $cert = $d->sslCertificate;
                            $sslStatus = $cert?->status ?? 'missing';
                            $sslExpiresAt = $cert?->expires_at;
                            $sslDaysLeft = $sslExpiresAt ? now()->diffInDays($sslExpiresAt, false) : null;
                            $sslExpiring = $sslDaysLeft !== null && $sslDaysLeft <= $sslDays;
                            $zoneId = $d->cf_zone_id ?: (string) config('services.cloudflare.zone_id');

                            $warnings = [];
                            if (($d->status ?? '') !== 'active') $warnings[] = 'status:' . ($d->status ?? 'unknown');
                            if (!empty($d->nginx_status) && !in_array($d->nginx_status, ['ok', 'active'], true)) $warnings[] = 'nginx:' . $d->nginx_status;
                            if ($sslStatus !== 'issued') $warnings[] = 'ssl:' . $sslStatus;
                            if ($sslExpiring) $warnings[] = 'ssl_expiring';
                            if ($d->http3_enabled && !in_array($d->http3_status, ['ok', 'advertised'], true)) $warnings[] = 'http3:' . ($d->http3_status ?? 'unknown');
                            if ($d->has_custom_nginx) $warnings[] = 'custom_nginx';
                            if (empty($zoneId)) $warnings[] = 'cf_zone_missing';
                            if (empty($d->cf_record_id)) $warnings[] = 'cf_record_missing';
                        @endphp
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-4">
                                <div class="text-sm font-semibold text-gray-900">{{ $d->hostname }}</div>
                                <div class="mt-1 text-xs text-gray-500 font-mono">
                                    <a href="https://{{ $d->hostname }}" target="_blank" class="text-primary-700 hover:underline">https://{{ $d->hostname }}</a>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                <a href="{{ route('platform.tenants.show', $d->tenant_id) }}" class="font-semibold text-gray-900 hover:underline">
                                    {{ $d->tenant?->name ?? ('tenant#' . $d->tenant_id) }}
                                </a>
                                <div class="mt-1 text-xs text-gray-500 font-mono">{{ $d->tenant?->slug ?? '—' }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ ($d->environment ?? '') === 'production' ? 'bg-green-100 text-green-800' : (($d->environment ?? '') === 'staging' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-800') }}">
                                    {{ $d->environment ?? 'production' }}
                                </span>
                                @if($d->is_primary && ($d->environment ?? '') === 'production')
                                    <span class="ml-2 px-2 py-1 rounded-full text-[11px] font-semibold bg-purple-100 text-purple-800">primary</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ !empty($zoneId) ? 'bg-slate-100 text-slate-800' : 'bg-red-100 text-red-800' }}">
                                        zone {{ !empty($zoneId) ? 'set' : 'missing' }}
                                    </span>
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ !empty($d->cf_record_id) ? 'bg-slate-100 text-slate-800' : 'bg-amber-100 text-amber-800' }}">
                                        record {{ !empty($d->cf_record_id) ? 'set' : 'missing' }}
                                    </span>
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                                        @click="openDns({{ (int) $d->id }}, @js($d->hostname), @js(route('platform.domains.dns', $d)))"
                                    >
                                        <i class="ph ph-tree-structure mr-1 text-gray-600"></i> DNS
                                    </button>
                                </div>
                                @if(!empty($zoneId))
                                    <div class="mt-1 text-[11px] text-gray-500 font-mono truncate max-w-[260px]">
                                        {{ $zoneId }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ $sslStatus === 'issued' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $sslStatus }}
                                </span>
                                @if($sslExpiresAt)
                                    <div class="mt-1 text-xs {{ $sslExpiring ? 'text-red-700' : 'text-gray-600' }}">
                                        expires {{ $sslExpiresAt->format('Y-m-d') }}
                                        @if($sslDaysLeft !== null)
                                            <span class="font-mono">({{ $sslDaysLeft }}d)</span>
                                        @endif
                                    </div>
                                @endif
                                @if(!empty($cert?->last_error))
                                    <div class="mt-1 text-xs text-red-700 truncate max-w-[360px]">{{ $cert->last_error }}</div>
                                @endif
                                <div class="mt-3 flex items-center gap-2 flex-wrap">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                                        @click="openModal({{ (int) $d->id }}, 'domain_ssl_provision_force')"
                                    >
                                        <i class="ph ph-certificate mr-1 text-gray-600"></i> Renew
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                                        @click="openModal({{ (int) $d->id }}, 'domain_ssl_request')"
                                    >
                                        <i class="ph ph-clipboard-text mr-1 text-gray-600"></i> Request
                                    </button>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ in_array(($d->nginx_status ?? ''), ['ok', 'active'], true) ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $d->nginx_status ?: 'unknown' }}
                                </span>
                                @if(!empty($d->nginx_error))
                                    <div class="mt-1 text-xs text-gray-600 truncate max-w-[360px]">{{ $d->nginx_error }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                @if($d->http3_enabled)
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ in_array(($d->http3_status ?? ''), ['ok', 'advertised'], true) ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $d->http3_status ?: 'enabled' }}
                                    </span>
                                    @if(!empty($d->http3_error))
                                        <div class="mt-1 text-xs text-gray-600 truncate max-w-[360px]">{{ $d->http3_error }}</div>
                                    @endif
                                @else
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-800">off</span>
                                @endif
                                <div class="mt-3 flex items-center gap-2 flex-wrap">
                                    @if($d->has_custom_nginx)
                                        <span class="text-xs text-gray-500">Toggle disabled (custom Nginx)</span>
                                    @else
                                        @if($d->http3_enabled)
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                                                @click="openModal({{ (int) $d->id }}, 'domain_http3_disable')"
                                            >
                                                <i class="ph ph-toggle-left mr-1 text-gray-600"></i> Disable
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                                                @click="openModal({{ (int) $d->id }}, 'domain_http3_enable')"
                                            >
                                                <i class="ph ph-toggle-right mr-1 text-gray-600"></i> Enable
                                            </button>
                                        @endif
                                    @endif
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                                        @click="openModal({{ (int) $d->id }}, 'domain_http3_check')"
                                    >
                                        <i class="ph ph-waveform mr-1 text-gray-600"></i> Check
                                    </button>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                @if(!empty($warnings))
                                    <div class="flex flex-wrap gap-2">
                                        @foreach(array_slice($warnings, 0, 3) as $w)
                                            <span class="px-2 py-1 rounded-full text-[11px] font-semibold bg-red-100 text-red-800">{{ $w }}</span>
                                        @endforeach
                                        @if(count($warnings) > 3)
                                            <span class="px-2 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-800">+{{ count($warnings) - 3 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center px-3 py-2 rounded-md text-sm font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                                    @click="openModal({{ (int) $d->id }})"
                                >
                                    <i class="ph ph-play mr-2 text-gray-600"></i> Run
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-sm text-gray-600">No domains found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($domains, 'hasPages') && $domains->hasPages())
            <div class="px-5 py-4 border-t border-gray-200 bg-white">
                {{ $domains->links() }}
            </div>
        @endif

        <!-- Bulk SSL Renew Modal -->
        <div
            x-show="bulkOpen"
            x-transition.opacity
            style="display: none;"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="bulkOpen = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form method="POST" action="{{ route('platform.control.run') }}">
                        @csrf
                        <input type="hidden" name="action_id" value="ssl_renew_expiring">

                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2" id="modal-title">Renew expiring SSL</h3>
                            <p class="text-sm text-gray-600">
                                This will attempt to renew certificates expiring soon (based on <span class="font-mono">ssl_alert_days</span>). It can take time.
                            </p>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">
                                    Type <span class="font-mono" x-text="bulkPhrase"></span> to confirm
                                </label>
                                <input
                                    type="text"
                                    name="confirm"
                                    x-model="bulkConfirmText"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm font-mono"
                                    :placeholder="bulkPhrase"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-900 text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                                :disabled="!canBulkRun()"
                            >
                                Run
                            </button>
                            <button type="button" @click="bulkOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- DNS Inventory Modal -->
        <div
            x-show="dnsOpen"
            x-transition.opacity
            style="display: none;"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="dnsOpen = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-1" id="modal-title">DNS inventory</h3>
                                <div class="text-sm text-gray-600">
                                    <span class="font-mono" x-text="dnsHostname"></span>
                                    <span class="text-xs text-gray-500" x-text="dnsDomainId ? ('(domain#' + dnsDomainId + ')') : ''"></span>
                                </div>
                            </div>
                            <button type="button" class="text-gray-500 hover:text-gray-900" @click="dnsOpen = false">
                                <i class="ph ph-x text-2xl"></i>
                            </button>
                        </div>

                        <div class="mt-4" x-show="dnsLoading">
                            <div class="text-sm text-gray-700">Loading Cloudflare DNS records…</div>
                        </div>

                        <div class="mt-4" x-show="dnsError">
                            <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800" x-text="dnsError"></div>
                        </div>

                        <div class="mt-4 space-y-4" x-show="!dnsLoading && !dnsError">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                    <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Zone</div>
                                    <div class="mt-1 font-mono text-xs text-gray-800 break-all" x-text="dnsZoneId || '—'"></div>
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                    <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Stored record id</div>
                                    <div class="mt-1 font-mono text-xs text-gray-800 break-all" x-text="dnsRecordId || '—'"></div>
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-200 overflow-hidden">
                                <div class="px-4 py-3 bg-white border-b border-gray-200">
                                    <div class="text-sm font-semibold text-gray-900">Record (by stored id)</div>
                                    <div class="text-xs text-gray-500">If `cf_record_id` is set, we load it directly.</div>
                                </div>
                                <div class="p-4 bg-gray-50">
                                    <template x-if="!dnsRecordById">
                                        <div class="text-sm text-gray-600">No record loaded by id.</div>
                                    </template>
                                    <template x-if="dnsRecordById">
                                        <pre class="text-xs whitespace-pre-wrap break-words rounded-md bg-white border border-black/5 p-3" x-text="JSON.stringify(dnsRecordById, null, 2)"></pre>
                                    </template>
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-200 overflow-hidden">
                                <div class="px-4 py-3 bg-white border-b border-gray-200 flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Records (by hostname)</div>
                                        <div class="text-xs text-gray-500">Cloudflare query: `name = hostname`</div>
                                    </div>
                                    <div class="text-xs text-gray-600" x-text="(dnsRecordsByName || []).length + ' record(s)'"></div>
                                </div>
                                <div class="overflow-x-auto bg-white">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Content</th>
                                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Proxied</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <template x-for="r in (dnsRecordsByName || [])" :key="r.id">
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-3 text-sm font-mono" x-text="r.type || '—'"></td>
                                                    <td class="px-4 py-3 text-sm font-mono" x-text="r.name || '—'"></td>
                                                    <td class="px-4 py-3 text-sm font-mono" x-text="r.content || '—'"></td>
                                                    <td class="px-4 py-3 text-sm text-right">
                                                        <span class="px-2 py-1 rounded-full text-[11px] font-semibold"
                                                            :class="r.proxied ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                                            x-text="r.proxied ? 'yes' : 'no'"
                                                        ></span>
                                                    </td>
                                                </tr>
                                            </template>
                                            <template x-if="(dnsRecordsByName || []).length === 0">
                                                <tr>
                                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-600">No records found for this hostname.</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="dnsOpen = false" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Modal -->
        <div
            x-show="open"
            x-transition.opacity
            style="display: none;"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="open = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form method="POST" action="{{ route('platform.control.run') }}">
                        @csrf
                        <input type="hidden" name="domain_id" :value="domainId">

                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2" id="modal-title">Run domain action</h3>
                            <p class="text-sm text-gray-600">
                                Select an action for <span class="font-mono" x-text="'domain#' + domainId"></span>.
                            </p>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Action</label>
                                <select
                                    name="action_id"
                                    x-model="actionId"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                >
                                    <template x-for="(meta, key) in actions" :key="key">
                                        <option :value="key" x-text="meta.label"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="mt-4" x-show="phrase()">
                                <label class="block text-sm font-medium text-gray-700">
                                    Type <span class="font-mono" x-text="phrase()"></span> to confirm
                                </label>
                                <input
                                    type="text"
                                    name="confirm"
                                    x-model="confirmText"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm font-mono"
                                    :placeholder="phrase()"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-900 text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                                :disabled="!canRun()"
                            >
                                Run
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
