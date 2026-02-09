@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Webhooks</h1>
        <p class="page-subtitle">Configure event-based webhooks for external integrations</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Webhooks</div>
            <div class="stat-value">{{ $webhooks->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active</div>
            <div class="stat-value" style="color: var(--success);">{{ $webhooks->where('is_active', true)->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Deliveries (24h)</div>
            <div class="stat-value">{{ $deliveries24h ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Success Rate</div>
            <div class="stat-value" style="font-size: 24px;">{{ $successRate ?? 0 }}%</div>
        </div>
    </div>

    <!-- Create Webhook -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Create Webhook</h2>
        </div>

        <form action="/admin/webhooks" method="POST">
            @csrf

            <div class="form-group">
                <label class="form-label">Event</label>
                <select name="event" class="form-input" required>
                    <option value="recipe.created">Recipe Created</option>
                    <option value="recipe.updated">Recipe Updated</option>
                    <option value="recipe.deleted">Recipe Deleted</option>
                    <option value="recipe.*">All Recipe Events</option>
                    <option value="category.created">Category Created</option>
                    <option value="category.updated">Category Updated</option>
                    <option value="category.*">All Category Events</option>
                    <option value="*">All Events</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">URL</label>
                <input type="url" name="url" class="form-input" placeholder="https://your-app.com/webhook" required>
            </div>

            <div class="form-group">
                <label class="form-label">Secret (Optional)</label>
                <input type="text" name="secret" class="form-input"
                    placeholder="Your webhook secret for signature verification">
                <small style="color: var(--gray); font-size: 12px;">Used to sign webhook payloads for security</small>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center;">
                    <input type="checkbox" name="is_active" value="1" checked style="margin-right: 8px;">
                    <span>Active</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary">🔗 Create Webhook</button>
        </form>
    </div>

    <!-- Webhooks List -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Configured Webhooks</h2>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>URL</th>
                    <th>Status</th>
                    <th>Last Triggered</th>
                    <th>Success/Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($webhooks as $webhook)
                    <tr>
                        <td><span class="badge badge-info">{{ $webhook->event }}</span></td>
                        <td><code style="font-size: 12px;">{{ $webhook->url }}</code></td>
                        <td>
                            @if($webhook->is_active)
                                <span class="badge badge-success">✓ Active</span>
                            @else
                                <span class="badge badge-danger">✗ Inactive</span>
                            @endif
                        </td>
                        <td>
                            @if($webhook->last_triggered_at)
                                {{ $webhook->last_triggered_at->diffForHumans() }}
                            @else
                                <span style="color: var(--gray);">Never</span>
                            @endif
                        </td>
                        <td>
                            <span style="color: var(--success);">{{ $webhook->success_count }}</span> /
                            <span>{{ $webhook->total_deliveries }}</span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <form action="/admin/webhooks/{{ $webhook->id }}/test" method="POST" style="display: inline;">
                                    @csrf
                                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">🧪
                                        Test</button>
                                </form>

                                <a href="/admin/webhooks/{{ $webhook->id }}/deliveries" class="btn btn-secondary"
                                    style="padding: 6px 12px; font-size: 12px;">📋 Logs</a>

                                <form action="/admin/webhooks/{{ $webhook->id }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;"
                                        onclick="return confirm('Delete this webhook?')">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--gray);">
                            No webhooks configured. Create one above!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Event Types Help -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📚 Event Types</h2>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Description</th>
                    <th>Payload</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>recipe.created</code></td>
                    <td>Triggered when a new recipe is created</td>
                    <td><code>{ event, recipe: {...} }</code></td>
                </tr>
                <tr>
                    <td><code>recipe.updated</code></td>
                    <td>Triggered when a recipe is updated</td>
                    <td><code>{ event, recipe: {...}, changes: {...} }</code></td>
                </tr>
                <tr>
                    <td><code>recipe.deleted</code></td>
                    <td>Triggered when a recipe is deleted</td>
                    <td><code>{ event, recipe_id }</code></td>
                </tr>
                <tr>
                    <td><code>recipe.*</code></td>
                    <td>Matches all recipe events</td>
                    <td>Varies by event</td>
                </tr>
                <tr>
                    <td><code>*</code></td>
                    <td>Matches all events (use with caution)</td>
                    <td>Varies by event</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection