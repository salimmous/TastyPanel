@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Import Data</h1>
        <p class="page-subtitle">Import recipes from CSV, JSON, or WordPress files</p>
    </div>

    <!-- Upload Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Upload File</h2>
        </div>

        <form action="/admin/imports" method="POST" enctype="multipart/form-data" id="import-form">
            @csrf

            <div class="form-group">
                <label class="form-label">Select Format</label>
                <select name="format" class="form-input" required>
                    <option value="csv">CSV</option>
                    <option value="json">JSON</option>
                    <option value="wordpress">WordPress (WXR)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Upload File</label>
                <input type="file" name="file" class="form-input" accept=".csv,.json,.xml" required>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center;">
                    <input type="checkbox" name="options[update_existing]" value="1" style="margin-right: 8px;">
                    <span>Update existing recipes (match by title)</span>
                </label>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center;">
                    <input type="checkbox" name="options[skip_duplicates]" value="1" checked style="margin-right: 8px;">
                    <span>Skip duplicate recipes</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary">
                📥 Start Import
            </button>
        </form>
    </div>

    <!-- Recent Imports -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Imports</h2>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>File</th>
                    <th>Format</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Processed</th>
                    <th>Success/Errors</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($imports ?? [] as $import)
                    <tr>
                        <td><strong>{{ $import->original_filename }}</strong></td>
                        <td><span class="badge badge-info">{{ strtoupper($import->format) }}</span></td>
                        <td>
                            @if($import->status === 'completed')
                                <span class="badge badge-success">✓ Completed</span>
                            @elseif($import->status === 'processing')
                                <span class="badge badge-warning">⟳ Processing</span>
                            @elseif($import->status === 'failed')
                                <span class="badge badge-danger">✗ Failed</span>
                            @else
                                <span class="badge badge-info">Pending</span>
                            @endif
                        </td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar" style="width: {{ $import->progress_percentage }}%"></div>
                            </div>
                            <small>{{ $import->progress_percentage }}%</small>
                        </td>
                        <td>{{ $import->processed_items }} / {{ $import->total_items }}</td>
                        <td>
                            <span style="color: var(--success);">✓ {{ $import->success_count }}</span> /
                            <span style="color: var(--danger);">✗ {{ $import->error_count }}</span>
                        </td>
                        <td>{{ $import->created_at->diffForHumans() }}</td>
                        <td>
                            <a href="/admin/imports/{{ $import->id }}" class="btn btn-secondary"
                                style="padding: 6px 12px; font-size: 12px;">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--gray);">
                            No imports yet. Upload a file to get started!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('scripts')
        <script>
            // Auto-refresh for processing imports
            setInterval(() => {
                const processingRows = document.querySelectorAll('.badge-warning');
                if (processingRows.length > 0) {
                    location.reload();
                }
            }, 5000);
        </script>
    @endpush
@endsection