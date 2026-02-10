@extends('layouts.platform')

@section('title', 'Monitoring Center')
@section('header', 'Monitoring Center')

@section('content')
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Monitoring Center</h1>
            <p class="mt-2 text-sm text-gray-700">Uptime checks, SSL expiry, backups, and domain health.</p>
        </div>
        <div
            class="mt-4 sm:mt-0 flex items-center gap-2"
            x-data="{
                open: false,
                actionId: '',
                phrase: '',
                confirmText: '',
                openModal(actionId, phrase) {
                    this.actionId = String(actionId || '');
                    this.phrase = String(phrase || '');
                    this.confirmText = '';
                    this.open = true;
                },
                canRun() { return (this.phrase || '') === '' || this.confirmText === this.phrase; },
            }"
        >
            <a href="{{ route('platform.monitoring.rules') }}" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50">
                <i class="ph ph-sliders mr-2 text-gray-600"></i> Rules
            </a>

            <form method="POST" action="{{ route('platform.control.run') }}">
                @csrf
                <input type="hidden" name="action_id" value="uptime_run">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800">
                    <i class="ph ph-waveform mr-2"></i> Run uptime
                </button>
            </form>

            <button
                type="button"
                class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50"
                @click="openModal('alerts_dispatch', 'alerts_dispatch')"
            >
                <i class="ph ph-bell-ringing mr-2 text-gray-600"></i> Dispatch alerts
            </button>

            <form method="POST" action="{{ route('platform.control.run') }}">
                @csrf
                <input type="hidden" name="action_id" value="integrity_check_latest">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50">
                    <i class="ph ph-shield-check mr-2 text-gray-600"></i> Integrity check
                </button>
            </form>

            <!-- Confirm Modal (for alerts, firewall, etc.) -->
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

                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2" id="modal-title">Confirm action</h3>
                                <p class="text-sm text-gray-600">
                                    Type the confirmation phrase to run this action.
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

    @php
        $uptimeActive = ($uptimeChecks ?? collect())->where('is_active', true)->count();
        $sslCount = ($sslExpiring ?? collect())->count();
        $backupFailCount = ($backupFailures ?? collect())->count();
        $http3Count = ($http3Issues ?? collect())->count();
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white shadow rounded-lg p-4 border border-gray-100">
            <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Uptime</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ $uptimeActive }}</div>
            <div class="mt-1 text-xs text-gray-600">Active checks</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4 border border-gray-100">
            <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">SSL</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ $sslCount }}</div>
            <div class="mt-1 text-xs text-gray-600">Expiring in {{ $sslDays ?? 14 }} days</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4 border border-gray-100">
            <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Backups</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ $backupFailCount }}</div>
            <div class="mt-1 text-xs text-gray-600">Failures (24h)</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4 border border-gray-100">
            <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">HTTP/3</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ $http3Count }}</div>
            <div class="mt-1 text-xs text-gray-600">Domains with issues</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Notifications & Schedules</h2>
                    <p class="mt-1 text-xs text-gray-600">Alert destinations + check intervals used by scheduler/runbooks.</p>
                </div>
            </div>
            <div class="p-5">
                <form method="POST" action="{{ route('platform.monitoring.settings.update') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">SSL alert days</label>
                        <input type="number" min="1" name="ssl_alert_days" value="{{ $settings['ssl_alert_days'] ?? ($sslDays ?? 14) }}" class="mt-1 block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Uptime check interval (minutes)</label>
                        <input type="number" min="1" name="uptime_check_interval_minutes" value="{{ $settings['uptime_check_interval_minutes'] ?? 5 }}" class="mt-1 block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alerts interval (hours)</label>
                        <input type="number" min="1" name="alerts_interval_hours" value="{{ $settings['alerts_interval_hours'] ?? 24 }}" class="mt-1 block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Slack webhook</label>
                        <input type="url" name="alerts_slack_webhook" value="{{ $settings['alerts_slack_webhook'] ?? '' }}" placeholder="https://hooks.slack.com/..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm font-mono">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Alert emails</label>
                        <input type="text" name="alerts_emails" value="{{ $settings['alerts_emails'] ?? '' }}" placeholder="ops@example.com, admin@example.com" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <p class="mt-2 text-xs text-gray-500">Comma-separated list.</p>
                    </div>

                    <div class="md:col-span-2 pt-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 shadow-sm">
                            <i class="ph ph-gear-six mr-2"></i> Save settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            class="bg-white shadow rounded-lg overflow-hidden"
            x-data="{
                    createOpen: false,
                    editOpen: false,
                    deleteOpen: false,
                    current: { id: null, tenant_id: '', name: '', url: '', expected_status: 200, is_active: true },
                    openCreate() {
                        this.current = { id: null, tenant_id: '', name: '', url: '', expected_status: 200, is_active: true };
                        this.createOpen = true;
                    },
                    openEdit(payload) {
                        this.current = {
                            id: payload.id,
                            tenant_id: payload.tenant_id,
                            name: payload.name || '',
                            url: payload.url || '',
                            expected_status: payload.expected_status || 200,
                            is_active: !!payload.is_active,
                        };
                        this.editOpen = true;
                    },
                    openDelete(payload) {
                        this.current = { id: payload.id, tenant_id: payload.tenant_id, name: payload.name || '', url: payload.url || '', expected_status: payload.expected_status || 200, is_active: !!payload.is_active };
                        this.deleteOpen = true;
                    },
                }"
        >
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Uptime Checks</h2>
                    <div class="mt-1 text-xs text-gray-500">{{ $uptimeActive }} active (showing up to 200)</div>
                </div>
                <button type="button" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 shadow-sm" @click="openCreate()">
                    <i class="ph ph-plus mr-2"></i> Add check
                </button>

                <!-- Create Modal -->
                <div x-show="createOpen" x-transition.opacity style="display:none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="createOpen = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form method="POST" action="{{ route('platform.monitoring.uptime.store') }}">
                                @csrf
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2" id="modal-title">Add uptime check</h3>

                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Tenant</label>
                                            <select name="tenant_id" x-model="current.tenant_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                                                <option value="" disabled>Select tenant…</option>
                                                @foreach(($tenants ?? []) as $t)
                                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Name</label>
                                            <input type="text" name="name" x-model="current.name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">URL</label>
                                            <input type="url" name="url" x-model="current.url" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm font-mono" placeholder="https://example.com/health" required>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Expected status</label>
                                                <input type="number" name="expected_status" min="100" max="599" x-model.number="current.expected_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                                            </div>
                                            <div class="flex items-center gap-2 pt-6">
                                                <input type="checkbox" name="is_active" value="1" x-model="current.is_active" class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                                <span class="text-sm text-gray-700 font-medium">Active</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-900 text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                                        Create
                                    </button>
                                    <button type="button" @click="createOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Modal -->
                <div x-show="editOpen" x-transition.opacity style="display:none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="editOpen = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form method="POST" :action="`{{ url('/platform/monitoring/uptime-checks') }}/${current.id}`">
                                @csrf
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2" id="modal-title">Edit uptime check</h3>

                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Name</label>
                                            <input type="text" name="name" x-model="current.name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">URL</label>
                                            <input type="url" name="url" x-model="current.url" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm font-mono" required>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Expected status</label>
                                                <input type="number" name="expected_status" min="100" max="599" x-model.number="current.expected_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                                            </div>
                                            <div class="flex items-center gap-2 pt-6">
                                                <input type="checkbox" name="is_active" value="1" x-model="current.is_active" class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                                <span class="text-sm text-gray-700 font-medium">Active</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-900 text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                                        Save
                                    </button>
                                    <button type="button" @click="editOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div x-show="deleteOpen" x-transition.opacity style="display:none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="deleteOpen = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form method="POST" :action="`{{ url('/platform/monitoring/uptime-checks') }}/${current.id}`">
                                @csrf
                                @method('DELETE')
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2" id="modal-title">Delete uptime check</h3>
                                    <p class="text-sm text-gray-700">
                                        This will delete <span class="font-mono" x-text="current.name"></span> and its events.
                                    </p>
                                </div>

                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                        Delete
                                    </button>
                                    <button type="button" @click="deleteOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tenant</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">URL</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Checked</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse(($uptimeChecks ?? []) as $check)
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-5 py-4">
                                    <div class="text-sm font-semibold text-gray-900">{{ $check->name }}</div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ $check->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $check->is_active ? 'active' : 'inactive' }}
                                        </span>
                                        <span class="ml-2 font-mono text-[11px] text-gray-500">expected {{ $check->expected_status }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ $check->tenant->name ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm">
                                    <a href="{{ $check->url }}" target="_blank" class="font-mono text-xs text-primary-700 hover:underline">{{ $check->url }}</a>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @php
                                        $ok = $check->last_status && (int) $check->last_status === (int) $check->expected_status && empty($check->last_error);
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $ok ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $check->last_status ?? '—' }}
                                    </span>
                                    @if(!empty($check->last_response_ms))
                                        <span class="ml-2 text-xs text-gray-600">{{ $check->last_response_ms }}ms</span>
                                    @endif
                                    @if(!empty($check->last_error))
                                        <div class="mt-1 text-xs text-red-700 truncate max-w-[420px]">{{ $check->last_error }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600 text-right">
                                    {{ optional($check->last_checked_at)->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-2 justify-end">
                                        <form method="POST" action="{{ route('platform.monitoring.uptime.run', $check) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm">
                                                <i class="ph ph-play mr-1 text-gray-600"></i> Run
                                            </button>
                                        </form>

                                        <button
                                            type="button"
                                            class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                                            @click="openEdit({ id: {{ (int) $check->id }}, tenant_id: {{ (int) $check->tenant_id }}, name: @js($check->name), url: @js($check->url), expected_status: {{ (int) $check->expected_status }}, is_active: {{ $check->is_active ? 'true' : 'false' }} })"
                                        >
                                            <i class="ph ph-pencil-simple mr-1 text-gray-600"></i> Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold text-red-900 bg-red-50 border border-red-200 hover:bg-red-100 shadow-sm"
                                            @click="openDelete({ id: {{ (int) $check->id }}, tenant_id: {{ (int) $check->tenant_id }}, name: @js($check->name), url: @js($check->url), expected_status: {{ (int) $check->expected_status }}, is_active: {{ $check->is_active ? 'true' : 'false' }} })"
                                        >
                                            <i class="ph ph-trash mr-1"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-600">No uptime checks configured.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Events Timeline</h2>
                <span class="text-xs text-gray-500">{{ ($events ?? collect())->count() }} latest</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">When</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Check</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tenant</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Latency</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse(($events ?? []) as $e)
                            @php
                                $ok = !empty($e->status) && empty($e->error) && (int) ($e->status) === (int) ($e->check?->expected_status ?? 200);
                            @endphp
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-5 py-4 text-sm text-gray-700">{{ optional($e->checked_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm">
                                    <div class="font-semibold text-gray-900">{{ $e->check?->name ?? '—' }}</div>
                                    <div class="mt-1 font-mono text-xs text-gray-500 truncate max-w-[520px]">{{ $e->check?->url ?? '' }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ $e->check?->tenant?->name ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ $ok ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $e->status ?? '—' }}
                                    </span>
                                    @if(!empty($e->error))
                                        <div class="mt-1 text-xs text-red-700 truncate max-w-[520px]">{{ $e->error }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700 text-right font-mono">{{ $e->response_ms ? ($e->response_ms . 'ms') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-600">No events yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">SSL Expiring Soon</h2>
                <span class="text-xs text-gray-500">{{ $sslCount }} items</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Domain</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Expires</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Days left</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse(($sslExpiring ?? []) as $cert)
                            @php
                                $daysLeft = $cert->expires_at ? now()->diffInDays($cert->expires_at, false) : null;
                            @endphp
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $cert->domain?->hostname ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ optional($cert->expires_at)->toDateTimeString() ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700 text-right">{{ $daysLeft !== null ? $daysLeft : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center text-sm text-gray-600">No SSL certificates expiring soon.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Backup Failures (24h)</h2>
                <span class="text-xs text-gray-500">{{ $backupFailCount }} items</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Run</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">When</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">By</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse(($backupFailures ?? []) as $run)
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900">#{{ $run->id }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ $run->type ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ optional($run->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700 text-right">{{ $run->creator->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-600">No backup failures in the last 24 hours.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">HTTP/3 Issues</h2>
                <span class="text-xs text-gray-500">{{ $http3Count }} items</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Domain</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Checked</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse(($http3Issues ?? []) as $d)
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $d->hostname }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">{{ $d->http3_status ?? 'unknown' }}</span>
                                    @if(!empty($d->http3_error))
                                        <div class="mt-1 text-xs text-gray-600 truncate max-w-[520px]">{{ $d->http3_error }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700 text-right">{{ optional($d->http3_checked_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center text-sm text-gray-600">No HTTP/3 issues detected.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
