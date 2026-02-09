@extends('layouts.platform')

@section('title', 'Roles & Permissions')
@section('header', 'Roles & Permissions')

@section('content')
    <div class="card p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-semibold text-lg">Platform Roles</h3>
            <a href="{{ route('platform.roles.create') }}" class="btn btn-primary">Create Role</a>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th class="text-left py-3 px-4">Display Name</th>
                        <th class="text-left py-3 px-4">Level</th>
                        <th class="text-left py-3 px-4">Users</th>
                        <th class="text-left py-3 px-4">System</th>
                        <th class="text-right py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($roles as $role)
                        <tr>
                            <td class="py-3 px-4">
                                <span class="font-medium">{{ $role->display_name }}</span>
                                <div class="text-xs text-gray-500">{{ $role->name }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-gray-100 rounded text-xs">{{ $role->level }}</span>
                            </td>
                            <td class="py-3 px-4">{{ $role->users_count }}</td>
                            <td class="py-3 px-4">
                                @if($role->is_system)
                                    <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">SYSTEM</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('platform.roles.edit', $role->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                                    @if(!$role->is_system)
                                        <form action="{{ route('platform.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
