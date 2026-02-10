@extends('layouts.platform')

@section('title', 'Control Center')
@section('header', 'Control Center')

@section('content')
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Control Center</h1>
            <p class="mt-2 text-sm text-gray-700">Quick access to platform modules and safe runbook actions.</p>
        </div>
    </div>

    @if(isset($lastAction) && $lastAction)
        <div class="mb-6 rounded-lg border {{ $lastSuccess ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} p-4 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold {{ $lastSuccess ? 'text-green-900' : 'text-red-900' }}">
                        Last action: {{ $lastAction }}
                    </p>
                    @if(isset($lastOutput) && $lastOutput)
                        <pre class="mt-3 text-xs whitespace-pre-wrap break-words rounded-md bg-white/70 border border-black/5 p-3 max-h-80 overflow-auto">{{ $lastOutput }}</pre>
                    @endif
                </div>
                <a href="{{ route('platform.audit_logs') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                    View audit logs
                </a>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Modules</h2>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
	                <a href="{{ route('platform.tenants') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
	                    <div class="flex items-center justify-between">
	                        <div class="flex items-center gap-3">
	                            <i class="ph ph-globe text-xl text-gray-500 group-hover:text-primary-600"></i>
	                            <div>
	                                <div class="text-sm font-semibold text-gray-900">Sites</div>
	                                <div class="text-xs text-gray-600">Tenants, domains, staging, backups.</div>
	                            </div>
	                        </div>
	                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
	                    </div>
	                </a>

                    <a href="{{ route('platform.domains') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="ph ph-network text-xl text-gray-500 group-hover:text-primary-600"></i>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Domain Center</div>
                                    <div class="text-xs text-gray-600">DNS, SSL, HTTP/3, Nginx actions.</div>
                                </div>
                            </div>
                            <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
                        </div>
                    </a>
	
	                <a href="{{ route('platform.deploy') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
	                    <div class="flex items-center justify-between">
	                        <div class="flex items-center gap-3">
	                            <i class="ph ph-rocket-launch text-xl text-gray-500 group-hover:text-primary-600"></i>
	                            <div>
	                                <div class="text-sm font-semibold text-gray-900">Deploy Center</div>
	                                <div class="text-xs text-gray-600">Deploy, migrate, restart tenants.</div>
	                            </div>
	                        </div>
	                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
	                    </div>
	                </a>

	                <a href="{{ route('platform.services') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
	                    <div class="flex items-center justify-between">
	                        <div class="flex items-center gap-3">
	                            <i class="ph ph-plugs text-xl text-gray-500 group-hover:text-primary-600"></i>
	                            <div>
                                <div class="text-sm font-semibold text-gray-900">Services</div>
                                <div class="text-xs text-gray-600">Start/stop/restart and logs.</div>
                            </div>
                        </div>
                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
                    </div>
                </a>

                <a href="{{ route('platform.queue') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-queue text-xl text-gray-500 group-hover:text-primary-600"></i>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Queue</div>
                                <div class="text-xs text-gray-600">Restart workers, flush failed.</div>
                            </div>
                        </div>
                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
                    </div>
                </a>

                <a href="{{ route('platform.backups') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-hard-drives text-xl text-gray-500 group-hover:text-primary-600"></i>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Backups</div>
                                <div class="text-xs text-gray-600">Platform backups and restore.</div>
                            </div>
                        </div>
                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
                    </div>
                </a>

	                <a href="{{ route('platform.analytics') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
	                    <div class="flex items-center justify-between">
	                        <div class="flex items-center gap-3">
	                            <i class="ph ph-chart-pie-slice text-xl text-gray-500 group-hover:text-primary-600"></i>
	                            <div>
	                                <div class="text-sm font-semibold text-gray-900">Analytics</div>
	                                <div class="text-xs text-gray-600">Traffic and platform stats.</div>
	                            </div>
	                        </div>
	                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
	                    </div>
	                </a>
	
	                <a href="{{ route('platform.monitoring') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
	                    <div class="flex items-center justify-between">
	                        <div class="flex items-center gap-3">
	                            <i class="ph ph-waveform text-xl text-gray-500 group-hover:text-primary-600"></i>
	                            <div>
	                                <div class="text-sm font-semibold text-gray-900">Monitoring</div>
	                                <div class="text-xs text-gray-600">Uptime, SSL expiry, alerts.</div>
	                            </div>
	                        </div>
	                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
	                    </div>
	                </a>

	                <a href="{{ route('platform.system') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
	                    <div class="flex items-center justify-between">
	                        <div class="flex items-center gap-3">
	                            <i class="ph ph-cpu text-xl text-gray-500 group-hover:text-primary-600"></i>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">System</div>
                                <div class="text-xs text-gray-600">DB/Redis health and queue stats.</div>
                            </div>
                        </div>
                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
                    </div>
                </a>

                <a href="{{ route('platform.audit_logs') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-scroll text-xl text-gray-500 group-hover:text-primary-600"></i>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Audit Logs</div>
                                <div class="text-xs text-gray-600">Who changed what, when.</div>
                            </div>
                        </div>
                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
                    </div>
                </a>

                <a href="{{ route('platform.drills') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-siren text-xl text-gray-500 group-hover:text-primary-600"></i>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">DR Drills</div>
                                <div class="text-xs text-gray-600">Recovery runs and history.</div>
                            </div>
                        </div>
                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
                    </div>
                </a>

                <a href="{{ route('platform.themes') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-paint-brush text-xl text-gray-500 group-hover:text-primary-600"></i>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Themes</div>
                                <div class="text-xs text-gray-600">Installed and marketplace.</div>
                            </div>
                        </div>
                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
                    </div>
                </a>

                <a href="{{ route('platform.plugins') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-puzzle-piece text-xl text-gray-500 group-hover:text-primary-600"></i>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Plugins</div>
                                <div class="text-xs text-gray-600">Installed plugins list.</div>
                            </div>
                        </div>
                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
                    </div>
                </a>

                <a href="{{ route('platform.settings') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-gear text-xl text-gray-500 group-hover:text-primary-600"></i>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Settings</div>
                                <div class="text-xs text-gray-600">Platform configuration.</div>
                            </div>
                        </div>
                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
                    </div>
                </a>

                <a href="{{ route('platform.security') }}" class="group rounded-lg border border-gray-200 p-4 hover:border-primary-300 hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-lock-key text-xl text-gray-500 group-hover:text-primary-600"></i>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Security</div>
                                <div class="text-xs text-gray-600">IP allowlist, 2FA, sessions.</div>
                            </div>
                        </div>
                        <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary-600"></i>
                    </div>
                </a>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Runbooks (Allowlisted)</h2>
            </div>
            <div
                class="p-5"
                data-runbook-actions="{{ e(json_encode($actionList ?? [])) }}"
                data-runbook-tenants="{{ e(json_encode($tenants ?? [])) }}"
                data-runbook-domains="{{ e(json_encode($domains ?? [])) }}"
                data-runbook-selected-id="{{ e(old('action_id') ?? $lastActionId ?? '') }}"
                data-runbook-tenant-id="{{ e(old('tenant_id') ?? $lastTenantId ?? '') }}"
                data-runbook-domain-id="{{ e(old('domain_id') ?? $lastDomainId ?? '') }}"
                data-runbook-confirm-text="{{ e(old('confirm') ?? '') }}"
                x-data="{
                    q: '',
                    actions: [],
                    tenants: [],
                    domains: [],
                    selectedId: '',
                    tenantId: '',
                    domainId: '',
                    confirmText: '',
                    init() {
                        try {
                            this.actions = JSON.parse(this.$el.getAttribute('data-runbook-actions') || '[]');
                            this.tenants = JSON.parse(this.$el.getAttribute('data-runbook-tenants') || '[]');
                            this.domains = JSON.parse(this.$el.getAttribute('data-runbook-domains') || '[]');
                        } catch (_) {}
                        this.selectedId = this.$el.getAttribute('data-runbook-selected-id') || '';
                        this.tenantId = this.$el.getAttribute('data-runbook-tenant-id') || '';
                        this.domainId = this.$el.getAttribute('data-runbook-domain-id') || '';
                        this.confirmText = this.$el.getAttribute('data-runbook-confirm-text') || '';
                        if (!this.selectedId && this.actions.length) this.selectedId = this.actions[0].id;
                        if (!this.tenantId) this.tenantId = '';
                        if (!this.domainId) this.domainId = '';
                    },
                    filtered() {
                        const actions = this.actions || [];
                        const q = (this.q || '').toLowerCase().trim();
                        if (!q) return actions;
                        return actions.filter(a => {
                            const hay = (a.id || '') + ' ' + (a.label || '') + ' ' + (a.description || '') + ' ' + (a.category || '');
                            return hay.toLowerCase().includes(q);
                        });
                    },
                    selected() {
                        const actions = this.actions || [];
                        return actions.find(a => String(a.id) === String(this.selectedId)) || actions[0] || null;
                    },
                    select(id) {
                        this.selectedId = id;
                        this.confirmText = '';
                    },
                    needs(kind) {
                        const s = this.selected();
                        if (!s) return false;
                        return (s.requires || []).includes(kind);
                    },
                    confirmPhrase() {
                        const s = this.selected();
                        return s && s.confirm ? String(s.confirm) : '';
                    },
                    needsConfirm() {
                        return this.confirmPhrase() !== '';
                    },
                    filteredDomains() {
                        const tenantId = String(this.tenantId || '');
                        if (!tenantId) return this.domains || [];
                        return (this.domains || []).filter(d => String(d.tenant_id) === tenantId);
                    },
                    domainLabel(d) {
                        const host = d.hostname || 'domain#' + (d.id || '');
                        const env = d.environment ? ' (' + d.environment + ')' : '';
                        const tenant = d.tenant && d.tenant.name ? ' - ' + d.tenant.name : '';
                        return host + env + tenant;
                    },
                    syncTenantFromDomain() {
                        if (!this.domainId) return;
                        const d = (this.domains || []).find(x => String(x.id) === String(this.domainId));
                        if (d && d.tenant_id && !this.tenantId) {
                            this.tenantId = String(d.tenant_id);
                        }
                    },
                }"
                @runbook-quick.window="
                    const d = $event.detail || {};
                    if (d.actionId && typeof select === 'function') select(d.actionId);
                    if (d.tenantId !== undefined && d.tenantId !== null) { tenantId = String(d.tenantId); domainId = ''; }
                    if (d.domainId !== undefined && d.domainId !== null) { domainId = String(d.domainId); if (typeof syncTenantFromDomain === 'function') syncTenantFromDomain(); }
                    confirmText = '';
                    $nextTick(() => {
                        if ($refs.runner && $refs.runner.scrollIntoView) $refs.runner.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        if (typeof needsConfirm === 'function' && needsConfirm() && $refs.confirm) $refs.confirm.focus();
                    });
                "
            >
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div class="text-sm text-gray-700">
                        <span class="font-semibold text-gray-900" x-text="(selected() && selected().label) ? selected().label : 'Select an action'"></span>
                        <span class="ml-2 text-xs text-gray-500" x-text="(selected() && selected().category) ? selected().category : ''"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400"></i>
                            <input
                                type="text"
                                x-model="q"
                                class="w-72 pl-9 rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm"
                                placeholder="Search actions..."
                                autocomplete="off"
                            />
                        </div>
                        <span class="text-xs text-gray-500" x-text="filtered().length + ' actions'"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Actions list -->
                    <div class="rounded-xl border border-gray-200 overflow-hidden">
                        <div class="max-h-[520px] overflow-auto divide-y divide-gray-100">
                            <template x-for="item in filtered()" :key="item.id">
                                <button
                                    type="button"
                                    class="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-start justify-between gap-4"
                                    :class="String(selectedId) === String(item.id) ? 'bg-gray-50' : ''"
                                    @click="select(item.id)"
                                >
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900" x-text="item.label"></div>
                                        <div class="mt-1 text-xs text-gray-600" x-text="item.description"></div>
                                        <div class="mt-2 text-[11px] text-gray-500">ID: <span class="font-mono" x-text="item.id"></span></div>
                                    </div>
                                    <span class="shrink-0 inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700" x-text="item.category"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Runner -->
                    <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4" x-ref="runner">
                        <form method="POST" action="{{ route('platform.control.run') }}">
                            @csrf
                            <input type="hidden" name="action_id" :value="selectedId">

                            <div class="mb-3">
                                <div class="text-sm font-semibold text-gray-900">Runner</div>
                                <div class="mt-1 text-xs text-gray-600" x-text="selected() ? selected().description : ''"></div>
                            </div>

                            <div class="grid grid-cols-1 gap-3">
                                <div x-show="needs('tenant') || needs('domain')">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                        Tenant
                                        <span class="text-red-600" x-show="needs('tenant')">*</span>
                                    </label>
                                    <select
                                        name="tenant_id"
                                        x-model="tenantId"
                                        class="w-full rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm"
                                        @change="domainId = ''"
                                    >
                                        <option value="">Select tenant...</option>
                                        <template x-for="t in tenants" :key="t.id">
                                            <option :value="t.id" x-text="`${t.name} (#${t.id})`"></option>
                                        </template>
                                    </select>
                                </div>

                                <div x-show="needs('domain')">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                        Domain <span class="text-red-600">*</span>
                                    </label>
                                    <select
                                        name="domain_id"
                                        x-model="domainId"
                                        class="w-full rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm"
                                        @change="syncTenantFromDomain()"
                                    >
                                        <option value="">Select domain...</option>
                                        <template x-for="d in filteredDomains()" :key="d.id">
                                            <option :value="d.id" x-text="domainLabel(d)"></option>
                                        </template>
                                    </select>
                                </div>

                                <div x-show="needsConfirm()">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                        Confirm by typing <span class="font-mono" x-text="confirmPhrase()"></span>
                                    </label>
                                    <input
                                        type="text"
                                        name="confirm"
                                        x-model="confirmText"
                                        x-ref="confirm"
                                        class="w-full rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm font-mono"
                                        :placeholder="confirmPhrase()"
                                        autocomplete="off"
                                    />
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <div class="text-xs text-gray-600">
                                    <span x-show="needs('tenant')">Requires tenant.</span>
                                    <span x-show="needs('domain')">Requires domain.</span>
                                    <span x-show="needsConfirm()" class="ml-2">Confirmation required.</span>
                                </div>
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed"
                                    :disabled="(needs('tenant') && !tenantId) || (needs('domain') && !domainId) || (needsConfirm() && confirmText !== confirmPhrase())"
                                >
                                    <i class="ph ph-play mr-2"></i>
                                    Run
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        class="mt-6 bg-white shadow rounded-lg overflow-hidden"
        data-env-base="{{ e(url('/platform/tenants')) }}"
        data-env-tenants="{{ e(json_encode($tenants ?? [])) }}"
        data-env-domains="{{ e(json_encode($domains ?? [])) }}"
        x-data="{
            q: '',
            base: '',
            tenants: [],
            domains: [],
            init() {
                this.base = this.$el.getAttribute('data-env-base') || '';
                try {
                    this.tenants = JSON.parse(this.$el.getAttribute('data-env-tenants') || '[]');
                    this.domains = JSON.parse(this.$el.getAttribute('data-env-domains') || '[]');
                } catch (_) {}
            },
            norm(v) { return String(v || '').toLowerCase(); },
            envOf(d) {
                const e = this.norm(d && d.environment);
                return e !== '' ? e : 'production';
            },
            tenantDomains(tenantId) {
                const id = String(tenantId || '');
                return (this.domains || []).filter(d => String(d.tenant_id) === id);
            },
            prodPrimary(tenantId) {
                const ds = this.tenantDomains(tenantId);
                const prod = ds.filter(d => this.envOf(d) === 'production');
                if (!prod.length) return null;
                return prod.find(d => !!d.is_primary) || prod[0] || null;
            },
            hostsFor(tenantId, env) {
                const want = this.norm(env);
                return this.tenantDomains(tenantId)
                    .filter(d => this.envOf(d) === want)
                    .map(d => d.hostname)
                    .filter(Boolean);
            },
            compact(list, max = 2) {
                const arr = Array.isArray(list) ? list : [];
                if (arr.length <= max) return arr;
                const rest = arr.length - max;
                return arr.slice(0, max).concat([`+${rest} more`]);
            },
            pillClass(state) {
                const s = this.norm(state);
                if (s === 'ready') return 'bg-green-100 text-green-800';
                if (s === 'provisioning') return 'bg-blue-100 text-blue-800';
                if (s === 'error') return 'bg-red-100 text-red-800';
                if (s === 'pending') return 'bg-gray-100 text-gray-800';
                return 'bg-gray-100 text-gray-800';
            },
            tenantUrl(id) { return `${this.base}/${id}`; },
            stagingUrl(id) { return `${this.base}/${id}/staging`; },
            previewUrl(id) { return `${this.base}/${id}/preview`; },
            haystack(t) {
                const prod = this.prodPrimary(t.id);
                const prodHost = prod && prod.hostname ? prod.hostname : '';
                const stagingHosts = this.hostsFor(t.id, 'staging').join(' ');
                const previewHosts = this.hostsFor(t.id, 'preview').join(' ');
                return `${t.id} ${t.name || ''} ${t.slug || ''} ${prodHost} ${stagingHosts} ${previewHosts}`.toLowerCase();
            },
            filteredTenants() {
                const q = this.norm(this.q).trim();
                const ts = this.tenants || [];
                if (!q) return ts;
                return ts.filter(t => this.haystack(t).includes(q));
            },
        }"
    >
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-4">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Environments</h2>
            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400"></i>
                    <input
                        type="text"
                        x-model="q"
                        class="w-72 pl-9 rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm"
                        placeholder="Search tenants or domains..."
                        autocomplete="off"
                    />
                </div>
                <span class="text-xs text-gray-500" x-text="filteredTenants().length + ' tenants'"></span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
	                    <tr>
	                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tenant</th>
	                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Instance</th>
	                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Staging</th>
	                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Preview</th>
	                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Domains</th>
	                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
	                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Open</th>
	                    </tr>
	                </thead>
	                <tbody class="bg-white divide-y divide-gray-200">
	                    <template x-if="filteredTenants().length === 0">
	                        <tr>
	                            <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-600">No tenants match your search.</td>
	                        </tr>
	                    </template>

                    <template x-for="t in filteredTenants()" :key="t.id">
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-4">
                                <a :href="tenantUrl(t.id)" class="text-sm font-semibold text-gray-900 hover:text-primary-700" x-text="t.name || ('Tenant #' + t.id)"></a>
                                <div class="mt-1 text-xs text-gray-500">
                                    <span class="font-mono" x-text="'#' + t.id"></span>
                                    <span x-show="t.slug" class="ml-2" x-text="'(' + t.slug + ')'"></span>
                                </div>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="pillClass(t.instance_status)" x-text="t.instance_status || 'unknown'"></span>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="t.staging_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                        x-text="t.staging_enabled ? 'enabled' : 'disabled'"
                                    ></span>
                                    <span
                                        x-show="t.staging_enabled && hostsFor(t.id, 'staging').length === 0"
                                        class="px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800"
                                    >no domain</span>
                                </div>
                                <div class="mt-1 text-xs text-gray-500" x-text="hostsFor(t.id, 'staging').length + ' domains'"></div>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="t.preview_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                        x-text="t.preview_enabled ? 'enabled' : 'disabled'"
                                    ></span>
                                    <span
                                        x-show="t.preview_enabled && hostsFor(t.id, 'preview').length === 0"
                                        class="px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800"
                                    >no domain</span>
                                </div>
                                <div class="mt-1 text-xs text-gray-500" x-text="hostsFor(t.id, 'preview').length + ' domains'"></div>
                            </td>

                            <td class="px-5 py-4">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Prod</span>
                                        <template x-if="prodPrimary(t.id) && prodPrimary(t.id).hostname">
                                            <a :href="'//' + prodPrimary(t.id).hostname" target="_blank" class="font-mono text-xs text-primary-700 hover:underline" x-text="prodPrimary(t.id).hostname"></a>
                                        </template>
                                        <template x-if="!(prodPrimary(t.id) && prodPrimary(t.id).hostname)">
                                            <span class="text-xs text-gray-400">—</span>
                                        </template>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Staging</span>
                                        <template x-if="hostsFor(t.id, 'staging').length === 0">
                                            <span class="text-xs text-gray-400">—</span>
                                        </template>
                                        <template x-for="h in compact(hostsFor(t.id, 'staging'))" :key="h">
                                            <span class="font-mono text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700" x-text="h"></span>
                                        </template>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Preview</span>
                                        <template x-if="hostsFor(t.id, 'preview').length === 0">
                                            <span class="text-xs text-gray-400">—</span>
                                        </template>
                                        <template x-for="h in compact(hostsFor(t.id, 'preview'))" :key="h">
                                            <span class="font-mono text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700" x-text="h"></span>
                                        </template>
                                    </div>
                                </div>
                            </td>

	                            <td class="px-5 py-4 whitespace-nowrap text-right">
	                                <div class="relative inline-block text-left" x-data="{ open: false }">
	                                    <button
	                                        type="button"
	                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-md border border-gray-200 text-xs font-semibold text-gray-900 bg-white hover:bg-gray-50"
	                                        @click="open = !open"
	                                    >
	                                        <i class="ph ph-lightning text-gray-600"></i>
	                                        Actions
	                                        <i class="ph ph-caret-down text-gray-400"></i>
	                                    </button>
	
	                                    <div
	                                        x-show="open"
	                                        x-transition
	                                        @click.outside="open = false"
	                                        style="display: none;"
	                                        class="absolute right-0 mt-2 w-72 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black/5 z-20"
	                                    >
	                                        <div class="py-2">
	                                            <div class="px-3 py-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Staging</div>
	                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
	                                                @click="open = false; $dispatch('runbook-quick', { actionId: 'tenant_staging_enable', tenantId: t.id })">
	                                                Enable
	                                            </button>
	                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
	                                                @click="open = false; $dispatch('runbook-quick', { actionId: 'tenant_staging_disable', tenantId: t.id })">
	                                                Disable
	                                            </button>
	                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
	                                                @click="open = false; $dispatch('runbook-quick', { actionId: 'tenant_staging_sync_prod_to_staging', tenantId: t.id })">
	                                                Sync prod -> staging
	                                            </button>
	                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
	                                                @click="open = false; $dispatch('runbook-quick', { actionId: 'tenant_staging_promote_to_prod', tenantId: t.id })">
	                                                Promote staging -> prod
	                                            </button>
	
	                                            <div class="px-3 pt-3 pb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Preview</div>
	                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
	                                                @click="open = false; $dispatch('runbook-quick', { actionId: 'tenant_preview_enable', tenantId: t.id })">
	                                                Enable
	                                            </button>
	                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
	                                                @click="open = false; $dispatch('runbook-quick', { actionId: 'tenant_preview_disable', tenantId: t.id })">
	                                                Disable
	                                            </button>
	                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
	                                                @click="open = false; $dispatch('runbook-quick', { actionId: 'tenant_preview_sync_prod_to_preview', tenantId: t.id })">
	                                                Sync prod -> preview
	                                            </button>
	                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
	                                                @click="open = false; $dispatch('runbook-quick', { actionId: 'tenant_preview_promote_to_prod', tenantId: t.id })">
	                                                Promote preview -> prod
	                                            </button>
	
		                                            <div class="px-3 pt-3 pb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Secrets</div>
		                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
		                                                @click="open = false; $dispatch('runbook-quick', { actionId: 'tenant_secrets_sync_all_to_env', tenantId: t.id })">
		                                                Sync all secrets -> .env
		                                            </button>

		                                            <div class="px-3 pt-3 pb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Deploy</div>
		                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
		                                                @click="open = false; $dispatch('runbook-quick', { actionId: 'tenant_deploy_full', tenantId: t.id })">
		                                                Full deploy
		                                            </button>
		                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
		                                                @click="open = false; $dispatch('runbook-quick', { actionId: 'tenant_migrate', tenantId: t.id })">
		                                                Migrate
		                                            </button>
		                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
		                                                @click="open = false; $dispatch('runbook-quick', { actionId: 'tenant_orchestrate_restart', tenantId: t.id })">
		                                                Restart runtime
		                                            </button>
		                                        </div>
		                                    </div>
		                                </div>
		                            </td>
	
	                            <td class="px-5 py-4 whitespace-nowrap text-right">
	                                <div class="inline-flex items-center gap-2">
	                                    <a :href="tenantUrl(t.id)" class="text-xs font-semibold text-gray-700 hover:text-gray-900 underline">Site</a>
	                                    <a :href="stagingUrl(t.id)" class="text-xs font-semibold text-gray-700 hover:text-gray-900 underline">Staging</a>
	                                    <a :href="previewUrl(t.id)" class="text-xs font-semibold text-gray-700 hover:text-gray-900 underline">Preview</a>
	                                </div>
	                            </td>
	                        </tr>
	                    </template>
	                </tbody>
            </table>
	        </div>
	    </div>

	    <div class="mt-6 bg-white shadow rounded-lg overflow-hidden">
	        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-4">
	            <div>
	                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Monitoring Snapshot</h2>
	                <div class="mt-1 text-xs text-gray-600">Quick health view for uptime, SSL, backups, and HTTP/3.</div>
	            </div>
	            <div class="hidden sm:inline-flex items-center gap-2">
	                <button type="button"
	                    class="inline-flex items-center px-3 py-2 rounded-md border border-gray-200 text-xs font-semibold text-gray-900 bg-white hover:bg-gray-50"
	                    @click="$dispatch('runbook-quick', { actionId: 'uptime_run' })"
	                >
	                    <i class="ph ph-waveform mr-2 text-gray-600"></i> Run uptime
	                </button>
	                <button type="button"
	                    class="inline-flex items-center px-3 py-2 rounded-md border border-gray-200 text-xs font-semibold text-gray-900 bg-white hover:bg-gray-50"
	                    @click="$dispatch('runbook-quick', { actionId: 'alerts_dispatch' })"
	                >
	                    <i class="ph ph-bell-ringing mr-2 text-gray-600"></i> Dispatch alerts
	                </button>
	                <button type="button"
	                    class="inline-flex items-center px-3 py-2 rounded-md border border-gray-200 text-xs font-semibold text-gray-900 bg-white hover:bg-gray-50"
	                    @click="$dispatch('runbook-quick', { actionId: 'integrity_check_latest' })"
	                >
	                    <i class="ph ph-shield-check mr-2 text-gray-600"></i> Integrity check
	                </button>
	            </div>
	        </div>
	        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
	            <div class="rounded-lg border border-gray-200 p-4">
	                <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Uptime</div>
	                <div class="mt-2 text-2xl font-bold text-gray-900">{{ $monitoring['uptime_active'] ?? 0 }}</div>
	                <div class="mt-1 text-xs text-gray-600">Active checks</div>
	            </div>
	            <div class="rounded-lg border border-gray-200 p-4">
	                <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">SSL</div>
	                <div class="mt-2 text-2xl font-bold text-gray-900">{{ $monitoring['ssl_expiring_soon'] ?? 0 }}</div>
	                <div class="mt-1 text-xs text-gray-600">Expiring in {{ $monitoring['ssl_days'] ?? 14 }} days</div>
	            </div>
	            <div class="rounded-lg border border-gray-200 p-4">
	                <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Backups</div>
	                <div class="mt-2 text-2xl font-bold text-gray-900">{{ $monitoring['backup_failures_24h'] ?? 0 }}</div>
	                <div class="mt-1 text-xs text-gray-600">Failures (24h)</div>
	            </div>
	            <div class="rounded-lg border border-gray-200 p-4">
	                <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">HTTP/3</div>
	                <div class="mt-2 text-2xl font-bold text-gray-900">{{ $monitoring['http3_issues'] ?? 0 }}</div>
	                <div class="mt-1 text-xs text-gray-600">Domains with issues</div>
	            </div>
	        </div>
	    </div>

	    <div
	        class="mt-6 bg-white shadow rounded-lg overflow-hidden"
	        data-bulk-action-id="{{ e(old('action_id') ?? '') }}"
	        data-bulk-confirm="{{ e(old('confirm') ?? '') }}"
	        data-bulk-actions="{{ e(json_encode($bulkActionList ?? [])) }}"
	        data-bulk-tenants="{{ e(json_encode($tenants ?? [])) }}"
	        x-data="{
	            q: '',
	            max: 50,
	            limitHit: false,
	            actionId: '',
	            confirmText: '',
	            actions: [],
	            tenants: [],
	            selected: [],
	            init() {
	                this.actionId = this.$el.getAttribute('data-bulk-action-id') || '';
	                this.confirmText = this.$el.getAttribute('data-bulk-confirm') || '';
	                try {
	                    this.actions = JSON.parse(this.$el.getAttribute('data-bulk-actions') || '[]');
	                    this.tenants = JSON.parse(this.$el.getAttribute('data-bulk-tenants') || '[]');
	                } catch (_) {}
	                if (!this.actionId && this.actions.length) this.actionId = this.actions[0].id;
	            },
	            meta() {
	                return (this.actions || []).find(a => String(a.id) === String(this.actionId)) || null;
	            },
	            phrase() {
	                const m = this.meta();
	                return m && m.confirm ? String(m.confirm) : '';
	            },
	            needsConfirm() {
	                return this.phrase() !== '';
	            },
	            norm(v) { return String(v || '').toLowerCase(); },
	            haystack(t) {
	                return `${t.id} ${t.name || ''} ${t.slug || ''}`.toLowerCase();
	            },
	            filteredTenants() {
	                const q = this.norm(this.q).trim();
	                const ts = this.tenants || [];
	                if (!q) return ts;
	                return ts.filter(t => this.haystack(t).includes(q));
	            },
	            isSelected(id) {
	                return (this.selected || []).includes(String(id));
	            },
	            toggleTenant(id) {
	                const key = String(id);
	                if (this.isSelected(key)) {
	                    this.selected = (this.selected || []).filter(x => x !== key);
	                    return;
	                }
	                if ((this.selected || []).length >= this.max) {
	                    this.limitHit = true;
	                    setTimeout(() => this.limitHit = false, 1800);
	                    return;
	                }
	                this.selected = (this.selected || []).concat([key]);
	            },
	            toggleAllVisible() {
	                const visible = this.filteredTenants().map(t => String(t.id));
	                const allSelected = visible.every(id => this.isSelected(id));
	                if (allSelected) {
	                    this.selected = (this.selected || []).filter(id => !visible.includes(id));
	                    return;
	                }
	                const next = (this.selected || []).slice();
	                for (const id of visible) {
	                    if (next.includes(id)) continue;
	                    if (next.length >= this.max) break;
	                    next.push(id);
	                }
	                this.selected = next;
	            },
	        }"
	        x-init="init()"
	    >
	        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-4">
	            <div>
	                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Bulk Ops</h2>
	                <div class="mt-1 text-xs text-gray-600">Run a safe action across multiple tenants (max 50).</div>
	            </div>
	            <div class="text-xs text-gray-500">
	                Selected: <span class="font-semibold text-gray-900" x-text="(selected || []).length"></span>
	            </div>
	        </div>
	
	        <div class="p-5 grid grid-cols-1 lg:grid-cols-3 gap-6">
	            <form method="POST" action="{{ route('platform.control.bulk-run') }}" class="lg:col-span-1 rounded-xl border border-gray-200 bg-gray-50/50 p-4">
	                @csrf
	                <div class="text-sm font-semibold text-gray-900">Bulk Runner</div>
	                <div class="mt-1 text-xs text-gray-600">Uses the same allowlisted runbooks and audit trail.</div>
	
	                <div class="mt-4 space-y-3">
	                    <div>
	                        <label class="block text-xs font-semibold text-gray-700 mb-1">Action</label>
	                        <select
	                            name="action_id"
	                            x-model="actionId"
	                            class="w-full rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm"
	                            @change="confirmText = ''"
	                        >
	                            <template x-for="a in actions" :key="a.id">
	                                <option :value="a.id" x-text="a.label"></option>
	                            </template>
	                        </select>
	                        <div class="mt-2 text-xs text-gray-600" x-text="meta() ? meta().description : ''"></div>
	                    </div>
	
	                    <div x-show="needsConfirm()">
	                        <label class="block text-xs font-semibold text-gray-700 mb-1">
	                            Confirm by typing <span class="font-mono" x-text="phrase()"></span>
	                        </label>
	                        <input
	                            type="text"
	                            name="confirm"
	                            x-model="confirmText"
	                            class="w-full rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm font-mono"
	                            :placeholder="phrase()"
	                            autocomplete="off"
	                        />
	                    </div>
	
	                    <template x-for="id in selected" :key="id">
	                        <input type="hidden" name="tenant_ids[]" :value="id" />
	                    </template>
	
	                    <div class="pt-2">
	                        <button
	                            type="submit"
	                            class="w-full inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed"
	                            :disabled="(selected || []).length === 0 || (needsConfirm() && confirmText !== phrase())"
	                        >
	                            <i class="ph ph-play mr-2"></i>
	                            Run Bulk
	                        </button>
	                        <div x-show="limitHit" class="mt-2 text-xs font-semibold text-amber-700">Max 50 tenants per bulk run.</div>
	                    </div>
	                </div>
	            </form>
	
	            <div class="lg:col-span-2">
	                <div class="flex items-center justify-between gap-4 mb-3">
	                    <div class="relative w-full">
	                        <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400"></i>
	                        <input
	                            type="text"
	                            x-model="q"
	                            class="w-full pl-9 rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm"
	                            placeholder="Filter tenants..."
	                            autocomplete="off"
	                        />
	                    </div>
	                    <button
	                        type="button"
	                        class="shrink-0 inline-flex items-center px-3 py-2 rounded-md border border-gray-200 text-xs font-semibold text-gray-900 bg-white hover:bg-gray-50"
	                        @click="toggleAllVisible()"
	                    >
	                        <i class="ph ph-checks mr-2 text-gray-600"></i>
	                        Toggle all
	                    </button>
	                </div>
	
	                <div class="rounded-xl border border-gray-200 overflow-hidden">
	                    <div class="max-h-[520px] overflow-auto divide-y divide-gray-100 bg-white">
	                        <template x-for="t in filteredTenants()" :key="t.id">
	                            <label class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-gray-50 cursor-pointer">
	                                <div class="flex items-center gap-3 min-w-0">
	                                    <input
	                                        type="checkbox"
	                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
	                                        :checked="isSelected(t.id)"
	                                        @change="toggleTenant(t.id)"
	                                    />
	                                    <div class="min-w-0">
	                                        <div class="text-sm font-semibold text-gray-900 truncate" x-text="t.name || ('Tenant #' + t.id)"></div>
	                                        <div class="mt-0.5 text-xs text-gray-500 truncate">
	                                            <span class="font-mono" x-text="'#' + t.id"></span>
	                                            <span x-show="t.slug" class="ml-2" x-text="'(' + t.slug + ')'"></span>
	                                        </div>
	                                    </div>
	                                </div>
	                                <span class="shrink-0 px-2 py-1 rounded-full text-xs font-semibold"
	                                    :class="String((t.instance_status || '')).toLowerCase() === 'ready' ? 'bg-green-100 text-green-800' : (String((t.instance_status || '')).toLowerCase() === 'error' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')"
	                                    x-text="t.instance_status || 'unknown'"></span>
	                            </label>
	                        </template>
	                    </div>
	                </div>
	            </div>
	        </div>
	    </div>

	    <div class="mt-6 bg-white shadow rounded-lg overflow-hidden">
	        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
	            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Service Status (Allowlisted)</h2>
	        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach(($services ?? []) as $svc)
                    @php
                        $state = $svc['state'] ?? 'unknown';
                        $pill = match ($state) {
                            'active' => 'bg-green-100 text-green-800',
                            'inactive' => 'bg-gray-100 text-gray-800',
                            'failed' => 'bg-red-100 text-red-800',
                            'missing' => 'bg-yellow-100 text-yellow-800',
                            default => 'bg-blue-100 text-blue-800',
                        };
                    @endphp
                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">{{ $svc['label'] ?? $svc['key'] ?? 'Service' }}</div>
                                <div class="mt-1 text-xs text-gray-600 font-mono">{{ $svc['unit'] ?? '' }}</div>
                            </div>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $pill }}">{{ $state }}</span>
                        </div>
                        @if(!empty($svc['detail']))
                            <div class="mt-2 text-xs text-gray-600">{{ $svc['detail'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Recent Activity</h2>
                <a href="{{ route('platform.audit_logs') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">All</a>
            </div>
            <div class="p-4">
                <div class="space-y-3">
                    @forelse(($recentAudit ?? []) as $log)
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-xs font-semibold text-gray-900 truncate">{{ $log->action }}</div>
                                <div class="mt-0.5 text-xs text-gray-600 truncate">{{ $log->description ?? '-' }}</div>
                                <div class="mt-1 text-[11px] text-gray-500">
                                    {{ optional($log->created_at)->format('Y-m-d H:i') }}
                                    · {{ $log->user->name ?? 'System' }}
                                </div>
                            </div>
                            <span class="shrink-0 px-2 py-1 rounded-full text-[11px] font-semibold {{ ($log->status ?? 'success') === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $log->status ?? 'success' }}
                            </span>
                        </div>
                    @empty
                        <div class="text-sm text-gray-600">No activity yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Security Scans</h2>
                <span class="text-xs text-gray-500">Last 20</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Started</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse(($securityScans ?? []) as $scan)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $scan->type }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ ($scan->status ?? '') === 'completed' ? 'bg-green-100 text-green-800' : (($scan->status ?? '') === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ $scan->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-right">{{ optional($scan->started_at)->format('m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-600">No scans yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Firewall Rules</h2>
                <span class="text-xs text-gray-500">Last 50</span>
            </div>

            <div class="p-4 border-b border-gray-200 bg-white">
                <form method="POST" action="{{ route('platform.control.firewall.store') }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3 items-end">
                    @csrf
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Action</label>
                        <select name="action" class="w-full rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm">
                            <option value="allow">allow</option>
                            <option value="deny">deny</option>
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Protocol</label>
                        <select name="protocol" class="w-full rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm">
                            <option value="tcp">tcp</option>
                            <option value="udp">udp</option>
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Port</label>
                        <input name="port" type="text" class="w-full rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm" placeholder="80 or 443" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Source (optional)</label>
                        <input name="source" type="text" class="w-full rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm font-mono" placeholder="1.2.3.4/32" />
                    </div>
                    <div class="sm:col-span-1 flex items-center gap-2">
                        <input id="fw_active" name="is_active" value="1" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" checked />
                        <label for="fw_active" class="text-sm text-gray-700">Active</label>
                    </div>

                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Description (optional)</label>
                        <input name="description" type="text" class="w-full rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm" placeholder="e.g. Allow SSH from office IP" />
                    </div>
                    <div class="sm:col-span-1">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800">
                            <i class="ph ph-plus mr-2"></i>
                            Add
                        </button>
                    </div>
                </form>
                <div class="mt-3 text-[11px] text-gray-500">
                    To apply rules on the server, run the <span class="font-mono">firewall_apply</span> runbook action.
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rule</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Active</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Applied</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Manage</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse(($firewallRules ?? []) as $rule)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <span class="font-semibold">{{ strtoupper($rule->action) }}</span>
                                    <span class="text-gray-600">{{ strtolower($rule->protocol) }}/{{ $rule->port }}</span>
                                    @if($rule->source)
                                        <span class="text-gray-500">from</span>
                                        <span class="text-gray-700 font-mono text-xs">{{ $rule->source }}</span>
                                    @endif
                                    @if($rule->description)
                                        <div class="text-xs text-gray-500 mt-1">{{ $rule->description }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $rule->is_active ? 'yes' : 'no' }}
                                        </span>
                                        <form method="POST" action="{{ route('platform.control.firewall.toggle', $rule->id) }}">
                                            @csrf
                                            <input type="hidden" name="is_active" value="{{ $rule->is_active ? 0 : 1 }}">
                                            <button type="submit" class="text-xs font-semibold text-gray-700 hover:text-gray-900 underline">
                                                {{ $rule->is_active ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-right">
                                    {{ optional($rule->applied_at)->format('m-d H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <form method="POST" action="{{ route('platform.control.firewall.destroy', $rule->id) }}" onsubmit="return confirm('Delete this firewall rule?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center px-2 py-1 rounded-md text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100">
                                            <i class="ph ph-trash mr-1"></i>
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-600">No firewall rules yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
