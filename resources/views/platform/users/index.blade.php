@extends('layouts.platform')

@section('header', 'Users')

@section('header_actions')
    <div x-data="{ tab: 'panel' }" class="flex gap-2">
        <a href="{{ route('platform.users.create') }}" class="btn btn-primary" x-show="tab === 'panel'">
            <i class="ph ph-plus-circle text-lg"></i>
            Create Panel User
        </a>
        <a href="{{ route('platform.users.system.create') }}" class="btn btn-primary" x-show="tab === 'system'">
            <i class="ph ph-plus-circle text-lg"></i>
            Create System User
        </a>
    </div>
@endsection

@section('content')
    <div x-data="{ tab: 'panel' }">
        <!-- Tabs -->
        <div class="flex space-x-1 rounded-xl bg-stone-200/50 p-1 mb-6 max-w-sm">
            <button
                @click="tab = 'panel'"
                :class="tab === 'panel' ? 'bg-white shadow text-stone-900' : 'text-stone-500 hover:text-stone-700'"
                class="w-full rounded-lg py-2.5 text-sm font-medium leading-5 transition-all duration-200"
            >
                Panel Users
            </button>
            <button
                @click="tab = 'system'"
                :class="tab === 'system' ? 'bg-white shadow text-stone-900' : 'text-stone-500 hover:text-stone-700'"
                class="w-full rounded-lg py-2.5 text-sm font-medium leading-5 transition-all duration-200"
            >
                System Users
            </button>
        </div>

        <!-- Panel Users Tab -->
        <div x-show="tab === 'panel'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="card">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>2FA</th>
                                <th>Created</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr class="group hover:bg-stone-50 transition-colors">
                                    <td class="font-medium text-stone-900">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-stone-200 flex items-center justify-center text-stone-500 text-xs font-bold">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                            {{ $user->name }}
                                        </div>
                                    </td>
                                    <td class="text-stone-600">{{ $user->email }}</td>
                                    <td>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-600">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($user->two_factor_enabled)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600">
                                                <i class="ph ph-shield-check text-base"></i> On
                                            </span>
                                        @else
                                            <span class="text-stone-400 text-xs">Off</span>
                                        @endif
                                    </td>
                                    <td class="text-stone-500 text-sm">{{ $user->created_at->format('M d, Y') }}</td>
                                    <td class="text-right">
                                        @if(auth()->id() !== $user->id)
                                            <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <form action="{{ route('platform.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Delete this user?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-2 rounded-lg text-stone-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                                        <i class="ph ph-trash text-lg"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-8 text-stone-500">No users found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($users->hasPages())
                    <div class="px-6 py-4 border-t border-stone-200">{{ $users->links() }}</div>
                @endif
            </div>
        </div>

        <!-- System Users Tab -->
        <div x-show="tab === 'system'" style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="card">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Site</th>
                                <th>Home Directory</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($systemUsers as $sysUser)
                                <tr class="group hover:bg-stone-50 transition-colors">
                                    <td class="font-medium text-stone-900">
                                        <div class="flex items-center gap-2">
                                            <i class="ph ph-terminal-window text-lg text-stone-400"></i>
                                            {{ $sysUser->username }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($sysUser->tenant)
                                            <a href="{{ route('platform.tenants.show', $sysUser->tenant->id) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
                                                {{ $sysUser->tenant->name }}
                                            </a>
                                        @else
                                            <span class="text-stone-400 text-sm">—</span>
                                        @endif
                                    </td>
                                    <td class="text-stone-600 font-mono text-xs">{{ $sysUser->home_dir }}</td>
                                    <td>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $sysUser->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $sysUser->status === 'active' ? 'bg-emerald-500' : 'bg-stone-400' }}"></span>
                                            {{ ucfirst($sysUser->status) }}
                                        </span>
                                    </td>
                                    <td class="text-stone-500 text-sm">{{ $sysUser->created_at->format('M d, Y') }}</td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <form action="{{ route('platform.users.system.destroy', $sysUser->id) }}" method="POST" onsubmit="return confirm('Delete this system user? Files in home directory may be lost.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 rounded-lg text-stone-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                                    <i class="ph ph-trash text-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-8 text-stone-500">No system users found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($systemUsers->hasPages())
                    <div class="px-6 py-4 border-t border-stone-200">{{ $systemUsers->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
