@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Search Analytics</h1>
        <p class="page-subtitle">Monitor search performance and discover user intent</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Searches (30 days)</div>
            <div class="stat-value">{{ $totalSearches ?? 0 }}</div>
            <div class="stat-change positive">↑ 12% from last month</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Unique Queries</div>
            <div class="stat-value">{{ $uniqueQueries ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Avg Response Time</div>
            <div class="stat-value" style="font-size: 24px;">{{ $avgResponseTime ?? 0 }}ms</div>
            <div class="stat-change positive">↓ 15% faster</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">No Results Rate</div>
            <div class="stat-value" style="font-size: 24px; color: var(--warning);">{{ $noResultsRate ?? 0 }}%</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Search Volume Trend (7 days)</h2>
            </div>
            <div style="height: 300px; display: flex; align-items: flex-end; gap: 8px; padding: 20px;">
                <!-- Simple bar chart visualization -->
                @foreach($searchTrends ?? [] as $day)
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                        <div style="
                            width: 100%;
                            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
                            height: {{ ($day['total_searches'] / max(array_column($searchTrends, 'total_searches'))) * 250 }}px;
                            border-radius: 4px 4px 0 0;
                            transition: all 0.3s;
                        " title="{{ $day['total_searches'] }} searches"></div>
                        <small style="margin-top: 8px; font-size: 11px; color: var(--gray);">
                            {{ \Carbon\Carbon::parse($day['date'])->format('D') }}
                        </small>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Quick Stats</h2>
            </div>
            <div style="padding: 10px 0;">
                <div style="padding: 12px 0; border-bottom: 1px solid var(--border);">
                    <div style="font-size: 13px; color: var(--gray); margin-bottom: 4px;">Avg Results per Search</div>
                    <div style="font-size: 20px; font-weight: 600;">{{ $avgResults ?? 0 }}</div>
                </div>
                <div style="padding: 12px 0; border-bottom: 1px solid var(--border);">
                    <div style="font-size: 13px; color: var(--gray); margin-bottom: 4px;">Peak Search Hour</div>
                    <div style="font-size: 20px; font-weight: 600;">{{ $peakHour ?? 'N/A' }}</div>
                </div>
                <div style="padding: 12px 0;">
                    <div style="font-size: 13px; color: var(--gray); margin-bottom: 4px;">Most Active Day</div>
                    <div style="font-size: 20px; font-weight: 600;">{{ $peakDay ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Searches -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🔥 Top 20 Searches (30 days)</h2>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Query</th>
                    <th>Times Searched</th>
                    <th>Avg Results</th>
                    <th>Avg Response</th>
                </tr>
            </thead>
            <tbody>
                @forelse($popularSearches ?? [] as $index => $search)
                    <tr>
                        <td><strong>#{{ $index + 1 }}</strong></td>
                        <td>
                            <strong>{{ $search['query'] }}</strong>
                            @if($index < 3)
                                <span style="margin-left: 8px;">🔥</span>
                            @endif
                        </td>
                        <td>{{ $search['count'] }}</td>
                        <td>{{ number_format($search['avg_results'], 1) }}</td>
                        <td>{{ number_format($search['avg_response_time'] ?? 0, 0) }}ms</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--gray);">
                            No search data yet
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- No Results Searches -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">❌ Searches with No Results (7 days)</h2>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Query</th>
                    <th>Times Searched</th>
                    <th>Last Searched</th>
                    <th>Suggestion</th>
                </tr>
            </thead>
            <tbody>
                @forelse($noResultsSearches ?? [] as $search)
                    <tr>
                        <td><strong>{{ $search['query'] }}</strong></td>
                        <td>{{ $search['count'] }}</td>
                        <td>{{ $search['last_searched']->diffForHumans() }}</td>
                        <td>
                            <span class="badge badge-warning">Consider creating content for this</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: var(--gray);">
                            Great! All searches returned results
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Search Provider Info -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">⚙️ Search Configuration</h2>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <div style="font-size: 13px; color: var(--gray); margin-bottom: 8px;">Search Provider</div>
                <div style="font-size: 18px; font-weight: 600;">
                    {{ $searchProvider ?? 'Database' }}
                    @if($searchProvider === 'meilisearch')
                        <span class="badge badge-success">Fast</span>
                    @endif
                </div>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--gray); margin-bottom: 8px;">Indexed Documents</div>
                <div style="font-size: 18px; font-weight: 600;">{{ $indexedDocs ?? 0 }}</div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .stats-grid .stat-card:hover {
                transform: translateY(-4px);
            }
        </style>
    @endpush
@endsection