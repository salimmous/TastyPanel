@extends('layouts.platform')

@section('header', 'SSL Certificates')

@section('content')
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Site</th>
                        <th>Provider</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($certificates as $cert)
                        <tr class="group hover:bg-stone-50 transition-colors">
                            <td class="font-medium text-stone-900">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-lock-key text-emerald-500"></i>
                                    {{ $cert->domain }}
                                </div>
                            </td>
                            <td>
                                @if($cert->site)
                                    <a href="{{ route('platform.tenants.show', $cert->site->id) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
                                        {{ $cert->site->name }}
                                    </a>
                                @else
                                    <span class="text-stone-400 text-sm">—</span>
                                @endif
                            </td>
                            <td class="text-stone-600">Let's Encrypt</td>
                            <td class="text-stone-600">
                                @if($cert->expires_at)
                                    <span class="{{ $cert->expires_at->isPast() ? 'text-red-600 font-bold' : ($cert->expires_at->diffInDays(now()) < 30 ? 'text-amber-600 font-bold' : 'text-stone-600') }}">
                                        {{ $cert->expires_at->format('M d, Y') }}
                                    </span>
                                    <span class="text-xs text-stone-400 ml-1">({{ $cert->expires_at->diffForHumans() }})</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $cert->status === 'active' || $cert->status === 'issued' ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $cert->status === 'active' || $cert->status === 'issued' ? 'bg-emerald-500' : 'bg-stone-400' }}"></span>
                                    {{ ucfirst($cert->status) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <!-- Actions like Renew/Revoke could go here -->
                                <button type="button" class="text-stone-400 hover:text-stone-600" title="Auto-renews automatically">
                                    <i class="ph ph-arrows-clockwise"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center text-stone-500">
                                    <i class="ph ph-certificate text-4xl mb-4 text-stone-300"></i>
                                    <p class="text-lg font-medium text-stone-900">No active certificates</p>
                                    <p class="text-sm mt-1">Issue SSL from the Site details page.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($certificates->hasPages())
            <div class="px-6 py-4 border-t border-stone-200">
                {{ $certificates->links() }}
            </div>
        @endif
    </div>
@endsection
