@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Security Dashboard</h1>
        <p class="page-subtitle">Monitor and manage security settings</p>
    </div>

    <!-- Security Score -->
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
        <div style="text-align: center; padding: 20px;">
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 12px;">Overall Security Score</div>
            <div style="font-size: 72px; font-weight: 700; margin: 20px 0;">{{ $securityScore ?? 85 }}%</div>
            <div style="font-size: 16px; opacity: 0.9;">
                @if(($securityScore ?? 85) >= 90)
                    🛡️ Excellent Protection
                @elseif(($securityScore ?? 85) >= 70)
                    ✅ Good Security
                @else
                    ⚠️ Needs Improvement
                @endif
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Users with 2FA</div>
            <div class="stat-value">{{ $users2FA ?? 0 }}</div>
            <div class="stat-change">{{ $users2FAPercent ?? 0 }}% of total users</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Sessions</div>
            <div class="stat-value">{{ $activeSessions ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Failed Logins (24h)</div>
            <div class="stat-value" style="color: var(--danger);">{{ $failedLogins24h ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Blocked IPs</div>
            <div class="stat-value">{{ $blockedIPs ?? 0 }}</div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📊 Recent Activity (Last 50)</h2>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Resource</th>
                    <th>IP Address</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditLogs ?? [] as $log)
                    <tr>
                        <td>
                            <strong>{{ $log->user->name ?? 'System' }}</strong>
                            <br>
                            <small style="color: var(--gray);">{{ $log->user->email ?? '-' }}</small>
                        </td>
                        <td>
                            <span class="badge badge-info">{{ $log->action_label }}</span>
                        </td>
                        <td>
                            @if($log->resource_type)
                                {{ class_basename($log->resource_type) }} #{{ $log->resource_id }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <code style="font-size: 12px;">{{ $log->ip_address }}</code>
                        </td>
                        <td>
                            @if($log->status === 'success')
                                <span class="badge badge-success">✓</span>
                            @else
                                <span class="badge badge-danger">✗</span>
                            @endif
                        </td>
                        <td>{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--gray);">
                            No activity yet
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- IP Restrictions -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🚫 IP Restrictions</h2>
            <button class="btn btn-primary" onclick="document.getElementById('add-ip-form').style.display='block'">
                + Add IP Rule
            </button>
        </div>

        <!-- Add IP Form (hidden by default) -->
        <form id="add-ip-form" action="/admin/security/ip-restrictions" method="POST"
            style="display: none; padding: 20px; background: var(--light-gray); border-radius: 8px; margin-bottom: 20px;">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr 2fr 1fr; gap: 12px; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">IP Address</label>
                    <input type="text" name="ip_address" class="form-input" placeholder="192.168.1.100" required>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-input" required>
                        <option value="blacklist">Blacklist</option>
                        <option value="whitelist">Whitelist</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Reason</label>
                    <input type="text" name="reason" class="form-input" placeholder="Suspicious activity">
                </div>
                <button type="submit" class="btn btn-primary">Add</button>
            </div>
        </form>

        <table class="table">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Created</th>
                    <th>Expires</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ipRestrictions ?? [] as $restriction)
                    <tr>
                        <td><code>{{ $restriction->ip_address }}</code></td>
                        <td>
                            @if($restriction->type === 'blacklist')
                                <span class="badge badge-danger">🚫 Blacklist</span>
                            @else
                                <span class="badge badge-success">✓ Whitelist</span>
                            @endif
                            @if($restriction->is_auto_ban)
                                <span class="badge badge-warning" title="Auto-banned">🤖</span>
                            @endif
                        </td>
                        <td>{{ $restriction->reason ?? '-' }}</td>
                        <td>{{ $restriction->created_at->format('M d, Y') }}</td>
                        <td>
                            @if($restriction->is_permanent)
                                <strong>Permanent</strong>
                            @elseif($restriction->expires_at)
                                {{ $restriction->expires_at->diffForHumans() }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <form action="/admin/security/ip-restrictions/{{ $restriction->id }}" method="POST"
                                style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--gray);">
                            No IP restrictions configured
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Failed Login Attempts -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">⚠️ Failed Login Attempts (Last 24 hours)</h2>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>IP Address</th>
                    <th>Attempts</th>
                    <th>Last Attempt</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($failedAttempts ?? [] as $attempt)
                    <tr>
                        <td>{{ $attempt['email'] }}</td>
                        <td><code>{{ $attempt['ip'] }}</code></td>
                        <td>
                            <strong style="color: var(--danger);">{{ $attempt['count'] }}</strong>
                            @if($attempt['count'] >= 5)
                                <span class="badge badge-danger">High Risk</span>
                            @endif
                        </td>
                        <td>{{ $attempt['last_attempt']->diffForHumans() }}</td>
                        <td>
                            <form action="/admin/security/ban-ip" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="ip" value="{{ $attempt['ip'] }}">
                                <button class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Ban IP</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--gray);">
                            ✅ No failed login attempts
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Active Sessions -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">👥 Active Sessions</h2>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Device</th>
                    <th>IP Address</th>
                    <th>Last Activity</th>
                    <th>Trusted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activeSessions ?? [] as $session)
                    <tr>
                        <td><strong>{{ $session->user->name }}</strong></td>
                        <td>
                            {{ $session->device_name ?? 'Unknown' }}
                            <br>
                            <small style="color: var(--gray);">{{ $session->user_agent ?? '-' }}</small>
                        </td>
                        <td><code>{{ $session->ip_address }}</code></td>
                        <td>{{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}</td>
                        <td>
                            @if($session->is_trusted)
                                <span class="badge badge-success">✓ Trusted</span>
                            @else
                                <span class="badge badge-warning">Untrusted</span>
                            @endif
                        </td>
                        <td>
                            <form action="/admin/security/revoke-session" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="session_id" value="{{ $session->id }}">
                                <button class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Revoke</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--gray);">
                            No active sessions
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('styles')
        <style>
            .security-score {
                animation: pulse 2s ease-in-out infinite;
            }

            @keyframes pulse {

                0%,
                100% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.05);
                }
            }
        </style>
    @endpush
@endsection