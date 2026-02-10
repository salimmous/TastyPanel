@extends('layouts.platform')

@section('title', 'Analytics')
@section('header', 'Analytics')

@section('content')
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-blue-500 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-50 rounded-md p-3">
                    <i class="ph ph-globe text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Requests</dt>
                        <dd>
                            <div class="text-2xl font-bold text-gray-900">{{ $stats['total_requests'] }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-400 font-medium">Requires Middleware</div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-green-500 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-50 rounded-md p-3">
                    <i class="ph ph-lightning text-green-600 text-xl"></i>
                </div>
                <div class="ml-4 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Avg Response Time</dt>
                        <dd>
                            <div class="text-2xl font-bold text-gray-900">{{ $stats['avg_response_time'] }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-400 font-medium">Requires APM</div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-yellow-500 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-50 rounded-md p-3">
                    <i class="ph ph-warning text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Job Error Rate</dt>
                        <dd>
                            <div class="text-2xl font-bold text-gray-900">{{ $stats['error_rate'] }}%</div>
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-500 font-medium">Based on Failed Jobs</div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-purple-500 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-50 rounded-md p-3">
                    <i class="ph ph-users text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Users (Total)</dt>
                        <dd>
                            <div class="text-2xl font-bold text-gray-900">{{ $stats['unique_visitors'] }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="mt-2 text-xs text-green-600 font-medium">Registered Accounts</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Traffic Overview</h3>
            </div>
            <div class="p-6">
                <div class="h-64 bg-gray-50 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg">
                    <i class="ph ph-chart-line-up text-gray-400 text-4xl mb-2"></i>
                    <span class="text-gray-500 text-sm font-medium text-center px-4">Traffic Chart requires external data source<br>(e.g. Google Analytics)</span>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Top Tenants by Users</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                @forelse($stats['top_tenants'] as $tenant)
                    <li class="px-4 py-4 sm:px-6 flex justify-between items-center hover:bg-gray-50 transition">
                        <div class="flex items-center">
                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gray-100 text-gray-500 font-bold text-sm mr-3">
                                {{ substr($tenant->name, 0, 1) }}
                            </span>
                            <span class="font-medium text-gray-900">{{ $tenant->name }}</span>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $tenant->users_count }} users
                        </span>
                    </li>
                @empty
                    <li class="px-4 py-4 sm:px-6 text-center text-sm text-gray-500">No tenants found.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
