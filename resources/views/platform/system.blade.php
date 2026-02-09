@extends('layouts.platform')

@section('title', 'System Status')
@section('header', 'System Status')

@section('content')
@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Services Status</h3>
            </div>
            <div class="p-4 bg-white space-y-4">
                @foreach($services as $name => $status)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            @if($status === 'running')
                                <div class="flex-shrink-0 h-2.5 w-2.5 rounded-full bg-green-500 mr-3 animate-pulse"></div>
                            @else
                                <div class="flex-shrink-0 h-2.5 w-2.5 rounded-full bg-red-500 mr-3"></div>
                            @endif
                            <span class="text-sm font-medium text-gray-700 capitalize">{{ str_replace('_', ' ', $name) }}</span>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $status === 'running' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ strtoupper($status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Queue Status</h3>
            </div>
            <div class="p-4 bg-white space-y-4">
                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="flex items-center">
                        <i class="ph ph-hourglass text-blue-500 text-xl mr-3"></i>
                        <span class="text-sm font-medium text-blue-900">Pending Jobs</span>
                    </div>
                    <span class="text-2xl font-bold text-blue-700">{{ number_format($queueSize) }}</span>
                </div>
                <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-100">
                    <div class="flex items-center">
                        <i class="ph ph-warning-circle text-red-500 text-xl mr-3"></i>
                        <span class="text-sm font-medium text-red-900">Failed Jobs</span>
                    </div>
                    <span class="text-2xl font-bold text-red-700">{{ number_format($failedJobs) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50 flex items-center">
            <i class="ph ph-server text-gray-400 text-xl mr-3"></i>
            <h3 class="text-lg leading-6 font-medium text-gray-900">Server Info</h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-3">
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">PHP Version</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded inline-block">{{ phpversion() }}</dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Laravel Version</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded inline-block">{{ app()->version() }}</dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Server IP</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded inline-block">{{ $_SERVER['SERVER_ADDR'] ?? 'Unknown' }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection