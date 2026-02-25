@extends('layouts.platform')

@section('header', 'Create Database')

@section('content')
    <div class="max-w-2xl mx-auto">
        <form action="{{ route('platform.databases.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="card p-6 space-y-6">
                <div class="grid grid-cols-1 gap-6">
                    <!-- Database Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-stone-700 mb-2">Database Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="input @error('name') border-red-500 @enderror"
                            placeholder="my_database">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Database User -->
                    <div>
                        <label for="username" class="block text-sm font-semibold text-stone-700 mb-2">Database User</label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}" required
                            class="input @error('username') border-red-500 @enderror"
                            placeholder="my_user">
                        @error('username')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password (Optional - Auto Generated) -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-stone-700 mb-2">
                            Password
                            <span class="font-normal text-stone-500 ml-1">(Leave empty to auto-generate)</span>
                        </label>
                        <input type="text" name="password" id="password" value="{{ old('password') }}"
                            class="input @error('password') border-red-500 @enderror"
                            placeholder="SecretPassword123">
                        @error('password')
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
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ old('tenant_id') == $site->id ? 'selected' : '' }}>
                                        {{ $site->name }} ({{ $site->slug }})
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
                <a href="{{ route('platform.databases') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    Create Database
                </button>
            </div>
        </form>
    </div>
@endsection
