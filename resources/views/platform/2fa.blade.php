@extends('layouts.platform')

@section('title', 'Two-Factor')
@section('header', 'Two-Factor Verification')

@section('content')
    <div class="max-w-xl">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Verify Login</h2>
                <p class="mt-1 text-sm text-gray-600">
                    We sent a verification code to <span class="font-mono">{{ $email ?? '' }}</span>. Enter it to continue.
                </p>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                    <form method="POST" action="{{ route('platform.2fa.verify') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Verification code</label>
                            <input
                                type="text"
                                name="code"
                                class="mt-1 block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm font-mono"
                                placeholder="123456"
                                autocomplete="one-time-code"
                                required
                            >
                            <p class="mt-2 text-xs text-gray-500">Code expires in ~10 minutes.</p>
                        </div>

                        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 shadow-sm">
                            <i class="ph ph-check-circle mr-2"></i> Verify
                        </button>
                    </form>

                    <form method="POST" action="{{ route('platform.2fa.resend') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm">
                            <i class="ph ph-paper-plane-right mr-2 text-gray-600"></i> Resend code
                        </button>
                    </form>
                </div>

                <div class="pt-3 border-t border-gray-200 text-xs text-gray-500">
                    If you are not receiving emails, check SMTP settings in <a class="text-primary-700 hover:underline" href="{{ route('platform.settings') }}">Settings</a>.
                </div>
            </div>
        </div>
    </div>
@endsection
