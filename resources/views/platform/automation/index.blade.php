@extends('layouts.platform')

@section('title', 'Automation Studio - ' . $tenant->name)
@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('platform.tenants.show', $tenant->id) }}" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <span>Automation Studio: {{ $tenant->name }}</span>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- providers -->
    <div class="card p-6">
        <h3 class="font-semibold text-lg mb-4">AI Providers</h3>
        <form action="{{ route('platform.automation.update') }}" method="POST">
            @csrf
            <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
            <input type="hidden" name="environment" value="{{ $environment ?? 'production' }}">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- OpenAI -->
                <div class="border rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                        <div class="font-medium">OpenAI (ChatGPT)</div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="openai[enabled]" value="1" class="sr-only peer" {{ !empty($data['openai']['enabled']) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm text-gray-700">API Key</label>
                            <input type="password" name="openai[api_key]" value="{{ $data['openai']['api_key'] ?? '' }}" class="input w-full" placeholder="sk-...">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700">Model</label>
                            <select name="openai[model]" class="input w-full">
                                <option value="gpt-4" {{ ($data['openai']['model'] ?? '') === 'gpt-4' ? 'selected' : '' }}>GPT-4</option>
                                <option value="gpt-3.5-turbo" {{ ($data['openai']['model'] ?? '') === 'gpt-3.5-turbo' ? 'selected' : '' }}>GPT-3.5 Turbo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Midjourney -->
                <div class="border rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                        <div class="font-medium">Midjourney (Discord)</div>
                         <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="midjourney[enabled]" value="1" class="sr-only peer" {{ !empty($data['midjourney']['enabled']) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <div class="space-y-3">
                         <div>
                            <label class="block text-sm text-gray-700">Bot Token</label>
                            <input type="password" name="midjourney[bot_token]" value="{{ $data['midjourney']['bot_token'] ?? '' }}" class="input w-full">
                        </div>
                         <div>
                            <label class="block text-sm text-gray-700">Channel ID</label>
                            <input type="text" name="midjourney[channel_id]" value="{{ $data['midjourney']['channel_id'] ?? '' }}" class="input w-full">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>

    <!-- Recent Runs -->
    <div class="card overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
             <h3 class="font-semibold text-lg">Recent Automations</h3>
             <button class="btn btn-sm btn-secondary">Run Manual Workflow</button>
        </div>
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 font-medium">
                <tr>
                    <th class="px-6 py-3">ID</th>
                    <th class="px-6 py-3">Type</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($runs as $run)
                <tr>
                    <td class="px-6 py-3">#{{ $run->id }}</td>
                    <td class="px-6 py-3">{{ $run->type }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                            {{ $run->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $run->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $run->status === 'running' ? 'bg-blue-100 text-blue-800' : '' }}
                        ">
                            {{ $run->status }}
                        </span>
                    </td>
                    <td class="px-6 py-3">{{ $run->created_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">No automation runs yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
