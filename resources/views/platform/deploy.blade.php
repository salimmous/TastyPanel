@extends('layouts.platform')

@section('title', 'Deploy Center')
@section('header', 'Deploy Center')

@section('content')
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Deploy Center</h1>
            <p class="mt-2 text-sm text-gray-700">Deploy workflows, migrations, and runtime actions for tenants.</p>
        </div>
    </div>

    @if(isset($lastAction) && $lastAction)
        <div class="mb-6 rounded-lg border {{ $lastSuccess ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} p-4 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-semibold {{ $lastSuccess ? 'text-green-900' : 'text-red-900' }}">
                        Last action: {{ $lastAction }}
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

    <div
        class="bg-white shadow rounded-lg overflow-hidden"
        x-data="{
            q: '',
            open: false,
            actionId: '',
            tenantId: '',
            phrase: '',
            confirmText: '',
            openModal(actionId, tenantId, phrase = '') {
                this.actionId = String(actionId || '');
                this.tenantId = String(tenantId || '');
                this.phrase = String(phrase || '');
                this.confirmText = '';
                this.open = true;
            },
            needsConfirm() { return (this.phrase || '') !== ''; },
            canRun() { return !this.needsConfirm() || this.confirmText === this.phrase; },
            norm(v) { return String(v || '').toLowerCase(); },
            match(hay) {
                const q = this.norm(this.q).trim();
                if (!q) return true;
                return this.norm(hay).includes(q);
            },
        }"
    >
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-4">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Tenants</h2>
            <div class="relative">
                <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400"></i>
                <input
                    type="text"
                    x-model="q"
                    class="w-80 pl-9 rounded-md border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm"
                    placeholder="Search tenants..."
                    autocomplete="off"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tenant</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Instance</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Open</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse(($tenants ?? []) as $tenant)
                        @php
                            $hay = strtolower(trim($tenant->id . ' ' . ($tenant->name ?? '') . ' ' . ($tenant->slug ?? '') . ' ' . ($tenant->instance_status ?? '')));
                        @endphp
                        <tr x-show="match(@json($hay))" class="hover:bg-gray-50/60">
                            <td class="px-5 py-4">
                                <div class="text-sm font-semibold text-gray-900">{{ $tenant->name ?? ('Tenant #' . $tenant->id) }}</div>
                                <div class="mt-1 text-xs text-gray-500">
                                    <span class="font-mono">#{{ $tenant->id }}</span>
                                    @if($tenant->slug)
                                        <span class="ml-2">({{ $tenant->slug }})</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                @php
                                    $state = strtolower((string) ($tenant->instance_status ?? 'unknown'));
                                    $pill = match ($state) {
                                        'ready' => 'bg-green-100 text-green-800',
                                        'provisioning' => 'bg-blue-100 text-blue-800',
                                        'error' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $pill }}">{{ $tenant->instance_status ?? 'unknown' }}</span>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                <div class="relative inline-block text-left" x-data="{ openRow: false }">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-md border border-gray-200 text-xs font-semibold text-gray-900 bg-white hover:bg-gray-50"
                                        @click="openRow = !openRow"
                                    >
                                        <i class="ph ph-command text-gray-600"></i>
                                        Run
                                        <i class="ph ph-caret-down text-gray-400"></i>
                                    </button>

                                    <div
                                        x-show="openRow"
                                        x-transition
                                        @click.outside="openRow = false"
                                        style="display: none;"
                                        class="absolute right-0 mt-2 w-72 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black/5 z-20"
                                    >
                                        <div class="py-2">
                                            <div class="px-3 py-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Deploy</div>
                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                                                @click="openRow = false; openModal('tenant_deploy_full', {{ $tenant->id }}, 'tenant_deploy_full')">
                                                Full deploy
                                            </button>
                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                                                @click="openRow = false; openModal('tenant_deploy_git_pull', {{ $tenant->id }}, 'tenant_deploy_git_pull')">
                                                Git pull
                                            </button>
                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                                                @click="openRow = false; openModal('tenant_deploy_composer_install', {{ $tenant->id }}, 'tenant_deploy_composer_install')">
                                                Composer install
                                            </button>

                                            <div class="px-3 pt-3 pb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Laravel</div>
                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                                                @click="openRow = false; openModal('tenant_migrate', {{ $tenant->id }}, 'tenant_migrate')">
                                                Run migrations
                                            </button>
                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                                                @click="openRow = false; openModal('tenant_optimize_clear', {{ $tenant->id }})">
                                                Clear caches
                                            </button>

                                            <div class="px-3 pt-3 pb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Runtime</div>
                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                                                @click="openRow = false; openModal('tenant_orchestrate_restart', {{ $tenant->id }})">
                                                Restart
                                            </button>
                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                                                @click="openRow = false; openModal('tenant_orchestrate_stop', {{ $tenant->id }}, 'tenant_orchestrate_stop')">
                                                Stop (maintenance)
                                            </button>
                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                                                @click="openRow = false; openModal('tenant_orchestrate_start', {{ $tenant->id }})">
                                                Start (exit maintenance)
                                            </button>

                                            <div class="px-3 pt-3 pb-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Secrets</div>
                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                                                @click="openRow = false; openModal('tenant_secrets_sync_all_to_env', {{ $tenant->id }}, 'tenant_secrets_sync_all_to_env')">
                                                Sync secrets -> .env
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('platform.tenants.show', $tenant->id) }}" class="text-xs font-semibold text-gray-700 hover:text-gray-900 underline">Tenant</a>
                                    <a href="{{ route('platform.tenants.staging', $tenant->id) }}" class="text-xs font-semibold text-gray-700 hover:text-gray-900 underline">Staging</a>
                                    <a href="{{ route('platform.tenants.preview', $tenant->id) }}" class="text-xs font-semibold text-gray-700 hover:text-gray-900 underline">Preview</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-600">No tenants found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Confirm + Run Modal -->
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
                        <input type="hidden" name="action_id" :value="actionId">
                        <input type="hidden" name="tenant_id" :value="tenantId">

                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2" id="modal-title">Run action</h3>
                            <p class="text-sm text-gray-600">
                                This will run an allowlisted runbook action for the selected tenant and will be recorded in audit logs.
                            </p>

                            <div class="mt-4">
                                <div class="text-xs text-gray-600">Action ID</div>
                                <div class="mt-1 font-mono text-sm text-gray-900" x-text="actionId"></div>
                            </div>

                            <div class="mt-4" x-show="needsConfirm()">
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

    <div class="mt-6 bg-white shadow rounded-lg overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Recent Deploy Activity</h2>
            <a href="{{ route('platform.audit_logs') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">All</a>
        </div>
        <div class="p-4">
            <div class="space-y-3">
                @forelse(($recentDeploy ?? []) as $log)
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-xs font-semibold text-gray-900 truncate">{{ $log->description ?? $log->action }}</div>
                            <div class="mt-1 text-[11px] text-gray-500">
                                {{ optional($log->created_at)->format('Y-m-d H:i') }}
                                · {{ $log->user->name ?? 'System' }}
                                @if($log->tenant_id)
                                    · tenant <span class="font-mono">#{{ $log->tenant_id }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="shrink-0 px-2 py-1 rounded-full text-[11px] font-semibold {{ ($log->status ?? 'success') === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $log->status ?? 'success' }}
                        </span>
                    </div>
                @empty
                    <div class="text-sm text-gray-600">No deploy activity yet.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

