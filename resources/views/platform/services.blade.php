@extends('layouts.platform')

@section('title', 'Service Manager')
@section('header', 'Service Manager')

@section('content')
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($services as $key => $service)
            <div class="bg-white overflow-hidden shadow rounded-lg flex flex-col h-full border border-gray-100">
                <div class="p-6 flex-1">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-shrink-0 p-3 bg-gray-50 rounded-lg text-gray-400">
                            {!! $service['icon'] !!}
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $service['status'] === 'running' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            <span class="flex-shrink-0 w-2 h-2 rounded-full mr-1.5 {{ $service['status'] === 'running' ? 'bg-green-400' : 'bg-red-400' }}"></span>
                            {{ ucfirst($service['status']) }}
                        </span>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900">{{ $service['name'] }}</h3>
                        <p class="text-sm text-gray-500 font-mono mt-1">{{ $service['service'] }}</p>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 grid grid-cols-2 gap-3">
                    @if($service['status'] === 'running')
                        <form action="{{ route('platform.services.action') }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="service" value="{{ $service['service'] }}">
                            <input type="hidden" name="action" value="restart">
                            <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <i class="ph ph-arrows-clockwise mr-2 text-gray-400"></i>
                                Restart
                            </button>
                        </form>
                        <form action="{{ route('platform.services.action') }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="service" value="{{ $service['service'] }}">
                            <input type="hidden" name="action" value="stop">
                            <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" onclick="return confirm('Stop {{ $service['name'] }}? This may cause downtime.')">
                                <i class="ph ph-stop mr-2"></i>
                                Stop
                            </button>
                        </form>
                    @else
                        <form action="{{ route('platform.services.action') }}" method="POST" class="col-span-2">
                            @csrf
                            <input type="hidden" name="service" value="{{ $service['service'] }}">
                            <input type="hidden" name="action" value="start">
                            <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent shadow-sm text-sm leading-4 font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="ph ph-play mr-2"></i>
                                Start Service
                            </button>
                        </form>
                    @endif

                    <button class="col-span-2 w-full inline-flex justify-center items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        onclick="viewLogs('{{ $service['service'] }}', '{{ $service['name'] }}')">
                        <i class="ph ph-list-dashes mr-2 text-gray-400"></i>
                        View Logs
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Logs Modal -->
    <div id="logsModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeLogs()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start justify-between mb-4">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            <span id="logsTitle">Service Logs</span>
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Last 50 lines
                            </span>
                        </h3>
                    </div>
                    <button type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeLogs()">
                        <span class="sr-only">Close</span>
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>
                <div class="mt-2">
                    <div class="bg-gray-900 rounded-lg p-4 font-mono text-sm text-gray-300 overflow-auto h-96">
                        <pre id="logsViewer" class="whitespace-pre-wrap">Loading...</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewLogs(service, name) {
            const modal = document.getElementById('logsModal');
            const title = document.getElementById('logsTitle');
            const viewer = document.getElementById('logsViewer');

            modal.classList.remove('hidden');
            title.textContent = name;
            viewer.textContent = 'Loading logs...';

            // Prevent body scrolling
            document.body.style.overflow = 'hidden';

            fetch(`/platform/services/logs?service=${service}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        viewer.textContent = `Error: ${data.error}`;
                    } else {
                        viewer.textContent = data.logs || 'No logs found.';
                        const container = viewer.parentElement;
                        container.scrollTop = container.scrollHeight;
                    }
                })
                .catch(err => {
                    viewer.textContent = 'Failed to load logs.';
                    console.error(err);
                });
        }

        function closeLogs() {
            document.getElementById('logsModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Close on escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeLogs();
            }
        });
    </script>
@endsection
@endsection