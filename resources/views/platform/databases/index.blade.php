@extends('layouts.platform')

@section('header', 'Databases')

@section('header_actions')
    <a href="{{ route('platform.databases.create') }}" class="btn btn-primary">
        <i class="ph ph-plus-circle text-lg"></i>
        Create Database
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>User</th>
                        <th>Site</th>
                        <th>Size (MB)</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($databases as $db)
                        <tr class="group hover:bg-stone-50 transition-colors">
                            <td class="font-medium text-stone-900">{{ $db->name }}</td>
                            <td class="text-stone-600">{{ $db->username }}</td>
                            <td>
                                @if($db->tenant)
                                    <a href="{{ route('platform.tenants.show', $db->tenant->id) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
                                        {{ $db->tenant->name }}
                                    </a>
                                @else
                                    <span class="text-stone-400 text-sm">—</span>
                                @endif
                            </td>
                            <td class="text-stone-600">{{ $db->size_mb }}</td>
                            <td>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $db->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $db->status === 'active' ? 'bg-emerald-500' : 'bg-stone-400' }}"></span>
                                    {{ ucfirst($db->status) }}
                                </span>
                            </td>
                            <td class="text-stone-500 text-sm">{{ $db->created_at->format('M d, Y') }}</td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <form action="{{ route('platform.databases.destroy', $db->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this database? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg text-stone-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete Database">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('platform.databases.backup', $db->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="p-2 rounded-lg text-stone-400 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Backup Database">
                                            <i class="ph ph-download-simple text-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center text-stone-500">
                                    <i class="ph ph-database text-4xl mb-4 text-stone-300"></i>
                                    <p class="text-lg font-medium text-stone-900">No databases found</p>
                                    <p class="text-sm mt-1">Create your first database to get started.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($databases->hasPages())
            <div class="px-6 py-4 border-t border-stone-200">
                {{ $databases->links() }}
            </div>
        @endif
    </div>
@endsection
