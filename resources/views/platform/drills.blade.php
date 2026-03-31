@extends('layouts.platform')

@section('title', 'Disaster Recovery Drills')
@section('header', 'Disaster Recovery Drills')

@section('content')
    @if(session('success'))
        <div class="rounded-md bg-green-50 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="ph ph-check-circle text-green-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-md bg-red-50 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="ph ph-x-circle text-red-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-blue-500">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="ph ph-activity text-blue-400 text-3xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Last Drill Status</dt>
                            <dd>
                                <div class="text-lg font-bold text-gray-900">
                                    @if($drills->first())
                                        <span class="{{ $drills->first()->status === 'passed' ? 'text-green-600' : ($drills->first()->status === 'running' ? 'text-blue-600' : 'text-red-600') }}">
                                            {{ ucfirst($drills->first()->status) }}
                                        </span>
                                        <span class="text-xs text-gray-400 font-normal ml-1">({{ $drills->first()->created_at->diffForHumans() }})</span>
                                    @else
                                        <span class="text-gray-400">Never Run</span>
                                    @endif
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-purple-500">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="ph ph-clock-clockwise text-purple-400 text-3xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg. Recovery Time (RTO)</dt>
                            <dd>
                                <div class="text-lg font-bold text-gray-900">
                                    @php
                                        $avgRto = $drills->where('status', 'passed')->avg(fn($d) => $d->details['rto_seconds'] ?? 0);
                                    @endphp
                                    {{ $avgRto ? round($avgRto, 2) . 's' : 'N/A' }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">Drill History</h2>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <form action="{{ route('platform.drills.run') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <i class="ph ph-play-circle mr-2"></i>
                    Run New Drill
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RTO (Verification)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RPO (Data Age)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Initiated By</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($drills as $drill)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $drill->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $drill->status === 'passed' ? 'bg-green-100 text-green-800' : ($drill->status === 'running' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($drill->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $drill->message }}">
                                {{ $drill->message }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                {{ $drill->details['rto_seconds'] ?? 'N/A' }}s
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                {{ $drill->details['rpo_hours'] ?? 'N/A' }}h
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $drill->creator->name ?? 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                <i class="ph ph-list-dashes text-gray-400 text-3xl mb-2 block"></i>
                                No drills recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($drills->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $drills->links() }}
        </div>
        @endif
    </div>
@endsection
