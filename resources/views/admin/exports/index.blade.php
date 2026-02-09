@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Export Data</h1>
        <p class="page-subtitle">Export your recipes in various formats</p>
    </div>

    <!-- Export Builder -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Create New Export</h2>
        </div>

        <form action="/admin/exports" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Format</label>
                    <select name="format" class="form-input" required>
                        <option value="csv">CSV</option>
                        <option value="json">JSON</option>
                        <option value="wordpress">WordPress (WXR)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-input" required>
                        <option value="recipe">Recipes</option>
                        <option value="category">Categories</option>
                    </select>
                </div>
            </div>

            <div style="border-top: 1px solid var(--border); padding-top: 20px; margin-top: 10px;">
                <h3 style="font-size: 16px; margin-bottom: 16px; font-weight: 600;">Filters (Optional)</h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="filters[category_id]" class="form-input">
                            <option value="">All Categories</option>
                            <!-- Categories will be loaded dynamically -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="filters[status]" class="form-input">
                            <option value="">All Statuses</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date From</label>
                        <input type="date" name="filters[date_from]" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date To</label>
                        <input type="date" name="filters[date_to]" class="form-input">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                📤 Generate Export
            </button>
        </form>
    </div>

    <!-- Export History -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Export History</h2>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>File</th>
                    <th>Format</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Size</th>
                    <th>Items</th>
                    <th>Downloads</th>
                    <th>Expires</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($exports ?? [] as $export)
                    <tr>
                        <td><strong>{{ $export->filename }}</strong></td>
                        <td><span class="badge badge-info">{{ strtoupper($export->format) }}</span></td>
                        <td>{{ ucfirst($export->type) }}</td>
                        <td>
                            @if($export->status === 'completed')
                                <span class="badge badge-success">✓ Ready</span>
                            @elseif($export->status === 'processing')
                                <span class="badge badge-warning">⟳ Processing</span>
                            @else
                                <span class="badge badge-danger">Failed</span>
                            @endif
                        </td>
                        <td>{{ $export->human_file_size }}</td>
                        <td>{{ $export->total_items }}</td>
                        <td>{{ $export->download_count }}</td>
                        <td>
                            @if($export->isExpired())
                                <span style="color: var(--danger);">Expired</span>
                            @else
                                {{ $export->expires_at->diffForHumans() }}
                            @endif
                        </td>
                        <td>
                            @if($export->isReady() && !$export->isExpired())
                                <a href="/admin/exports/{{ $export->id }}/download" class="btn btn-success"
                                    style="padding: 6px 12px; font-size: 12px;">
                                    ⬇️ Download
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--gray);">
                            No exports yet. Create your first export above!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('scripts')
        <script>
            // Auto-refresh for processing exports
            setInterval(() => {
                const processingRows = document.querySelectorAll('.badge-warning');
                if (processingRows.length > 0) {
                    location.reload();
                }
            }, 5000);
        </script>
    @endpush
@endsection