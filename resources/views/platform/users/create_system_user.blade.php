@extends('layouts.platform')

@section('header', 'Create System User')

@section('content')
    <div class="max-w-2xl mx-auto">
        <form action="{{ route('platform.users.system.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="card p-6 space-y-6">
                <div class="grid grid-cols-1 gap-6">
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-semibold text-stone-700 mb-2">Username</label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}" required
                            class="input @error('username') border-red-500 @enderror"
                            placeholder="ssh_user">
                        @error('username')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-stone-700 mb-2">
                            Password
                            <span class="font-normal text-stone-500 ml-1">(Optional if SSH Key provided)</span>
                        </label>
                        <input type="text" name="password" id="password" value="{{ old('password') }}"
                            class="input @error('password') border-red-500 @enderror"
                            placeholder="SecretPassword123">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- SSH Key -->
                    <div>
                        <label for="ssh_key" class="block text-sm font-semibold text-stone-700 mb-2">
                            SSH Public Key
                            <span class="font-normal text-stone-500 ml-1">(Optional)</span>
                        </label>
                        <textarea name="ssh_key" id="ssh_key" rows="3"
                            class="input font-mono text-xs @error('ssh_key') border-red-500 @enderror"
                            placeholder="ssh-rsa AAAAB3NzaC..."></textarea>
                        @error('ssh_key')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Site Link (Optional) -->
                    <div>
                        <label for="tenant_id" class="block text-sm font-semibold text-stone-700 mb-2">
                            Link to Site
                            <span class="font-normal text-stone-500 ml-1">(Optional)</span>
                        </label>
                        <div class="relative">
                            <select name="tenant_id" id="tenant_id" class="input appearance-none">
                                <option value="">-- No Site --</option>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                                        {{ $tenant->name }} ({{ $tenant->slug }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-stone-500">
                                <i class="ph ph-caret-down"></i>
                            </div>
                        </div>
                        @error('tenant_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('platform.users') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    Create System User
                </button>
            </div>
        </form>
    </div>
@endsection
