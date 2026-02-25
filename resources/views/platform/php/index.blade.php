@extends('layouts.platform')

@section('header', 'PHP Versions')

@section('content')
    <div class="card">
        <div class="px-6 py-6 border-b border-stone-200">
            <h3 class="text-lg font-medium text-stone-900">Installed PHP Versions</h3>
            <p class="mt-1 text-sm text-stone-500">Manage available PHP versions for your tenants.</p>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach(['8.1', '8.2', '8.3'] as $version)
                <div class="border border-stone-200 rounded-xl p-5 relative overflow-hidden group hover:border-primary-300 transition-colors bg-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-lg bg-[#777BB4]/10 flex items-center justify-center text-[#777BB4]">
                                <i class="ph ph-file-code text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-stone-900 text-lg">PHP {{ $version }}</h4>
                                <p class="text-xs text-stone-500">FPM & CLI</p>
                            </div>
                        </div>
                        @if($version === '8.3')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                Installed
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-500">
                                Not Installed
                            </span>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-stone-500">Status</span>
                            <span class="font-medium {{ $version === '8.3' ? 'text-emerald-600' : 'text-stone-400' }}">
                                {{ $version === '8.3' ? 'Active' : 'Available' }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-stone-500">Extensions</span>
                            <span class="font-medium text-stone-900">84</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        @if($version === '8.3')
                            <button disabled class="w-full py-2 px-4 bg-stone-100 text-stone-400 rounded-lg text-sm font-medium cursor-not-allowed">
                                Installed
                            </button>
                        @else
                            <button class="w-full py-2 px-4 border border-stone-300 text-stone-700 hover:bg-stone-50 rounded-lg text-sm font-medium transition-colors">
                                Install
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
