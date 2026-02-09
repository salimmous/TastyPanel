@extends('layouts.platform')

@section('title', 'Revenue & Subscriptions')
@section('header', 'Revenue Overview')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- MRR Card -->
    <div class="card p-6">
        <div class="text-gray-500 text-sm uppercase font-semibold mb-2">Monthly Recurring Revenue</div>
        <div class="text-3xl font-bold text-gray-900">{{ $dashboard['mrr_formatted'] ?? '$0.00' }}</div>
        <div class="text-sm {{ ($dashboard['mrr_growth'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
            {{ ($dashboard['mrr_growth'] ?? 0) >= 0 ? '+' : '' }}{{ $dashboard['mrr_growth'] ?? 0 }}% from last month
        </div>
    </div>

    <!-- Active Subs -->
    <div class="card p-6">
        <div class="text-gray-500 text-sm uppercase font-semibold mb-2">Active Subscriptions</div>
        <div class="text-3xl font-bold text-gray-900">{{ $dashboard['active_subs'] ?? 0 }}</div>
        <div class="text-sm text-gray-500 mt-2">
            Total Subscribers
        </div>
    </div>

    <!-- ARR -->
    <div class="card p-6">
        <div class="text-gray-500 text-sm uppercase font-semibold mb-2">Est. Annual Run Rate</div>
        <div class="text-3xl font-bold text-gray-900">{{ $dashboard['arr_formatted'] ?? '$0.00' }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Transactions -->
    <div class="card">
        <div class="p-6 border-b border-gray-100">
            <h3 class="font-semibold text-lg">Recent Transactions</h3>
        </div>
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 font-medium">
                <tr>
                    <th class="px-6 py-3">Tenant</th>
                    <th class="px-6 py-3">Amount</th>
                    <th class="px-6 py-3">Date</th>
                    <th class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($dashboard['recent_invoices'] ?? [] as $invoice)
                <tr>
                    <td class="px-6 py-3">{{ $invoice->tenant->name ?? 'Unknown' }}</td>
                    <td class="px-6 py-3 font-medium">{{ $invoice->amount_formatted }}</td>
                    <td class="px-6 py-3">{{ $invoice->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">No recent transactions.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Plan Breakdown -->
    <div class="card p-6">
        <h3 class="font-semibold text-lg mb-4">Revenue by Plan</h3>
        <div class="space-y-4">
             @forelse($dashboard['plans_breakdown'] ?? [] as $plan)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-700">{{ $plan['name'] }}</span>
                        <span class="text-gray-900 font-semibold">{{ $plan['revenue_formatted'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $plan['percentage'] }}%"></div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ $plan['count'] }} active subscriptions</div>
                </div>
            @empty
                <div class="text-center text-gray-500 py-8">No plan data available.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
