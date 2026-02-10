@extends('layouts.platform')

@section('title', 'Create Site')
@section('header', 'Create Site')

@section('content')
<div class="max-w-3xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:grid md:grid-cols-3 md:gap-8">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0 md:sticky md:top-24">
                <h3 class="text-lg font-semibold text-stone-900">Site Configuration</h3>
                <p class="mt-2 text-sm text-stone-600 leading-relaxed">
                    Create a new manual site. No theme or provisioning is applied automatically. You can assign a theme and deploy later from the admin tools.
                </p>
                <p class="mt-3 text-xs text-stone-500">
                    Workflow: <code class="px-1.5 py-0.5 rounded bg-stone-100 text-stone-700 font-medium">TENANT-WORKFLOW.md</code>
                </p>
            </div>
        </div>

        <div class="mt-6 md:mt-0 md:col-span-2">
            <form action="{{ route('platform.tenants.store') }}" method="POST">
                @csrf
                <div class="rounded-2xl border border-stone-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-5 py-6 sm:p-6 space-y-6">
                        @if($errors->any())
                            <div class="rounded-xl bg-red-50/90 border border-red-100 p-4">
                                <div class="flex gap-3">
                                    <div class="flex-shrink-0">
                                        <i class="ph ph-warning-circle text-red-500 text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-red-800">Errors</h3>
                                        <ul class="mt-1.5 text-sm text-red-700 list-disc pl-4 space-y-0.5">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-4">
                                <label for="name" class="block text-sm font-medium text-stone-700">Site Name</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                    class="mt-1.5 block w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-sm text-stone-900 placeholder:text-stone-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition"
                                    placeholder="My Project Site">
                            </div>

                            <div class="col-span-6 sm:col-span-4">
                                <label for="domain" class="block text-sm font-medium text-stone-700">Primary Domain</label>
                                <div class="mt-1.5 flex rounded-xl overflow-hidden border border-stone-300 focus-within:ring-2 focus-within:ring-amber-500/20 focus-within:border-amber-500">
                                    <span class="inline-flex items-center px-3.5 bg-stone-50 text-stone-500 text-sm border-r border-stone-300">https://</span>
                                    <input type="text" name="domain" id="domain" value="{{ old('domain') }}" required
                                        class="flex-1 min-w-0 border-0 bg-white px-3.5 py-2.5 text-sm text-stone-900 placeholder:text-stone-400 focus:ring-0 focus:outline-none"
                                        placeholder="example.com">
                                </div>
                                <p class="mt-1.5 text-sm text-stone-500">The primary domain for this tenant.</p>
                            </div>

                            <div class="col-span-6 border-t border-stone-200 pt-6">
                                <h4 class="text-sm font-semibold text-stone-900">Database</h4>
                                <p class="mt-1 text-sm text-stone-600">Created automatically during install (MySQL). Credentials are written to the site’s <code class="rounded bg-stone-100 px-1.5 py-0.5 text-stone-700 text-xs font-medium">.env</code>.</p>
                            </div>

                            <div class="col-span-6 border-t border-stone-200 pt-6">
                                <h4 class="text-sm font-semibold text-stone-900">Admin account (site login)</h4>
                                <p class="mt-1 text-sm text-stone-600 mb-4">First admin user for this site. Optional: leave blank to create admin later from the site page.</p>
                                <div class="grid grid-cols-6 gap-4">
                                    <div class="col-span-6 sm:col-span-3">
                                        <label for="admin_email" class="block text-sm font-medium text-stone-700">Admin email</label>
                                        <input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email', auth()->user()?->email ?? '') }}"
                                            class="mt-1.5 block w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                                            placeholder="admin@example.com">
                                    </div>
                                    <div class="col-span-6 sm:col-span-3">
                                        <label for="admin_user" class="block text-sm font-medium text-stone-700">Admin username</label>
                                        <input type="text" name="admin_user" id="admin_user" value="{{ old('admin_user', 'admin') }}"
                                            class="mt-1.5 block w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                                            placeholder="admin">
                                    </div>
                                    <div class="col-span-6 sm:col-span-3">
                                        <label for="admin_password" class="block text-sm font-medium text-stone-700">Admin password</label>
                                        <input type="password" name="admin_password" id="admin_password"
                                            class="mt-1.5 block w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                                            placeholder="Min 8 characters" autocomplete="new-password">
                                        <p class="mt-1 text-xs text-stone-500">Optional. Used by the app’s first-run or seed to create the admin user.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-6 border-t border-stone-200 pt-6">
                                <div class="rounded-xl border border-amber-200/80 bg-amber-50/60 p-4">
                                    <div class="flex items-center gap-2">
                                        <i class="ph ph-download-simple text-amber-600 text-lg"></i>
                                        <h4 class="text-sm font-semibold text-stone-900">Install after create</h4>
                                    </div>
                                    <p class="mt-2 text-sm text-stone-700">
                                        After you click <strong>Create Site</strong>, provisioning runs automatically: clone app (Laravel patch), database, Nginx, SSL. You will be redirected to the new site page where you can follow the install status.
                                    </p>
                                    <p class="mt-1 text-xs text-stone-600">One click - site is created and install starts. Check the site page for progress.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-5 py-4 bg-stone-50/80 border-t border-stone-200 flex flex-wrap items-center justify-end gap-3 sm:px-6">
                        <a href="{{ route('platform.tenants') }}" class="inline-flex items-center justify-center py-2.5 px-4 rounded-xl border border-stone-300 bg-white text-sm font-semibold text-stone-700 shadow-sm hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:ring-offset-0 transition">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center py-2.5 px-5 rounded-xl border border-transparent bg-amber-500 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition">
                            Create Site
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
