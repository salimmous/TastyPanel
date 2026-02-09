@extends('layouts.platform')

@section('title', 'Theme Marketplace')
@section('header', 'Marketplace')

@section('content')
@section('content')
    <div class="mb-6 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Theme Marketplace</h1>
            <p class="mt-2 text-sm text-gray-700">Discover and install themes for your platform.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('platform.themes') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <i class="ph ph-arrow-left mr-2"></i>
                Back to Installed Themes
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($themes as $theme)
            <div class="bg-white overflow-hidden shadow rounded-lg flex flex-col h-full border border-gray-100">
                <div class="relative aspect-video bg-gray-100 border-b border-gray-200">
                    @if($theme->preview_image)
                        <img src="{{ $theme->preview_image }}" alt="{{ $theme->name }}" class="w-full h-full object-cover">
                    @else
                        <div class="flex items-center justify-center h-full text-gray-400">
                            <span class="flex items-center">
                                <i class="ph ph-image-broken text-2xl mr-2"></i>
                                No Preview
                            </span>
                        </div>
                    @endif
                    @if($theme->is_featured)
                        <div class="absolute top-2 right-2 bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-1 rounded shadow-sm">Featured</div>
                    @endif
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $theme->name }}</h3>
                            <p class="text-xs text-gray-500">by {{ $theme->author }}</p>
                        </div>
                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded">{{ $theme->version }}</span>
                    </div>
                    <p class="text-sm text-gray-600 mb-6 flex-1">{{ Str::limit($theme->description, 100) }}</p>

                    <div class="mt-auto">
                        <button class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <i class="ph ph-check mr-2"></i>
                            Installed
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12 bg-white rounded-lg shadow border border-gray-100">
                <i class="ph ph-storefront text-gray-300 text-5xl mb-4"></i>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No themes found</h3>
                <p class="mt-1 text-sm text-gray-500">No themes available in the marketplace right now.</p>
            </div>
        @endforelse
    </div>
@endsection
