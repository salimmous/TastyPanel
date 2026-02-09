@extends('layouts.platform')

@section('title', isset($role) ? 'Edit Role' : 'Create Role')
@section('header', isset($role) ? 'Edit Role: ' . $role->display_name : 'Create New Role')

@section('content')
    <div class="card p-6 max-w-4xl mx-auto">
        <form action="{{ isset($role) ? route('platform.roles.update', $role->id) : route('platform.roles.store') }}" method="POST">
            @csrf
            @if(isset($role))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Display Name</label>
                    <input type="text" name="display_name" value="{{ old('display_name', $role->display_name ?? '') }}" class="input w-full" required>
                    <p class="mt-1 text-xs text-gray-500">Human readable name (e.g. "Support Agent")</p>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">System Name (Slug)</label>
                    <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}" class="input w-full" {{ isset($role) ? 'readonly disabled' : '' }} required>
                    <p class="mt-1 text-xs text-gray-500">Unique identifier (e.g. "support_agent")</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Description</label>
                    <textarea name="description" rows="2" class="input w-full">{{ old('description', $role->description ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Access Level (0-100)</label>
                    <input type="number" name="level" value="{{ old('level', $role->level ?? 0) }}" min="0" max="100" class="input w-full">
                    <p class="mt-1 text-xs text-gray-500">Higher level = more authority over lower levels.</p>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-6">
                <h3 class="font-semibold text-lg mb-4">Permissions</h3>
                
                @foreach($permissions as $group => $perms)
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-700 capitalize mb-2">{{ str_replace('_', ' ', $group) }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($perms as $perm)
                                <label class="flex items-start gap-2 p-2 border rounded hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm['id'] }}" 
                                        class="mt-1"
                                        {{ (isset($role) && $role->permissions->contains('id', $perm['id'])) ? 'checked' : '' }}>
                                    <div>
                                        <div class="text-sm font-medium">{{ $perm['display_name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $perm['name'] }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end gap-3 mt-8">
                <a href="{{ route('platform.roles.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ isset($role) ? 'Update Role' : 'Create Role' }}</button>
            </div>
        </form>
    </div>
@endsection
