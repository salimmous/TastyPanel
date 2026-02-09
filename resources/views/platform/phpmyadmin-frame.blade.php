@extends('layouts.platform')

@section('title', 'phpMyAdmin — ' . $tenant->name)
@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">phpMyAdmin — {{ $tenant->name }}</h1>
        <a href="{{ route('platform.tenants.show', $tenant->id) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <i class="ph ph-arrow-left mr-2"></i> Back to site
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow overflow-hidden" style="height: calc(100vh - 12rem); min-height: 480px;">
        <iframe
            src="{{ $pmaUrl }}"
            title="phpMyAdmin — {{ $tenant->name }}"
            class="w-full h-full border-0"
            sandbox="allow-same-origin allow-scripts allow-forms allow-popups"
        ></iframe>
    </div>
    <p class="mt-2 text-sm text-gray-500">You may be prompted for web login (basic auth) and then MySQL credentials. Use the database user from the Database tab or the <code class="text-xs bg-gray-100 px-1 rounded">pma_{{ $tenant->slug ?? $tenant->instance_key }}</code> user if configured.</p>
@endsection
