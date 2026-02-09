@extends('layouts.platform')

@section('title', 'Tenants')
@section('header', 'Manage Sites')

@section('content')
    @if(session('success'))
        <div class="rounded-md bg-green-50 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="ph ph-check-circle text-green-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Filters -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <div class="flex-1 relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="ph ph-magnifying-glass text-gray-400"></i>
            </div>
            <input type="text" id="searchInput" class="focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" placeholder="Search sites by name or domain...">
        </div>

        <select id="statusFilter" class="mt-1 block pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
        </select>

        <a href="{{ route('platform.tenants.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <i class="ph ph-plus mr-2"></i>
            Create New Site
        </a>
    </div>

    <!-- Bulk Actions -->
    <div class="hidden bg-gray-50 border border-gray-200 rounded-md p-4 mb-6 flex items-center gap-4" id="bulkActions">
        <span id="selectedCount" class="text-sm font-semibold text-gray-700">0 selected</span>
        <button class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" onclick="bulkAction('activate')">
            Activate
        </button>
        <button class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500" onclick="bulkAction('deactivate')">
            Deactivate
        </button>
        <button class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" onclick="bulkAction('delete')">
            Delete
        </button>
        <button class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" onclick="clearSelection()">
            Clear
        </button>
    </div>

    <!-- Tenant Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3" id="tenantGrid">
        @forelse($tenants as $tenant)
            @php
                $primaryDomain = $tenant->domains->firstWhere('is_primary', true);
                $isActive = $tenant->status === 'active';
                $userCount = $tenant->users->count() ?? 0;
                $domainCount = $tenant->domains->count();
            @endphp

            <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow border border-gray-200 tenant-card" 
                 data-tenant-id="{{ $tenant->id }}" 
                 data-status="{{ $tenant->status }}"
                 data-name="{{ strtolower($tenant->name) }}" 
                 data-domain="{{ strtolower($primaryDomain->hostname ?? '') }}">
                
                <div class="p-5">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 leading-tight">{{ $tenant->name }}</h3>
                            <code class="text-xs text-gray-500 bg-gray-100 rounded px-1.5 py-0.5 mt-1 inline-block">{{ $tenant->id }}</code>
                        </div>
                        <div class="ml-2">
                             <input type="checkbox" class="tenant-checkbox h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" value="{{ $tenant->id }}">
                        </div>
                    </div>

                    <div class="mb-4">
                        @if($isActive)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {{ ucfirst($tenant->status) }}
                            </span>
                        @endif
                    </div>

                    <!-- Domains -->
                    <div class="bg-gray-50 rounded-md p-3 mb-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Domains</div>
                        <div class="space-y-1">
                            @forelse($tenant->domains->take(3) as $domain)
                                <div class="flex items-center text-sm">
                                    @if($domain->is_primary)
                                        <i class="ph ph-star-fill text-yellow-400 mr-1.5 text-xs"></i>
                                    @else
                                        <i class="ph ph-globe text-gray-400 mr-1.5 text-xs"></i>
                                    @endif
                                    <a href="//{{ $domain->hostname }}" target="_blank" class="text-primary-600 hover:text-primary-800 truncate">
                                        {{ $domain->hostname }}
                                    </a>
                                </div>
                            @empty
                                <div class="text-sm text-gray-500 italic">No domains</div>
                            @endforelse
                        </div>
                        @if($tenant->domains->count() > 3)
                            <div class="text-xs text-gray-500 mt-2 pl-5">
                                +{{ $tenant->domains->count() - 3 }} more
                            </div>
                        @endif
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-4 border-t border-b border-gray-100 py-3">
                        <div class="text-center border-r border-gray-100">
                            <div class="text-lg font-bold text-gray-900">{{ $domainCount }}</div>
                            <div class="text-xs text-gray-500 uppercase">Domains</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-gray-900">{{ $userCount }}</div>
                            <div class="text-xs text-gray-500 uppercase">Users</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-2">
                        <a href="{{ route('platform.tenants.show', $tenant->id) }}" class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Manage
                        </a>
                        
                        <form action="{{ route('platform.tenants.toggle-status', $tenant->id) }}" method="POST" class="flex-1">
                            @csrf
                            @if($isActive)
                                <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                    Deactivate
                                </button>
                            @else
                                <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Activate
                                </button>
                            @endif
                        </form>

                        <form action="{{ route('platform.tenants.destroy', $tenant->id) }}" method="POST" onsubmit="return confirm('Delete {{ $tenant->name }}? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex justify-center items-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <i class="ph ph-trash"></i>
                            </button>
                        </form>
                    </div>

                    <div class="mt-3 text-right">
                        <span class="text-xs text-gray-400">Created {{ $tenant->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 bg-white rounded-lg shadow border border-gray-200">
                <i class="ph ph-globe text-gray-300 text-6xl mb-4 block"></i>
                <h3 class="text-lg font-medium text-gray-900">No sites found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first tenant site.</p>
                <div class="mt-6">
                    <a href="{{ route('platform.tenants.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <i class="ph ph-plus mr-2"></i>
                        Create New Site
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($tenants->hasPages())
        <div class="mt-6">
            {{ $tenants->links() }}
        </div>
    @endif

    <script>
        // Search and filter
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const tenantCards = document.querySelectorAll('.tenant-card');

        function filterTenants() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusValue = statusFilter.value.toLowerCase();

            tenantCards.forEach(card => {
                const name = card.dataset.name;
                const domain = card.dataset.domain;
                const status = card.dataset.status;

                const matchesSearch = !searchTerm || name.includes(searchTerm) || domain.includes(searchTerm);
                const matchesStatus = !statusValue || status === statusValue;

                card.style.display = matchesSearch && matchesStatus ? 'block' : 'none';
            });
        }

        searchInput.addEventListener('input', filterTenants);
        statusFilter.addEventListener('change', filterTenants);

        // Bulk selection
        const checkboxes = document.querySelectorAll('.tenant-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActions);
        });

        function updateBulkActions() {
            const selected = document.querySelectorAll('.tenant-checkbox:checked');
            const count = selected.length;

            if (count > 0) {
                bulkActions.classList.remove('hidden');
                selectedCount.textContent = `${count} selected`;

                // Update card styling
                tenantCards.forEach(card => {
                    const checkbox = card.querySelector('.tenant-checkbox');
                    if (checkbox && checkbox.checked) {
                        card.classList.add('ring-2', 'ring-primary-500', 'bg-blue-50');
                    } else {
                        card.classList.remove('ring-2', 'ring-primary-500', 'bg-blue-50');
                    }
                });
            } else {
                bulkActions.classList.add('hidden');
                tenantCards.forEach(card => card.classList.remove('ring-2', 'ring-primary-500', 'bg-blue-50'));
            }
        }

        function clearSelection() {
            checkboxes.forEach(checkbox => checkbox.checked = false);
            updateBulkActions();
        }

        function bulkAction(action) {
            const selected = Array.from(document.querySelectorAll('.tenant-checkbox:checked')).map(cb => cb.value);

            if (selected.length === 0) return;

            let confirmMessage = '';
            if (action === 'delete') {
                confirmMessage = `Delete ${selected.length} site(s)? This cannot be undone.`;
            } else if (action === 'activate') {
                confirmMessage = `Activate ${selected.length} site(s)?`;
            } else if (action === 'deactivate') {
                confirmMessage = `Deactivate ${selected.length} site(s)?`;
            }

            if (!confirm(confirmMessage)) return;

            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/platform/tenants/bulk-${action}`;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            selected.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tenant_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    </script>
@endsection